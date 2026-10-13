<?php

namespace Tally\Integrations;

use Tally\Models\Company;
use Tally\Models\IntegrationEndpoint;

class IntegrationEndpointService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Company $company, array $data, ?IntegrationEndpoint $endpoint = null): IntegrationEndpoint
    {
        $endpoint ??= new IntegrationEndpoint(['company_id' => $company->id]);
        $endpoint->fill([
            'company_id' => $company->id,
            'name' => $data['name'],
            'kind' => $data['kind'],
            'url' => $data['url'] ?? null,
            'bank_account_id' => ($data['bank_account_id'] ?? null) ?: null,
            'auth_header' => ($data['auth_header'] ?? null) ?: null,
            'config' => $data['config'] ?? $endpoint->config,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ])->save();

        return $endpoint;
    }

    public function markSynced(IntegrationEndpoint $endpoint, ?string $error = null): IntegrationEndpoint
    {
        $endpoint->update([
            'last_synced_at' => $error ? $endpoint->last_synced_at : now(),
            'last_error' => $error,
            'retry_count' => $error ? $endpoint->retry_count + 1 : 0,
        ]);

        return $endpoint->fresh();
    }
}
