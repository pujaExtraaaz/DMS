<?php

namespace Tally\Banking;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tally\Integrations\IntegrationEndpointService;
use Tally\Models\Company;
use Tally\Models\ElectronicPayment;
use Tally\Models\IntegrationEndpoint;

class NeftRtgsPaymentFileService
{
    public function __construct(
        private readonly IntegrationEndpointService $endpoints,
    ) {}

    /**
     * @param  Collection<int, ElectronicPayment>|iterable<ElectronicPayment>  $payments
     * @return array{csv: string, filename: string, count: int}
     */
    public function buildCsv(iterable $payments): array
    {
        $rows = [[
            'Payment Date',
            'Amount',
            'Beneficiary Name',
            'Account Number',
            'IFSC',
            'Payment Mode',
            'Narration',
            'Client Ref',
        ]];

        $count = 0;
        foreach ($payments as $payment) {
            if (! $payment instanceof ElectronicPayment) {
                continue;
            }
            if (! in_array($payment->channel, ['neft', 'rtgs'], true)) {
                continue;
            }
            if (in_array($payment->status, ['cancelled', 'failed'], true)) {
                continue;
            }

            $account = trim((string) ($payment->beneficiary_account ?? ''));
            $ifsc = strtoupper(trim((string) ($payment->beneficiary_ifsc ?? '')));
            if ($account === '' || $ifsc === '') {
                throw ValidationException::withMessages([
                    'payments' => 'Payment #'.$payment->id.' needs beneficiary account and IFSC before export.',
                ]);
            }
            if (! preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
                throw ValidationException::withMessages([
                    'payments' => 'Payment #'.$payment->id.' has an invalid IFSC.',
                ]);
            }

            $rows[] = [
                $payment->payment_date?->format('Y-m-d') ?? now()->toDateString(),
                number_format((float) $payment->amount, 2, '.', ''),
                $payment->beneficiary ?: 'Beneficiary',
                $account,
                $ifsc,
                strtoupper($payment->channel),
                $payment->reference ?: ('EP-'.$payment->id),
                $payment->reference ?: ('EP-'.$payment->id),
            ];
            $count++;
        }

        if ($count === 0) {
            throw ValidationException::withMessages([
                'payments' => 'No NEFT/RTGS payments ready to export.',
            ]);
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return [
            'csv' => $csv,
            'filename' => 'neft-rtgs-'.now()->format('YmdHis').'.csv',
            'count' => $count,
        ];
    }

    /**
     * Mark exported payments as sent and optionally POST CSV to payment_api endpoint.
     *
     * @param  Collection<int, ElectronicPayment>|iterable<ElectronicPayment>  $payments
     * @return array{csv: string, filename: string, count: int, pushed: bool}
     */
    public function exportAndMark(Company $company, iterable $payments, bool $push = true): array
    {
        $built = $this->buildCsv($payments);

        foreach ($payments as $payment) {
            if (! $payment instanceof ElectronicPayment) {
                continue;
            }
            if (! in_array($payment->channel, ['neft', 'rtgs'], true)) {
                continue;
            }
            if (in_array($payment->status, ['cancelled', 'failed', 'cleared'], true)) {
                continue;
            }
            $payment->update([
                'status' => 'sent',
                'reference' => $payment->reference ?: pathinfo($built['filename'], PATHINFO_FILENAME),
            ]);
        }

        $pushed = false;
        if ($push) {
            $pushed = $this->pushToApi($company, $built['csv'], $built['filename']);
        }

        return $built + ['pushed' => $pushed];
    }

    public function pushToApi(Company $company, string $csv, string $filename): bool
    {
        $endpoint = IntegrationEndpoint::query()
            ->where('company_id', $company->id)
            ->where('kind', 'payment_api')
            ->where('is_active', true)
            ->whereNotNull('url')
            ->orderBy('id')
            ->first();

        if (! $endpoint) {
            return false;
        }

        try {
            $request = Http::timeout(60)->attach('file', $csv, $filename);
            if ($endpoint->auth_header) {
                $request = $request->withHeaders(['Authorization' => $endpoint->auth_header]);
            }
            $response = $request->post($endpoint->url);
            if (! $response->successful()) {
                $this->endpoints->markSynced($endpoint, 'HTTP '.$response->status());

                return false;
            }
            $this->endpoints->markSynced($endpoint);

            return true;
        } catch (\Throwable $e) {
            $this->endpoints->markSynced($endpoint, mb_substr($e->getMessage(), 0, 500));

            return false;
        }
    }
}
