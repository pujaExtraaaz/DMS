<?php

namespace Tally\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\JobWork;
use Tally\Models\Ledger;
use Tally\Models\Product;

class JobWorkService
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, ?Branch $branch, array $input, ?JobWork $job = null): JobWork
    {
        return DB::transaction(function () use ($company, $branch, $input, $job) {
            if ($job && ! $job->isOpen()) {
                throw ValidationException::withMessages([
                    'status' => 'Closed job work cannot be changed.',
                ]);
            }

            $vendor = Ledger::query()
                ->where('company_id', $company->id)
                ->whereKey((int) ($input['vendor_ledger_id'] ?? 0))
                ->firstOrFail();

            $productId = isset($input['product_id']) && $input['product_id'] !== '' ? (int) $input['product_id'] : null;
            if ($productId !== null) {
                Product::query()->where('company_id', $company->id)->whereKey($productId)->firstOrFail();
            }

            $issued = number_format((float) ($input['issued_qty'] ?? 0), 4, '.', '');
            $received = number_format((float) ($input['received_qty'] ?? ($job?->received_qty ?? 0)), 4, '.', '');

            if ((float) $issued <= 0) {
                throw ValidationException::withMessages([
                    'issued_qty' => 'Issued quantity must be greater than zero.',
                ]);
            }

            if ((float) $received > (float) $issued) {
                throw ValidationException::withMessages([
                    'received_qty' => 'Received quantity cannot exceed issued quantity.',
                ]);
            }

            $payload = [
                'branch_id' => $branch?->id ?? ($input['branch_id'] ?? null),
                'issue_date' => (string) ($input['issue_date'] ?? now()->toDateString()),
                'vendor_ledger_id' => $vendor->id,
                'product_id' => $productId,
                'issued_qty' => $issued,
                'received_qty' => $received,
                'job_charges' => number_format((float) ($input['job_charges'] ?? 0), 2, '.', ''),
                'narration' => ($input['narration'] ?? null) ?: null,
                'status' => ($input['status'] ?? null) ?: ($job?->status ?? 'open'),
            ];

            if ($job) {
                $job->update($payload);

                return $job->fresh(['vendor', 'product']);
            }

            $seq = JobWork::query()->where('company_id', $company->id)->count() + 1;

            return JobWork::query()->create($payload + [
                'company_id' => $company->id,
                'number' => ($input['number'] ?? null) ?: 'JW-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
            ])->fresh(['vendor', 'product']);
        });
    }

    public function receive(JobWork $job, string $qty, ?string $status = null): JobWork
    {
        if (! $job->isOpen()) {
            throw ValidationException::withMessages([
                'status' => 'This job work is already closed.',
            ]);
        }

        $add = (float) $qty;
        if ($add <= 0) {
            throw ValidationException::withMessages([
                'received_qty' => 'Enter a positive receipt quantity.',
            ]);
        }

        $newReceived = (float) $job->received_qty + $add;
        if ($newReceived > (float) $job->issued_qty) {
            throw ValidationException::withMessages([
                'received_qty' => 'Received quantity cannot exceed issued quantity.',
            ]);
        }

        $closed = abs($newReceived - (float) $job->issued_qty) < 0.00005;
        $job->update([
            'received_qty' => number_format($newReceived, 4, '.', ''),
            'status' => $status ?: ($closed ? 'closed' : 'open'),
        ]);

        return $job->fresh(['vendor', 'product']);
    }
}
