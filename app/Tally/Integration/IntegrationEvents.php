<?php

namespace Tally\Integration;

use Tally\Accounting\Events\VoucherWritten;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Tally\Models\Product;
use Tally\Models\StockMovement;
use Tally\Models\Voucher;
use Illuminate\Support\Facades\Event;

class IntegrationEvents
{
    public function __construct(private readonly WebhookDispatcher $webhooks) {}

    public function register(): void
    {
        Company::created(fn (Company $company) => $this->webhooks->record('company.created', $this->company($company)));
        Company::updated(fn (Company $company) => $this->webhooks->record('company.updated', $this->company($company)));

        Ledger::created(fn (Ledger $ledger) => $this->ledger($ledger, 'created'));
        Ledger::updated(fn (Ledger $ledger) => $this->ledger($ledger, 'updated'));

        Product::created(fn (Product $product) => $this->webhooks->record('product.created', $this->product($product)));
        Product::updated(fn (Product $product) => $this->webhooks->record('product.updated', $this->product($product)));

        StockMovement::created(fn (StockMovement $movement) => $this->webhooks->record('stock_movement.created', [
            'id' => $movement->id,
            'company_id' => $movement->company_id,
            'product_id' => $movement->product_id,
            'godown_id' => $movement->godown_id,
            'quantity' => (string) $movement->quantity,
            'movement_type' => $movement->movement_type->value,
            'movement_date' => $movement->movement_date?->toDateString(),
        ]));

        Event::listen(VoucherWritten::class, function (VoucherWritten $event): void {
            $voucher = $event->voucher;
            $name = match ($voucher->status) {
                VoucherStatus::Posted => 'voucher.posted',
                VoucherStatus::Cancelled => 'voucher.cancelled',
                default => 'voucher.created',
            };

            $this->webhooks->record($name, $this->voucher($voucher));

            if ($voucher->status === VoucherStatus::Posted && $voucher->voucher_type === VoucherType::Payment) {
                $this->webhooks->record('payment.posted', $this->voucher($voucher));
            }

            if ($voucher->status === VoucherStatus::Posted && $voucher->voucher_type === VoucherType::Receipt) {
                $this->webhooks->record('receipt.posted', $this->voucher($voucher));
            }
        });
    }

    private function ledger(Ledger $ledger, string $action): void
    {
        $payload = [
            'id' => $ledger->id,
            'company_id' => $ledger->company_id,
            'name' => $ledger->name,
            'account_group_id' => $ledger->account_group_id,
        ];

        $this->webhooks->record('ledger.'.$action, $payload);
        $ledger->loadMissing('accountGroup.parent');

        if ($ledger->isCustomer() || $ledger->isSupplier()) {
            $payload['role'] = $ledger->isCustomer() ? 'customer' : 'supplier';
            $this->webhooks->record('party.'.$action, $payload);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function company(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'gstin' => $company->gstin,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function product(Product $product): array
    {
        return [
            'id' => $product->id,
            'company_id' => $product->company_id,
            'name' => $product->name,
            'code' => $product->code,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function voucher(Voucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'company_id' => $voucher->company_id,
            'branch_id' => $voucher->branch_id,
            'financial_year_id' => $voucher->financial_year_id,
            'voucher_type' => $voucher->voucher_type->value,
            'voucher_number' => $voucher->voucher_number,
            'status' => $voucher->status->value,
        ];
    }
}
