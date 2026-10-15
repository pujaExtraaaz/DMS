<?php

namespace App\Console\Commands;

use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Crm\Models\Lead;
use App\Domains\Deal\Models\ExpenseType;
use App\Domains\Delivery\Models\Delivery;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Inventory\Models\StockValuationSetting;
use App\Domains\Logistics\Models\LoadSheet;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Purchasing\Models\LandedCost;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Settlement\Models\Settlement;
use App\Domains\Sync\Handlers\CompanyBankSync;
use App\Domains\Sync\Handlers\StockOpeningSync;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncGuard;
use App\Domains\Sync\Support\SyncRegistry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Ensures DMS company is linked to Books, then pushes all operational data across.
 */
class LinkAllAccounting extends Command
{
    protected $signature = 'accounting:link-all {--company= : DMS company id (defaults to Avit Digital / default)}';

    protected $description = 'Link DMS company bank + masters + invoices + stock + payments + cheques into Accounting Books';

    public function handle(): int
    {
        $company = $this->resolveCompany();
        if (! $company) {
            $this->error('No DMS company found.');

            return self::FAILURE;
        }

        $this->info("Linking DMS company #{$company->id} ({$company->name}) → Books…");

        if (! BooksCompany::forOrganization($company->id)) {
            $this->info('Provisioning Books company…');
            Artisan::call('accounting:provision-company', ['company' => $company->id]);
            $this->line(Artisan::output());
        }

        $books = BooksCompany::forOrganization($company->id);
        if (! $books) {
            $this->error('Books company still missing after provision.');

            return self::FAILURE;
        }

        $this->info("Books company #{$books->id} ({$books->name})");

        app(CompanyBankSync::class)->push($company);
        $this->line('✓ Company bank details synced');

        $copied = 0;
        $failed = 0;

        SyncGuard::run(function () use ($company, &$copied, &$failed) {
            $steps = [
                Branch::class,
                FinancialYear::class,
                BusinessGroup::class,
                Brand::class,
                Category::class,
                SubCategory::class,
                Uom::class,
                Warehouse::class,
                CustomerType::class,
                Customer::class,
                Product::class,
                ExpenseType::class,
                Employee::class,
                Lead::class,
                Quotation::class,
                Order::class,
                Invoice::class,
                Payment::class,
                PurchaseOrder::class,
                PurchaseInward::class,
                FreightBill::class,
                LandedCost::class,
                PurchaseInvoice::class,
                CreditNote::class,
                StockAdjustment::class,
                StockTransfer::class,
                StockMovement::class,
                ProductSerial::class,
                StockValuationSetting::class,
                LoadSheet::class,
                Delivery::class,
                Settlement::class,
                OdAccount::class,
                BankAccountTransaction::class,
                Cheque::class,
            ];

            foreach ($steps as $class) {
                $label = class_basename($class);
                $this->line("→ {$label}");

                $class::query()->orderBy('id')->each(function (Model $row) use (&$copied, &$failed, $label) {
                    try {
                        SyncRegistry::handler($row)?->sync($row);
                        $copied++;
                    } catch (Throwable $e) {
                        $failed++;
                        $this->error("  {$label} #{$row->getKey()}: ".$e->getMessage());
                    }
                });
            }

            $this->line('→ Stock openings (warehouses)');
            Warehouse::query()->where('company_id', $company->id)->orderBy('id')->each(function (Warehouse $warehouse) use (&$copied, &$failed) {
                try {
                    app(StockOpeningSync::class)->pushWarehouse($warehouse);
                    $copied++;
                } catch (Throwable $e) {
                    $failed++;
                    $this->error('  Stock opening '.$warehouse->code.': '.$e->getMessage());
                }
            });
        });

        $links = DB::table('sync_entity_links')->count();
        $this->info("Done. Synced attempts={$copied}, failed={$failed}, sync_entity_links={$links}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function resolveCompany(): ?Company
    {
        $id = $this->option('company');
        if ($id) {
            return Company::query()->find($id);
        }

        return Company::query()->where('name', 'Avit Digital')->first()
            ?? Company::query()->where('is_default', true)->orderBy('id')->first()
            ?? Company::query()->orderBy('id')->first();
    }
}
