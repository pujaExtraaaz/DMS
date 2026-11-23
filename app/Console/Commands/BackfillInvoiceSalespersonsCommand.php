<?php

namespace App\Console\Commands;

use App\Domains\Master\Models\Customer;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Target\Services\SalesTargetCalculationService;
use App\Http\Controllers\Sales\InvoiceController;
use App\Models\User;
use Illuminate\Console\Command;

class BackfillInvoiceSalespersonsCommand extends Command
{
    protected $signature = 'invoices:backfill-salespersons
                            {--dry-run : Show how many invoices would be updated without writing}
                            {--assign-default : Assign the default salesperson to customers that have none}
                            {--salesperson= : User id (or email) to use as default salesperson}
                            {--recalculate : Recalculate sales targets after backfill}';

    protected $description = 'Backfill invoices.salesperson_id from order/customer, optionally assign a default salesperson to parties first';

    public function handle(SalesTargetCalculationService $calculationService): int
    {
        $defaultSp = $this->resolveDefaultSalesperson();

        if ($this->option('assign-default')) {
            if (! $defaultSp) {
                $this->error('No salesperson user found. Pass --salesperson=ID or ensure a user has the salesperson role.');

                return self::FAILURE;
            }

            $customersQuery = Customer::query()->whereNull('salesperson_id');
            $customerCount = (clone $customersQuery)->count();
            $this->info("Customers missing salesperson_id: {$customerCount}");

            if (! $this->option('dry-run') && $customerCount > 0) {
                $customersQuery->update(['salesperson_id' => $defaultSp->id]);
                $this->info("Assigned salesperson [{$defaultSp->name} #{$defaultSp->id}] to {$customerCount} customer(s).");
            } elseif ($this->option('dry-run')) {
                $this->info("Would assign salesperson [{$defaultSp->name} #{$defaultSp->id}] to {$customerCount} customer(s).");
            }
        }

        $query = Invoice::query()
            ->with(['order:id,salesperson_id', 'customer:id,salesperson_id'])
            ->whereNull('salesperson_id');

        $total = (clone $query)->count();
        $this->info("Invoices missing salesperson_id: {$total}");

        if ($total === 0) {
            if ($this->option('recalculate') && ! $this->option('dry-run')) {
                $count = $calculationService->recalculateAll(true);
                $this->info("Recalculated {$count} sales targets.");
            }

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        $query->orderBy('id')->chunkById(200, function ($invoices) use (&$updated, &$skipped) {
            foreach ($invoices as $invoice) {
                $salespersonId = $invoice->order?->salesperson_id
                    ?: $invoice->customer?->salesperson_id;

                if (! $salespersonId) {
                    $skipped++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $updated++;
                    continue;
                }

                $invoice->forceFill(['salesperson_id' => (int) $salespersonId])->saveQuietly();
                $updated++;
            }
        });

        $this->info(($this->option('dry-run') ? 'Would update' : 'Updated').": {$updated}; skipped (no source SP): {$skipped}");

        if (! $this->option('dry-run') && $this->option('recalculate') && $updated > 0) {
            $count = $calculationService->recalculateAll(true);
            $this->info("Recalculated {$count} sales targets.");
        }

        return self::SUCCESS;
    }

    protected function resolveDefaultSalesperson(): ?User
    {
        $raw = $this->option('salesperson');
        if (filled($raw)) {
            if (is_numeric($raw)) {
                return User::query()->find((int) $raw);
            }

            return User::query()->where('email', $raw)->orWhere('name', $raw)->first();
        }

        return InvoiceController::getSalespersons()->first();
    }
}
