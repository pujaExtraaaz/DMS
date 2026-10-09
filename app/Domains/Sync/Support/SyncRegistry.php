<?php

namespace App\Domains\Sync\Support;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Order\Models\Order;
use App\Domains\Sync\Handlers\BankAccountSync;
use App\Domains\Sync\Handlers\BankTransactionSync;
use App\Domains\Sync\Handlers\CreditNoteSync;
use App\Domains\Sync\Handlers\CustomerSync;
use App\Domains\Sync\Handlers\InvoiceSync;
use App\Domains\Sync\Handlers\PaymentSync;
use App\Domains\Sync\Handlers\ProductSync;
use App\Domains\Sync\Handlers\PurchaseInvoiceSync;
use App\Domains\Sync\Handlers\PurchaseOrderSync;
use App\Domains\Sync\Handlers\SalesOrderSync;
use App\Domains\Sync\Handlers\StockJournalSync;
use App\Domains\Sync\Handlers\UomSync;
use App\Domains\Sync\Handlers\WarehouseSync;
use Illuminate\Database\Eloquent\Model;
use Tally\Inventory\StockTransactionType;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\BankAccount;
use Tally\Models\Godown;
use Tally\Models\Invoice as BooksInvoice;
use Tally\Models\Party;
use Tally\Models\Product as BooksProduct;
use Tally\Models\PurchaseOrder as BooksPurchaseOrder;
use Tally\Models\SalesOrder as BooksSalesOrder;
use Tally\Models\StockTransaction;
use Tally\Models\Unit;
use Tally\Models\Voucher;

final class SyncRegistry
{
    public static function handler(Model $model): ?object
    {
        $class = self::handlerClass($model);

        return $class ? app($class) : null;
    }

    public static function handlerClass(Model $model): ?string
    {
        return match ($model::class) {
            Product::class, BooksProduct::class => ProductSync::class,
            Uom::class, Unit::class => UomSync::class,
            Customer::class, Party::class => CustomerSync::class,
            Warehouse::class, Godown::class => WarehouseSync::class,
            Invoice::class => InvoiceSync::class,
            BooksInvoice::class => match ($model->kind) {
                InvoiceKind::Sales => InvoiceSync::class,
                InvoiceKind::Purchase => PurchaseInvoiceSync::class,
                InvoiceKind::CreditNote => CreditNoteSync::class,
                default => null,
            },
            Payment::class, Voucher::class => PaymentSync::class,
            PurchaseInvoice::class => PurchaseInvoiceSync::class,
            CreditNote::class => CreditNoteSync::class,
            PurchaseOrder::class, BooksPurchaseOrder::class => PurchaseOrderSync::class,
            Order::class, BooksSalesOrder::class => SalesOrderSync::class,
            StockAdjustment::class => StockJournalSync::class,
            StockTransaction::class => $model->type === StockTransactionType::Adjustment ? StockJournalSync::class : null,
            OdAccount::class, BankAccount::class => BankAccountSync::class,
            BankAccountTransaction::class => BankTransactionSync::class,
            default => null,
        };
    }

    public static function entityKey(Model $model): string
    {
        return match ($model::class) {
            Product::class, BooksProduct::class => ProductSync::KEY,
            Uom::class, Unit::class => 'uom',
            Customer::class, Party::class => CustomerSync::KEY,
            Warehouse::class, Godown::class => WarehouseSync::KEY,
            Invoice::class => InvoiceSync::KEY,
            BooksInvoice::class => match ($model->kind) {
                InvoiceKind::Purchase => PurchaseInvoiceSync::KEY,
                InvoiceKind::CreditNote => CreditNoteSync::KEY,
                default => InvoiceSync::KEY,
            },
            Payment::class, Voucher::class => PaymentSync::KEY,
            PurchaseInvoice::class => PurchaseInvoiceSync::KEY,
            CreditNote::class => CreditNoteSync::KEY,
            PurchaseOrder::class, BooksPurchaseOrder::class => PurchaseOrderSync::KEY,
            Order::class, BooksSalesOrder::class => SalesOrderSync::KEY,
            StockAdjustment::class, StockTransaction::class => StockJournalSync::KEY,
            OdAccount::class, BankAccount::class => BankAccountSync::KEY,
            BankAccountTransaction::class => BankTransactionSync::KEY,
            default => 'sync',
        };
    }
}
