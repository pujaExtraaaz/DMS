<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Integration\WebhookDispatcher;
use Tally\Integration\WebhookUrl;
use Tally\Models\WebhookDelivery;
use Tally\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebhookController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->webhookEndpoints()->latest();

        return $this->page($query->paginate($this->perPage($request)), fn (WebhookEndpoint $endpoint) => $this->transform($endpoint));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateEndpoint($request);
        $secret = Str::random(40);
        $endpoint = $request->user()->webhookEndpoints()->create([
            ...$data,
            'secret' => $secret,
        ]);

        return $this->data($this->transform($endpoint, $secret), 201);
    }

    public function show(Request $request, WebhookEndpoint $webhook): JsonResponse
    {
        $this->owns($request, $webhook);

        return $this->data($this->transform($webhook));
    }

    public function update(Request $request, WebhookEndpoint $webhook): JsonResponse
    {
        $this->owns($request, $webhook);
        $webhook->update($this->validateEndpoint($request));

        return $this->data($this->transform($webhook));
    }

    public function destroy(Request $request, WebhookEndpoint $webhook): JsonResponse
    {
        $this->owns($request, $webhook);
        $webhook->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function deliveries(Request $request, WebhookEndpoint $webhook): JsonResponse
    {
        $this->owns($request, $webhook);
        $query = $webhook->deliveries()->latest();

        return $this->page($query->paginate($this->perPage($request)), fn (WebhookDelivery $delivery) => $this->delivery($delivery));
    }

    public function retry(Request $request, WebhookDelivery $delivery, WebhookDispatcher $dispatcher): JsonResponse
    {
        $delivery->load('endpoint');
        $this->owns($request, $delivery->endpoint);

        if (! $delivery->canRetry()) {
            return response()->json(['message' => 'This delivery cannot be retried.'], 422);
        }

        return $this->data($this->delivery($dispatcher->attempt($delivery)));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEndpoint(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! WebhookUrl::isAllowed($value)) {
                    $fail('Use a public web address. Private and loopback addresses are refused.');
                }
            }],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', Rule::in([...WebhookDispatcher::EVENTS, '*'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $companyEvents = ['company.created', 'company.updated'];
        $needsCompany = collect($data['events'])->contains(fn ($event) => $event === '*' || ! in_array($event, $companyEvents, true));

        if ($needsCompany && empty($data['company_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'company_id' => 'Choose the company this endpoint may receive.',
            ]);
        }

        return $data;
    }

    private function owns(Request $request, WebhookEndpoint $endpoint): void
    {
        abort_unless($endpoint->user_id === $request->user()->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(WebhookEndpoint $endpoint, ?string $secret = null): array
    {
        $payload = [
            'id' => $endpoint->id,
            'name' => $endpoint->name,
            'url' => $endpoint->url,
            'events' => $endpoint->events,
            'is_active' => $endpoint->is_active,
            'created_at' => $endpoint->created_at?->toIso8601String(),
            'updated_at' => $endpoint->updated_at?->toIso8601String(),
        ];

        if ($secret !== null) {
            $payload['secret'] = $secret;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function delivery(WebhookDelivery $delivery): array
    {
        return [
            'id' => $delivery->id,
            'webhook_endpoint_id' => $delivery->webhook_endpoint_id,
            'event' => $delivery->event,
            'status' => $delivery->status,
            'attempts' => $delivery->attempts,
            'response_status' => $delivery->response_status,
            'last_error' => $delivery->last_error,
            'next_attempt_at' => $delivery->next_attempt_at?->toIso8601String(),
            'delivered_at' => $delivery->delivered_at?->toIso8601String(),
            'created_at' => $delivery->created_at?->toIso8601String(),
        ];
    }
}
