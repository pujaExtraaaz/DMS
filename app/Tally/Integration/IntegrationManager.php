<?php

namespace Tally\Integration;

use Tally\Models\Integration;
use Tally\Models\IntegrationSync;
use App\Models\User;
use Tally\Models\WebhookEndpoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IntegrationManager
{
    public function __construct(private readonly WebhookDispatcher $dispatcher) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(User $user, array $data, ?Integration $integration = null): Integration
    {
        return DB::transaction(function () use ($user, $data, $integration) {
            $endpoint = $integration?->endpoint;

            if ($endpoint) {
                $endpoint->update([
                    'name' => $data['name'],
                    'url' => $data['url'],
                    'events' => $data['events'],
                    'is_active' => (bool) $data['is_active'],
                ]);
            } else {
                $endpoint = WebhookEndpoint::query()->create([
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'url' => $data['url'],
                    'secret' => Str::random(40),
                    'events' => $data['events'],
                    'is_active' => (bool) $data['is_active'],
                ]);
            }

            $credentials = $integration?->credentials ?? [];

            if (($data['api_key'] ?? '') !== '') {
                $credentials['api_key'] = $data['api_key'];
            }

            $payload = [
                'company_id' => $data['company_id'],
                'webhook_endpoint_id' => $endpoint->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'direction' => $data['direction'],
                'external_system' => $data['external_system'] ?: null,
                'credentials' => $credentials === [] ? null : $credentials,
                'is_active' => (bool) $data['is_active'],
            ];

            if ($integration) {
                $integration->update($payload);

                return $integration->fresh('endpoint');
            }

            return Integration::query()->create($payload)->load('endpoint');
        });
    }

    public function sync(Integration $integration): IntegrationSync
    {
        if (! $integration->is_active) {
            throw ValidationException::withMessages([
                'integration' => 'Activate the integration before syncing.',
            ]);
        }

        $sync = $integration->syncs()->create([
            'status' => 'running',
            'message' => 'Manual sync started.',
            'started_at' => now(),
        ]);

        try {
            $this->dispatcher->record('integration.sync', [
                'integration_id' => $integration->id,
                'company_id' => $integration->company_id,
                'name' => $integration->name,
                'external_system' => $integration->external_system,
            ]);
            $sync->update([
                'status' => 'completed',
                'message' => 'Sync event queued through the webhook dispatcher.',
                'finished_at' => now(),
            ]);
            $integration->update([
                'last_status' => 'completed',
                'last_synced_at' => now(),
                'last_error' => null,
            ]);
        } catch (\Throwable $exception) {
            $sync->update([
                'status' => 'failed',
                'message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);
            $integration->update([
                'last_status' => 'failed',
                'last_error' => $exception->getMessage(),
            ]);
        }

        return $sync->fresh();
    }

    public function retryFailed(Integration $integration): int
    {
        $count = 0;
        $deliveries = $integration->endpoint?->deliveries()->where('status', 'failed')->limit(25)->get() ?? collect();

        foreach ($deliveries as $delivery) {
            if ($delivery->canRetry()) {
                $this->dispatcher->attempt($delivery);
                $count++;
            }
        }

        return $count;
    }
}
