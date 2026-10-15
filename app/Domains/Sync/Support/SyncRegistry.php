<?php

namespace App\Domains\Sync\Support;

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
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Inventory\Models\StockValuationSetting;
use App\Domains\Logistics\Models\LoadSheet;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
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
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Purchasing\Models\LandedCost;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Settlement\Models\Settlement;
use App\Domains\Sync\Handlers\BankAccountSync;
use App\Domains\Sync\Handlers\BankTransactionSync;
use App\Domains\Sync\Handlers\BranchSync;
use App\Domains\Sync\Handlers\BrandSync;
use App\Domains\Sync\Handlers\BusinessGroupSync;
use App\Domains\Sync\Handlers\CategorySync;
use App\Domains\Sync\Handlers\ChequeSync;
use App\Domains\Sync\Handlers\CompanySync;
use App\Domains\Sync\Handlers\CreditNoteSync;
use App\Domains\Sync\Handlers\CustomerSync;
use App\Domains\Sync\Handlers\DeliverySync;
use App\Domains\Sync\Handlers\EmployeeSync;
use App\Domains\Sync\Handlers\ExpenseTypeSync;
use App\Domains\Sync\Handlers\FinancialYearSync;
use App\Domains\Sync\Handlers\FreightBillSync;
use App\Domains\Sync\Handlers\GoodsReceiptSync;
use App\Domains\Sync\Handlers\InvoiceSync;
use App\Domains\Sync\Handlers\LandedCostSync;
use App\Domains\Sync\Handlers\LeadSync;
use App\Domains\Sync\Handlers\LoadSheetSync;
use App\Domains\Sync\Handlers\OutstandingLedgerSync;
use App\Domains\Sync\Handlers\PaymentSync;
use App\Domains\Sync\Handlers\PriceListSync;
use App\Domains\Sync\Handlers\ProductSerialSync;
use App\Domains\Sync\Handlers\ProductSync;
use App\Domains\Sync\Handlers\PurchaseInvoiceSync;
use App\Domains\Sync\Handlers\PurchaseOrderSync;
use App\Domains\Sync\Handlers\QuotationSync;
use App\Domains\Sync\Handlers\SalesOrderSync;
use App\Domains\Sync\Handlers\SettlementSync;
use App\Domains\Sync\Handlers\StockJournalSync;
use App\Domains\Sync\Handlers\StockLevelSync;
use App\Domains\Sync\Handlers\StockMovementSync;
use App\Domains\Sync\Handlers\StockTransferSync;
use App\Domains\Sync\Handlers\StockValuationSettingSync;
use App\Domains\Sync\Handlers\SubCategorySync;
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
            Company::class => CompanySync::class,
            Branch::class => BranchSync::class,
            FinancialYear::class => FinancialYearSync::class,
            BusinessGroup::class => BusinessGroupSync::class,
            Brand::class => BrandSync::class,
            Category::class => CategorySync::class,
            SubCategory::class => SubCategorySync::class,
            Product::class, BooksProduct::class => ProductSync::class,
            Uom::class, Unit::class => UomSync::class,
            Customer::class, Party::class => CustomerSync::class,
            CustomerType::class, PriceMaster::class => PriceListSync::class,
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
            PurchaseInward::class => GoodsReceiptSync::class,
            FreightBill::class => FreightBillSync::class,
            LandedCost::class => LandedCostSync::class,
            Order::class, BooksSalesOrder::class => SalesOrderSync::class,
            Quotation::class => QuotationSync::class,
            StockAdjustment::class => StockJournalSync::class,
            StockLevel::class => StockLevelSync::class,
            StockMovement::class => StockMovementSync::class,
            StockTransfer::class => StockTransferSync::class,
            ProductSerial::class => ProductSerialSync::class,
            StockValuationSetting::class => StockValuationSettingSync::class,
            StockTransaction::class => match ($model->type) {
                StockTransactionType::Adjustment => StockJournalSync::class,
                default => null,
            },
            OdAccount::class, BankAccount::class => BankAccountSync::class,
            BankAccountTransaction::class => BankTransactionSync::class,
            Cheque::class => ChequeSync::class,
            OutstandingLedger::class => OutstandingLedgerSync::class,
            LoadSheet::class => LoadSheetSync::class,
            Delivery::class => DeliverySync::class,
            Settlement::class => SettlementSync::class,
            Employee::class => EmployeeSync::class,
            ExpenseType::class => ExpenseTypeSync::class,
            Lead::class => LeadSync::class,
            default => null,
        };
    }

    public static function entityKey(Model $model): string
    {
        return match ($model::class) {
            Company::class => CompanySync::KEY,
            Branch::class => BranchSync::KEY,
            FinancialYear::class => FinancialYearSync::KEY,
            BusinessGroup::class => BusinessGroupSync::KEY,
            Brand::class => BrandSync::KEY,
            Category::class => CategorySync::KEY,
            SubCategory::class => SubCategorySync::KEY,
            Product::class, BooksProduct::class => ProductSync::KEY,
            Uom::class, Unit::class => 'uom',
            Customer::class, Party::class => CustomerSync::KEY,
            CustomerType::class, PriceMaster::class => PriceListSync::KEY,
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
            PurchaseInward::class => GoodsReceiptSync::KEY,
            FreightBill::class => FreightBillSync::KEY,
            LandedCost::class => LandedCostSync::KEY,
            Order::class, BooksSalesOrder::class => SalesOrderSync::KEY,
            Quotation::class => QuotationSync::KEY,
            StockAdjustment::class, StockTransaction::class => StockJournalSync::KEY,
            StockLevel::class => StockLevelSync::KEY,
            StockMovement::class => StockMovementSync::KEY,
            StockTransfer::class => StockTransferSync::KEY,
            ProductSerial::class => ProductSerialSync::KEY,
            StockValuationSetting::class => StockValuationSettingSync::KEY,
            OdAccount::class, BankAccount::class => BankAccountSync::KEY,
            BankAccountTransaction::class => BankTransactionSync::KEY,
            Cheque::class => ChequeSync::KEY,
            OutstandingLedger::class => OutstandingLedgerSync::KEY,
            LoadSheet::class => LoadSheetSync::KEY,
            Delivery::class => DeliverySync::KEY,
            Settlement::class => SettlementSync::KEY,
            Employee::class => EmployeeSync::KEY,
            ExpenseType::class => ExpenseTypeSync::KEY,
            Lead::class => LeadSync::KEY,
            default => 'sync',
        };
    }
}
