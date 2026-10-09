<?php

use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockAdjustmentItem;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sync\Handlers\BankAccountSync;
use App\Domains\Sync\Handlers\BankTransactionSync;
use App\Domains\Sync\Handlers\SalesOrderSync;
use App\Domains\Sync\Handlers\StockJournalSync;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Support\Facades\DB;
use Tally\Accounting\VoucherStatus;
use Tally\Models\BankAccount;
use Tally\Models\SalesOrder as BooksSalesOrder;
use Tally\Models\StockTransaction;
use Tally\Models\Voucher;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

DB::beginTransaction();

try {
    $customer = Customer::query()->whereNotNull('company_id')->first();
    $product = Product::query()->whereNotNull('base_uom_id')->first();
    $warehouse = Warehouse::query()->where('company_id', $customer->company_id)->first();
    if (! $customer || ! $product || ! $warehouse) {
        throw new RuntimeException('Missing customer, product, or warehouse for the sync test.');
    }

    $order = Order::query()->create([
        'order_no' => 'SO-SYNC-TEST',
        'customer_id' => $customer->id,
        'salesperson_id' => 1,
        'order_date' => now()->toDateString(),
        'status' => 'pending',
        'fulfilment_mode' => 'warehouse',
        'subtotal' => 50,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 50,
    ]);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'uom_id' => $product->base_uom_id,
        'quantity' => 2,
        'unit_price' => 25,
        'line_total' => 50,
    ]);
    app(SalesOrderSync::class)->push($order->fresh(['customer', 'items.product']));
    $booksOrder = BooksSalesOrder::query()->find(SyncLinks::acctId(SalesOrderSync::KEY, $order));
    if (! $booksOrder || $booksOrder->lines()->count() !== 1) {
        throw new RuntimeException('Sales order did not copy to Books.');
    }
    $booksOrder->update(['narration' => 'from books']);
    app(SalesOrderSync::class)->pull($booksOrder->fresh('lines'));
    if (Order::query()->find($order->id)->notes !== 'from books') {
        throw new RuntimeException('Sales order did not copy back to DMS.');
    }
    $order->status = 'converted';
    app(SalesOrderSync::class)->remove($order);
    if (! BooksSalesOrder::query()->find($booksOrder->id)) {
        throw new RuntimeException('Converted sales order delete removed the Books order.');
    }

    $adjustment = StockAdjustment::query()->create([
        'adjustment_no' => 'ADJ-SYNC-TEST',
        'adjustment_date' => now()->toDateString(),
        'warehouse_id' => $warehouse->id,
        'reason' => 'found',
        'status' => 'posted',
        'notes' => 'sync test',
        'created_by' => 1,
    ]);
    StockAdjustmentItem::query()->create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'uom_id' => $product->base_uom_id,
        'quantity' => 3,
    ]);
    app(StockJournalSync::class)->push($adjustment->fresh(['warehouse', 'items.product']));
    $journal = StockTransaction::query()->find(SyncLinks::acctId(StockJournalSync::KEY, $adjustment));
    if (! $journal || $journal->status !== VoucherStatus::Draft || (float) $journal->lines()->value('quantity') !== 3.0) {
        throw new RuntimeException('Stock journal did not copy as a draft.');
    }
    app(StockJournalSync::class)->remove($adjustment);
    if (StockTransaction::query()->find($journal->id)) {
        throw new RuntimeException('Draft stock journal was not deleted.');
    }

    $od = OdAccount::query()->create([
        'company_id' => $customer->company_id,
        'account_number' => 'SYNC-TEST-001',
        'bank_name' => 'Sync Test Bank',
        'ifsc_code' => 'TEST0000001',
        'od_limit' => 0,
        'interest_rate' => 0,
        'interest_calculation_method' => 'simple',
        'effective_from' => now()->toDateString(),
        'status' => 'active',
    ]);
    app(BankAccountSync::class)->push($od);
    $bank = BankAccount::query()->find(SyncLinks::acctId(BankAccountSync::KEY, $od));
    if (! $bank || ! $bank->ledger?->isBank() || $bank->account_number !== 'SYNC-TEST-001') {
        throw new RuntimeException('Bank account did not copy.');
    }

    $txn = BankAccountTransaction::query()->create([
        'company_id' => $customer->company_id,
        'account_number' => $od->account_number,
        'od_account_id' => $od->id,
        'transaction_date' => now()->toDateString(),
        'transaction_no' => 'BNK-SYNC-TEST',
        'description' => 'deposit',
        'transaction_type' => 'credit',
        'debit' => 0,
        'credit' => 125,
    ]);
    app(BankTransactionSync::class)->push($txn);
    $voucher = Voucher::query()->find(SyncLinks::acctId(BankTransactionSync::KEY, $txn));
    $bankLine = $voucher?->entries()->where('ledger_id', $bank->ledger_id)->first();
    if (! $voucher || (float) $bankLine?->debit !== 125.0 || (float) $voucher->entries()->sum('debit') !== (float) $voucher->entries()->sum('credit')) {
        throw new RuntimeException('Bank deposit did not become a balanced voucher with money in on the bank ledger.');
    }
    app(BankTransactionSync::class)->remove($txn);
    if (Voucher::query()->find($voucher->id)) {
        throw new RuntimeException('Draft bank voucher was not deleted.');
    }
    app(BankAccountSync::class)->remove($od);
    if (BankAccount::query()->find($bank->id)) {
        throw new RuntimeException('Bank account with no vouchers was not deleted.');
    }

    echo "ok\n";
} catch (Throwable $exception) {
    echo $exception::class.': '.$exception->getMessage()."\n".$exception->getFile().':'.$exception->getLine()."\n";
} finally {
    DB::rollBack();
}
