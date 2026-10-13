<?php

namespace Tally\Banking;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tally\Integrations\IntegrationEndpointService;
use Tally\Models\BankAccount;
use Tally\Models\Company;
use Tally\Models\IntegrationEndpoint;

class BankFeedService
{
    public function __construct(
        private readonly BankStatementImporter $importer,
        private readonly BankCategorizationService $categorization,
        private readonly IntegrationEndpointService $endpoints,
    ) {}

    /**
     * Pull all active bank_feed endpoints (optionally for one company).
     *
     * @return list<array{endpoint_id: int, imported: int, duplicates: int, errors: list<string>}>
     */
    public function pullAll(?Company $company = null): array
    {
        $query = IntegrationEndpoint::query()
            ->where('kind', 'bank_feed')
            ->where('is_active', true)
            ->whereNotNull('url');

        if ($company) {
            $query->where('company_id', $company->id);
        }

        $results = [];
        foreach ($query->orderBy('id')->get() as $endpoint) {
            $results[] = $this->pull($endpoint);
        }

        return $results;
    }

    /**
     * @return array{endpoint_id: int, imported: int, duplicates: int, errors: list<string>}
     */
    public function pull(IntegrationEndpoint $endpoint): array
    {
        abort_unless($endpoint->kind === 'bank_feed', 404);

        try {
            $account = $this->resolveAccount($endpoint);
            $path = $this->materializeFile($endpoint);
            $extension = strtolower(pathinfo(parse_url($endpoint->url, PHP_URL_PATH) ?: $endpoint->url, PATHINFO_EXTENSION) ?: 'csv');
            if (! in_array($extension, ['csv', 'txt', 'ofx', 'qfx', 'xlsx', 'xls'], true)) {
                $extension = 'csv';
            }

            $result = $this->importer->importPath($account, $path, $extension);
            if (is_file($path) && str_starts_with(basename($path), 'bank-feed-')) {
                @unlink($path);
            }

            $this->categorization->apply($endpoint->company);
            $this->endpoints->markSynced($endpoint);

            return [
                'endpoint_id' => $endpoint->id,
                'imported' => $result['imported'],
                'duplicates' => $result['duplicates'],
                'errors' => $result['errors'],
            ];
        } catch (\Throwable $e) {
            $this->endpoints->markSynced($endpoint, mb_substr($e->getMessage(), 0, 500));

            throw $e;
        }
    }

    private function resolveAccount(IntegrationEndpoint $endpoint): BankAccount
    {
        $accountId = $endpoint->bank_account_id
            ?? data_get($endpoint->config, 'bank_account_id');

        if (! $accountId) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Link a bank account on this bank feed endpoint.',
            ]);
        }

        $account = BankAccount::query()
            ->where('company_id', $endpoint->company_id)
            ->whereKey($accountId)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Bank account not found for this feed.',
            ]);
        }

        return $account;
    }

    private function materializeFile(IntegrationEndpoint $endpoint): string
    {
        $url = trim((string) $endpoint->url);
        if ($url === '') {
            throw ValidationException::withMessages(['url' => 'Feed URL or file path is required.']);
        }

        if ($this->isLocalPath($url)) {
            if (! is_file($url)) {
                throw ValidationException::withMessages(['url' => 'Local feed file was not found.']);
            }

            return $url;
        }

        $request = Http::timeout(60)->accept('*/*');
        if ($endpoint->auth_header) {
            $request = $request->withHeaders(['Authorization' => $endpoint->auth_header]);
        }

        $response = $request->get($url);
        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'url' => 'Feed download failed with HTTP '.$response->status().'.',
            ]);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'bank-feed-');
        if ($tmp === false) {
            throw ValidationException::withMessages(['url' => 'Could not create a temp file for the feed.']);
        }

        file_put_contents($tmp, $response->body());

        return $tmp;
    }

    private function isLocalPath(string $url): bool
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            return false;
        }

        return str_starts_with($url, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $url) === 1;
    }
}
