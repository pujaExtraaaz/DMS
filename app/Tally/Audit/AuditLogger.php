<?php

namespace Tally\Audit;

use Tally\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditLogger
{
    /** @var list<string> */
    private const SECRET_KEYS = [
        'password', 'password_confirmation', 'current_password', 'remember_token',
        'token', 'secret', 'api_token', 'plain_text', 'authorization', 'webhook_secret',
        'credentials', 'api_key', 'secret_key',
    ];

    /**
     * @param  array<string, mixed>|null  $previous
     * @param  array<string, mixed>|null  $new
     */
    public function record(
        string $action,
        string $module,
        ?Model $model = null,
        ?string $description = null,
        ?array $previous = null,
        ?array $new = null,
    ): void {
        if (! Schema::hasTable('acct_audit_logs')) {
            return;
        }

        $request = request();
        $companyId = $this->idFrom($model, 'company_id');
        $branchId = $this->idFrom($model, 'branch_id');
        $yearId = $this->idFrom($model, 'financial_year_id');

        if ($model instanceof \Tally\Models\Company) {
            $companyId = $model->getKey();
        }

        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'auditable_type' => $model?->getMorphClass(),
            'auditable_id' => $model?->getKey(),
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'financial_year_id' => $yearId,
            'description' => $description ? Str::limit($description, 500, '') : null,
            'previous_values' => $this->clean($previous),
            'new_values' => $this->clean($new),
            'ip' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 255, ''),
            'source' => $this->source(),
        ]);
    }

    public function security(string $action, string $description): void
    {
        $this->record($action, 'security', null, $description);
    }

    public function blocked(Model $model, string $reason): void
    {
        $this->record('delete_blocked', $this->module($model), $model, $reason);
    }

    public function deny(Model $model, string $reason): RedirectResponse
    {
        $this->blocked($model, $reason);

        return back()->with('error', $reason);
    }

    public function denyJson(Model $model, string $reason, int $status = 422): JsonResponse
    {
        $this->blocked($model, $reason);

        return response()->json(['message' => $reason], $status);
    }

    public function module(Model $model): string
    {
        return app(AuditedModels::class)->moduleFor($model) ?? 'records';
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    public function clean(?array $values): ?array
    {
        if ($values === null || $values === []) {
            return null;
        }

        $clean = [];

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::SECRET_KEYS, true)) {
                continue;
            }

            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            }

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }

            if (is_array($value)) {
                $value = $this->clean($value);
            }

            $clean[$key] = $value;
        }

        return $clean === [] ? null : $clean;
    }

    private function source(): string
    {
        $request = request();

        if ($request?->is('api/*')) {
            return 'api';
        }

        return app()->runningInConsole() ? 'console' : 'web';
    }

    private function idFrom(?Model $model, string $attribute): ?int
    {
        if (! $model || ! array_key_exists($attribute, $model->getAttributes())) {
            return null;
        }

        $value = $model->getAttribute($attribute);

        return $value === null ? null : (int) $value;
    }
}
