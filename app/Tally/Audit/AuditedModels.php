<?php

namespace Tally\Audit;

use Tally\Models\AccountGroup;
use Tally\Models\BillOfMaterial;
use Tally\Models\Branch;
use Tally\Models\Budget;
use Tally\Models\Company;
use Tally\Models\CostCategory;
use Tally\Models\CostCentre;
use Tally\Models\DeductionSection;
use Tally\Models\FinancialYear;
use Tally\Models\Godown;
use Tally\Models\HsnSac;
use Tally\Models\Invoice;
use Tally\Models\Ledger;
use Tally\Models\ManufacturingOrder;
use Tally\Models\Product;
use Tally\Models\ProductGroup;
use Tally\Models\StockTransaction;
use Tally\Models\TaxAccount;
use Tally\Models\TaxCategory;
use Tally\Models\TaxRate;
use Tally\Models\Unit;
use App\Models\User;
use Tally\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AuditedModels
{
    public function register(): void
    {
        foreach ($this->map() as $class => $module) {
            $class::created(function (Model $model) use ($module) {
                $this->whenReady(fn () => $this->created($module, $model));
            });
            $class::updated(function (Model $model) use ($module) {
                $this->whenReady(fn () => $this->updated($module, $model));
            });
            $class::deleted(function (Model $model) use ($module) {
                $this->whenReady(fn () => $this->deleted($module, $model));
            });
        }
    }

    private function whenReady(\Closure $callback): void
    {
        if (Schema::hasTable('acct_audit_logs')) {
            $callback();
        }
    }

    public function moduleFor(Model $model): ?string
    {
        return $this->map()[$model::class] ?? null;
    }

    /**
     * @return array<class-string<Model>, string>
     */
    public function map(): array
    {
        return [
            Company::class => 'company',
            Branch::class => 'branch',
            FinancialYear::class => 'financial-year',
            AccountGroup::class => 'accounts',
            Ledger::class => 'accounts',
            ProductGroup::class => 'inventory',
            Product::class => 'inventory',
            Unit::class => 'inventory',
            Godown::class => 'inventory',
            TaxCategory::class => 'tax',
            TaxRate::class => 'tax',
            HsnSac::class => 'tax',
            TaxAccount::class => 'tax',
            CostCategory::class => 'accounts',
            CostCentre::class => 'accounts',
            Budget::class => 'accounts',
            DeductionSection::class => 'tax',
            BillOfMaterial::class => 'manufacturing',
            ManufacturingOrder::class => 'manufacturing',
            Voucher::class => 'accounting',
            Invoice::class => 'accounting',
            StockTransaction::class => 'inventory',
            User::class => 'security',
        ];
    }

    private function created(string $module, Model $model): void
    {
        app(AuditLogger::class)->record('created', $module, $model, $this->label($model).' created.', null, $this->attributes($model));
    }

    private function updated(string $module, Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $previous = [];

        foreach (array_keys($changes) as $key) {
            $previous[$key] = $model->getOriginal($key);
        }

        $logger = app(AuditLogger::class);

        if ($model instanceof User && array_key_exists('password', $changes)) {
            $logger->record('password_changed', 'security', $model, 'Password changed.');

            return;
        }

        $action = 'updated';
        $status = $changes['status'] ?? null;
        $status = $status instanceof \BackedEnum ? $status->value : $status;

        if ($status === 'posted') {
            $action = 'posted';
        } elseif ($status === 'cancelled') {
            $action = 'cancelled';
        } elseif (array_key_exists('is_active', $changes)) {
            $action = $model->is_active ? 'activated' : 'deactivated';
        }

        $logger->record($action, $module, $model, $this->label($model).' '.$action.'.', $previous, $changes);
    }

    private function deleted(string $module, Model $model): void
    {
        app(AuditLogger::class)->record('deleted', $module, $model, $this->label($model).' deleted.', $this->attributes($model), null);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(Model $model): array
    {
        return $model->attributesToArray();
    }

    private function label(Model $model): string
    {
        foreach (['name', 'voucher_number', 'invoice_number', 'number', 'email', 'code'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return class_basename($model).' '.$value;
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }
}
