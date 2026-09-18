<?php

namespace App\Domains\Crm\Services;

use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadActivity;
use App\Domains\Crm\Models\LeadCampaign;
use App\Domains\Crm\Models\LeadSource;
use App\Domains\Crm\Models\MetaLeadForm;
use App\Domains\Crm\Models\MetaLeadLog;
use App\Support\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MetaLeadService
{
    public function __construct(protected AuditLogService $auditLogService) {}

    public function verifyToken(?string $mode, ?string $token, ?string $challenge): ?string
    {
        if ($mode === 'subscribe' && $token && hash_equals((string) config('services.meta.verify_token'), $token)) {
            return $challenge;
        }

        return null;
    }

    public function handleWebhook(array $payload): MetaLeadLog
    {
        $externalId = $this->extractExternalLeadId($payload);

        $log = MetaLeadLog::create([
            'external_lead_id' => $externalId,
            'status' => 'received',
            'attempts' => 1,
            'payload' => $payload,
        ]);

        try {
            $lead = $this->ingestPayload($payload, $log);

            $log->update([
                'status' => $lead ? 'processed' : 'duplicate',
                'result' => $lead ? "Lead #{$lead->id} created" : 'Duplicate skipped',
                'processed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);
            Log::warning('Meta lead webhook failed', ['error' => $e->getMessage()]);
        }

        return $log->fresh();
    }

    public function retryFailed(MetaLeadLog $log): MetaLeadLog
    {
        $log->increment('attempts');
        $log->update(['status' => 'retried', 'last_error' => null]);

        try {
            $lead = $this->ingestPayload($log->payload ?? [], $log);
            $log->update([
                'status' => $lead ? 'processed' : 'duplicate',
                'result' => $lead ? "Lead #{$lead->id} created" : 'Duplicate skipped',
                'processed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);
        }

        return $log->fresh();
    }

    public function ingestPayload(array $payload, ?MetaLeadLog $log = null): ?Lead
    {
        $externalId = $this->extractExternalLeadId($payload);
        $fields = $this->normalizeFields($payload);

        if ($externalId && Lead::where('external_lead_id', $externalId)->exists()) {
            return null;
        }

        $mobile = $this->normalizePhone($fields['mobile'] ?? $fields['phone'] ?? null);
        $email = isset($fields['email']) ? strtolower(trim((string) $fields['email'])) : null;

        if ($mobile && Lead::where('mobile', $mobile)->where('status', '!=', 'lost')->exists()) {
            return null;
        }

        if ($email && Lead::where('email', $email)->where('status', '!=', 'lost')->exists()) {
            return null;
        }

        return DB::transaction(function () use ($payload, $fields, $externalId, $mobile, $email) {
            $source = LeadSource::query()->firstOrCreate(
                ['code' => 'meta'],
                ['name' => 'Meta Lead Ads', 'is_active' => true]
            );

            $campaign = null;
            if (! empty($fields['campaign_id']) || ! empty($fields['campaign_name'])) {
                $campaign = LeadCampaign::query()->firstOrCreate(
                    ['external_campaign_id' => (string) ($fields['campaign_id'] ?? $fields['campaign_name'])],
                    [
                        'name' => $fields['campaign_name'] ?? ('Campaign '.$fields['campaign_id']),
                        'platform' => 'meta',
                        'is_active' => true,
                    ]
                );
            }

            $form = null;
            if (! empty($fields['form_id'])) {
                $form = MetaLeadForm::query()->firstOrCreate(
                    ['form_id' => (string) $fields['form_id']],
                    [
                        'form_name' => $fields['form_name'] ?? null,
                        'page_id' => $fields['page_id'] ?? null,
                        'lead_campaign_id' => $campaign?->id,
                        'is_active' => true,
                    ]
                );
            }

            if (! empty($fields['leadgen_id']) && empty($fields['name']) && empty($mobile) && empty($email)) {
                $fetched = $this->fetchLeadFromGraph((string) $fields['leadgen_id']);
                $fields = array_merge($fields, $fetched);
                $mobile = $this->normalizePhone($fields['mobile'] ?? $fields['phone'] ?? $mobile);
                $email = isset($fields['email']) ? strtolower(trim((string) $fields['email'])) : $email;
            }

            $lead = Lead::create([
                'lead_source_id' => $source->id,
                'lead_campaign_id' => $campaign?->id,
                'meta_lead_form_id' => $form?->id,
                'external_lead_id' => $externalId,
                'name' => $fields['name'] ?? $fields['full_name'] ?? 'Meta Lead',
                'mobile' => $mobile,
                'email' => $email,
                'organization' => $fields['company'] ?? $fields['organization'] ?? null,
                'city' => $fields['city'] ?? null,
                'state' => $fields['state'] ?? null,
                'interested_product' => $fields['product'] ?? $fields['interested_product'] ?? null,
                'status' => 'new',
                'priority' => 'normal',
                'meta_payload' => $payload,
                'notes' => $fields['notes'] ?? null,
            ]);

            LeadActivity::create([
                'lead_id' => $lead->id,
                'activity_type' => 'note',
                'body' => 'Lead received from Meta webhook',
                'meta' => ['source' => 'meta'],
            ]);

            $this->auditLogService->record($lead, 'created', 'Meta Webhook');

            return $lead;
        });
    }

    protected function fetchLeadFromGraph(string $leadgenId): array
    {
        $token = config('services.meta.access_token');
        if (! $token) {
            return ['leadgen_id' => $leadgenId];
        }

        $response = Http::get("https://graph.facebook.com/v19.0/{$leadgenId}", [
            'access_token' => $token,
            'fields' => 'id,created_time,field_data,ad_id,form_id,campaign_id',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Unable to fetch Meta lead details: '.$response->body());
        }

        return $this->normalizeFields($response->json());
    }

    protected function extractExternalLeadId(array $payload): ?string
    {
        return data_get($payload, 'entry.0.changes.0.value.leadgen_id')
            ?? data_get($payload, 'leadgen_id')
            ?? data_get($payload, 'id')
            ?? null;
    }

    protected function normalizeFields(array $payload): array
    {
        $fields = [];

        $value = data_get($payload, 'entry.0.changes.0.value', []);
        if (is_array($value)) {
            $fields = array_merge($fields, $value);
        }

        foreach (data_get($payload, 'field_data', []) as $row) {
            $name = strtolower((string) ($row['name'] ?? ''));
            $values = $row['values'] ?? [];
            $fields[$name] = is_array($values) ? ($values[0] ?? null) : $values;
        }

        foreach (['name', 'full_name', 'email', 'phone', 'mobile', 'company', 'city', 'state'] as $key) {
            if (isset($payload[$key])) {
                $fields[$key] = $payload[$key];
            }
        }

        if (! empty($fields['full_name']) && empty($fields['name'])) {
            $fields['name'] = $fields['full_name'];
        }

        return $fields;
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return $digits ?: null;
    }
}
