<?php

use Illuminate\Support\Facades\Route;
use Tally\Http\Controllers\AccountingReportController;
use Tally\Http\Controllers\AuditController;
use Tally\Http\Controllers\BarcodeController;
use Tally\Http\Controllers\ParityMasterController;
use Tally\Http\Controllers\ParityReportController;
use Tally\Http\Controllers\PartyController;
use Tally\Http\Controllers\PayrollController;
use Tally\Http\Controllers\PreferenceController;
use Tally\Http\Controllers\RoleController;
use Tally\Http\Controllers\UserController;
use Tally\Http\Controllers\AdvancedInventoryController;
use Tally\Http\Controllers\BillOfMaterialController;
use Tally\Http\Controllers\ManufacturingController;
use Tally\Http\Controllers\AccountGroupController;
use Tally\Http\Controllers\BankController;
use Tally\Http\Controllers\BankStatementController;
use Tally\Http\Controllers\BranchController;
use Tally\Http\Controllers\CostCategoryController;
use Tally\Http\Controllers\CostCentreController;
use Tally\Http\Controllers\CompanyController;
use Tally\Http\Controllers\CompanyFeaturesController;
use Tally\Http\Controllers\DeductionSectionController;
use Tally\Http\Controllers\DashboardController;
use Tally\Http\Controllers\ShellSearchController;
use Tally\Http\Controllers\FinancialYearController;
use Tally\Http\Controllers\BudgetController;
use Tally\Http\Controllers\BackupController;
use Tally\Http\Controllers\ExportController;
use Tally\Http\Controllers\GodownController;
use Tally\Http\Controllers\ImportController;
use Tally\Http\Controllers\HsnCatalogueController;
use Tally\Http\Controllers\HsnSacController;
use Tally\Http\Controllers\PriceListController;
use Tally\Http\Controllers\InvoiceController;
use Tally\Http\Controllers\PurchaseOrderController;
use Tally\Http\Controllers\SalesOrderController;
use Tally\Http\Controllers\KeyboardShortcutController;
use Tally\Http\Controllers\InventoryReportController;
use Tally\Http\Controllers\LedgerController;
use Tally\Http\Controllers\ProductController;
use Tally\Http\Controllers\ProductGroupController;
use Tally\Http\Controllers\StockMovementController;
use Tally\Http\Controllers\StockTransactionController;
use Tally\Http\Controllers\TaxAccountController;
use Tally\Http\Controllers\TaxCategoryController;
use Tally\Http\Controllers\TaxRateController;
use Tally\Http\Controllers\UnitController;
use Tally\Http\Controllers\ProfileController;
use Tally\Http\Controllers\VoucherClassController;
use Tally\Http\Controllers\VoucherController;
use Tally\Http\Controllers\Shell\PlaceholderController;
use Tally\Http\Controllers\WorkspaceContextController;
use Tally\Support\Shell\Navigation;

Route::middleware(['auth', 'tally.workspace'])->prefix('books/tally')->name('books.tally.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/sync/resolve', \App\Domains\Sync\Http\SyncConflictController::class)->name('sync.resolve');
    Route::get('/masters/menu', function () {
        return \Tally\Support\Shell\TallyPanel::catalogue('List of Masters', 'masters.menu', 'masters');
    })->name('masters.menu');
    Route::get('/masters/create-menu', function () {
        return \Tally\Support\Shell\TallyPanel::catalogue('Master Creation', 'masters.create-menu', 'masters', 'create');
    })->name('masters.create-menu');
    Route::get('/reports/menu', function () {
        return \Tally\Support\Shell\TallyPanel::catalogue('Display More Reports', 'reports.menu', 'reports');
    })->name('reports.menu');
    Route::get('/search/records', [ShellSearchController::class, 'records'])->name('search.records');
    Route::get('/masters/gstin', \Tally\Http\Controllers\GstinController::class)->name('gstin.lookup');
    Route::get('/masters/hsn-catalogue', [HsnCatalogueController::class, 'index'])->name('hsn-catalogue.index');
    Route::post('/masters/hsn-catalogue', [HsnCatalogueController::class, 'store'])->name('hsn-catalogue.store');

    Route::post('/context/company', [WorkspaceContextController::class, 'updateCompany'])->name('context.company');
    Route::post('/context/branch', [WorkspaceContextController::class, 'updateBranch'])->name('context.branch');
    Route::post('/context/financial-year', [WorkspaceContextController::class, 'updateFinancialYear'])->name('context.financial-year');
    Route::post('/context/date', [WorkspaceContextController::class, 'updateDate'])->name('context.date');
    Route::post('/context/period', [WorkspaceContextController::class, 'updatePeriod'])->name('context.period');

    Route::get('/branches', [BranchController::class, 'current'])->name('branches.current');
    Route::get('/financial-years', [FinancialYearController::class, 'current'])->name('financial-years.current');

    Route::get('/company/features', [CompanyFeaturesController::class, 'edit'])->name('companies.features');
    Route::put('/company/features', [CompanyFeaturesController::class, 'update'])->name('companies.features.update');
    Route::patch('/companies/{company}/activation', [CompanyController::class, 'updateActivation'])->name('companies.activation');
    Route::resource('companies', CompanyController::class);

    Route::scopeBindings()->group(function () {
        Route::patch('/companies/{company}/branches/{branch}/activation', [BranchController::class, 'updateActivation'])
            ->name('companies.branches.activation');
        Route::patch('/companies/{company}/financial-years/{financialYear}/activation', [FinancialYearController::class, 'updateActivation'])
            ->name('companies.financial-years.activation');

        Route::resource('companies.branches', BranchController::class);
        Route::resource('companies.financial-years', FinancialYearController::class);
    });

    Route::post('/masters/groups/standard', [AccountGroupController::class, 'prepareStandard'])->name('account-groups.standard');
    Route::get('/masters/groups', [AccountGroupController::class, 'index'])->name('account-groups.index');
    Route::get('/masters/groups/create', [AccountGroupController::class, 'create'])->name('account-groups.create');
    Route::post('/masters/groups', [AccountGroupController::class, 'store'])->name('account-groups.store');
    Route::get('/masters/groups/{accountGroup}', [AccountGroupController::class, 'show'])->name('account-groups.show');
    Route::get('/masters/groups/{accountGroup}/edit', [AccountGroupController::class, 'edit'])->name('account-groups.edit');
    Route::put('/masters/groups/{accountGroup}', [AccountGroupController::class, 'update'])->name('account-groups.update');
    Route::patch('/masters/groups/{accountGroup}/activation', [AccountGroupController::class, 'updateActivation'])->name('account-groups.activation');
    Route::delete('/masters/groups/{accountGroup}', [AccountGroupController::class, 'destroy'])->name('account-groups.destroy');

    Route::get('/masters/ledgers', [LedgerController::class, 'index'])->name('ledgers.index');
    Route::get('/masters/ledgers/create', [LedgerController::class, 'create'])->name('ledgers.create');
    Route::post('/masters/ledgers', [LedgerController::class, 'store'])->name('ledgers.store');
    Route::get('/masters/ledgers/{ledger}', [LedgerController::class, 'show'])->name('ledgers.show');
    Route::get('/masters/ledgers/{ledger}/edit', [LedgerController::class, 'edit'])->name('ledgers.edit');
    Route::put('/masters/ledgers/{ledger}', [LedgerController::class, 'update'])->name('ledgers.update');
    Route::patch('/masters/ledgers/{ledger}/activation', [LedgerController::class, 'updateActivation'])->name('ledgers.activation');
    Route::delete('/masters/ledgers/{ledger}', [LedgerController::class, 'destroy'])->name('ledgers.destroy');

    Route::get('/masters/units', [UnitController::class, 'index'])->name('units.index');
    Route::get('/masters/units/create', [UnitController::class, 'create'])->name('units.create');
    Route::post('/masters/units', [UnitController::class, 'store'])->name('units.store');
    Route::get('/masters/units/{unit}', [UnitController::class, 'show'])->name('units.show');
    Route::get('/masters/units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
    Route::put('/masters/units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::patch('/masters/units/{unit}/activation', [UnitController::class, 'updateActivation'])->name('units.activation');
    Route::delete('/masters/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

    Route::get('/masters/product-groups', [ProductGroupController::class, 'index'])->name('product-groups.index');
    Route::get('/masters/product-groups/create', [ProductGroupController::class, 'create'])->name('product-groups.create');
    Route::post('/masters/product-groups', [ProductGroupController::class, 'store'])->name('product-groups.store');
    Route::get('/masters/product-groups/{productGroup}', [ProductGroupController::class, 'show'])->name('product-groups.show');
    Route::get('/masters/product-groups/{productGroup}/edit', [ProductGroupController::class, 'edit'])->name('product-groups.edit');
    Route::put('/masters/product-groups/{productGroup}', [ProductGroupController::class, 'update'])->name('product-groups.update');
    Route::patch('/masters/product-groups/{productGroup}/activation', [ProductGroupController::class, 'updateActivation'])->name('product-groups.activation');
    Route::delete('/masters/product-groups/{productGroup}', [ProductGroupController::class, 'destroy'])->name('product-groups.destroy');

    Route::get('/masters/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/masters/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/masters/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/masters/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/masters/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/masters/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::patch('/masters/products/{product}/activation', [ProductController::class, 'updateActivation'])->name('products.activation');
    Route::delete('/masters/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/masters/godowns', [GodownController::class, 'index'])->name('godowns.index');
    Route::get('/masters/godowns/create', [GodownController::class, 'create'])->name('godowns.create');
    Route::post('/masters/godowns', [GodownController::class, 'store'])->name('godowns.store');
    Route::get('/masters/godowns/{godown}', [GodownController::class, 'show'])->name('godowns.show');
    Route::get('/masters/godowns/{godown}/edit', [GodownController::class, 'edit'])->name('godowns.edit');
    Route::put('/masters/godowns/{godown}', [GodownController::class, 'update'])->name('godowns.update');
    Route::patch('/masters/godowns/{godown}/activation', [GodownController::class, 'updateActivation'])->name('godowns.activation');
    Route::delete('/masters/godowns/{godown}', [GodownController::class, 'destroy'])->name('godowns.destroy');

    Route::get('/masters/tax-categories', [TaxCategoryController::class, 'index'])->name('tax-categories.index');
    Route::get('/masters/tax-categories/create', [TaxCategoryController::class, 'create'])->name('tax-categories.create');
    Route::post('/masters/tax-categories', [TaxCategoryController::class, 'store'])->name('tax-categories.store');
    Route::get('/masters/tax-categories/{taxCategory}', [TaxCategoryController::class, 'show'])->name('tax-categories.show');
    Route::get('/masters/tax-categories/{taxCategory}/edit', [TaxCategoryController::class, 'edit'])->name('tax-categories.edit');
    Route::put('/masters/tax-categories/{taxCategory}', [TaxCategoryController::class, 'update'])->name('tax-categories.update');
    Route::patch('/masters/tax-categories/{taxCategory}/activation', [TaxCategoryController::class, 'updateActivation'])->name('tax-categories.activation');
    Route::delete('/masters/tax-categories/{taxCategory}', [TaxCategoryController::class, 'destroy'])->name('tax-categories.destroy');

    Route::get('/masters/tax-rates', [TaxRateController::class, 'index'])->name('tax-rates.index');
    Route::get('/masters/tax-rates/create', [TaxRateController::class, 'create'])->name('tax-rates.create');
    Route::post('/masters/tax-rates', [TaxRateController::class, 'store'])->name('tax-rates.store');
    Route::get('/masters/tax-rates/{taxRate}', [TaxRateController::class, 'show'])->name('tax-rates.show');
    Route::get('/masters/tax-rates/{taxRate}/edit', [TaxRateController::class, 'edit'])->name('tax-rates.edit');
    Route::put('/masters/tax-rates/{taxRate}', [TaxRateController::class, 'update'])->name('tax-rates.update');
    Route::patch('/masters/tax-rates/{taxRate}/activation', [TaxRateController::class, 'updateActivation'])->name('tax-rates.activation');
    Route::delete('/masters/tax-rates/{taxRate}', [TaxRateController::class, 'destroy'])->name('tax-rates.destroy');

    Route::get('/masters/hsn-sac', [HsnSacController::class, 'index'])->name('hsn-sacs.index');
    Route::get('/masters/hsn-sac/create', [HsnSacController::class, 'create'])->name('hsn-sacs.create');
    Route::post('/masters/hsn-sac', [HsnSacController::class, 'store'])->name('hsn-sacs.store');
    Route::get('/masters/hsn-sac/{hsnSac}', [HsnSacController::class, 'show'])->name('hsn-sacs.show');
    Route::get('/masters/hsn-sac/{hsnSac}/edit', [HsnSacController::class, 'edit'])->name('hsn-sacs.edit');
    Route::put('/masters/hsn-sac/{hsnSac}', [HsnSacController::class, 'update'])->name('hsn-sacs.update');
    Route::patch('/masters/hsn-sac/{hsnSac}/activation', [HsnSacController::class, 'updateActivation'])->name('hsn-sacs.activation');
    Route::delete('/masters/hsn-sac/{hsnSac}', [HsnSacController::class, 'destroy'])->name('hsn-sacs.destroy');

    Route::get('/masters/tax-accounts', [TaxAccountController::class, 'edit'])->name('tax-accounts.index');
    Route::get('/masters/tax-accounts/create', [TaxAccountController::class, 'edit'])->name('tax-accounts.create');
    Route::get('/masters/tax-accounts/alter', [TaxAccountController::class, 'edit'])->name('tax-accounts.edit');
    Route::put('/masters/tax-accounts', [TaxAccountController::class, 'update'])->name('tax-accounts.update');

    foreach ([
        'stock-in' => 'in',
        'stock-out' => 'out',
        'stock-transfers' => 'transfer',
        'stock-adjustments' => 'adjustment',
    ] as $slug => $stockType) {
        Route::get("/transactions/{$slug}", [StockTransactionController::class, 'index'])->name("stock.{$stockType}.index");
        Route::get("/transactions/{$slug}/create", [StockTransactionController::class, 'create'])->name("stock.{$stockType}.create");
        Route::post("/transactions/{$slug}", [StockTransactionController::class, 'store'])->name("stock.{$stockType}.store");
        Route::get("/transactions/{$slug}/{stockTransaction}", [StockTransactionController::class, 'show'])->name("stock.{$stockType}.show");
        Route::post("/transactions/{$slug}/{stockTransaction}/cancel", [StockTransactionController::class, 'cancel'])->name("stock.{$stockType}.cancel");
    }

    Route::get('/transactions/stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');

    foreach (['sales', 'purchase', 'credit-note', 'debit-note'] as $invoiceKind) {
        Route::get("/transactions/{$invoiceKind}", [InvoiceController::class, 'index'])->name("invoices.{$invoiceKind}.index");
        Route::get("/transactions/{$invoiceKind}/create", [InvoiceController::class, 'create'])->name("invoices.{$invoiceKind}.create");
        Route::post("/transactions/{$invoiceKind}", [InvoiceController::class, 'store'])->name("invoices.{$invoiceKind}.store");
        Route::get("/transactions/{$invoiceKind}/{invoice}", [InvoiceController::class, 'show'])->name("invoices.{$invoiceKind}.show");
        Route::get("/transactions/{$invoiceKind}/{invoice}/print", [InvoiceController::class, 'print'])->name("invoices.{$invoiceKind}.print");
        Route::get("/transactions/{$invoiceKind}/{invoice}/pdf", [InvoiceController::class, 'pdf'])->name("invoices.{$invoiceKind}.pdf");
        Route::get("/transactions/{$invoiceKind}/{invoice}/einvoice", [InvoiceController::class, 'einvoice'])->name("invoices.{$invoiceKind}.einvoice");
        Route::get("/transactions/{$invoiceKind}/{invoice}/eway", [InvoiceController::class, 'eway'])->name("invoices.{$invoiceKind}.eway");
        Route::get("/transactions/{$invoiceKind}/{invoice}/edit", [InvoiceController::class, 'edit'])->name("invoices.{$invoiceKind}.edit");
        Route::put("/transactions/{$invoiceKind}/{invoice}", [InvoiceController::class, 'update'])->name("invoices.{$invoiceKind}.update");
        Route::post("/transactions/{$invoiceKind}/{invoice}/post", [InvoiceController::class, 'post'])->name("invoices.{$invoiceKind}.post");
        Route::post("/transactions/{$invoiceKind}/{invoice}/cancel", [InvoiceController::class, 'cancel'])->name("invoices.{$invoiceKind}.cancel");
    }

    Route::get('/vouchers/classes', [VoucherClassController::class, 'index'])->name('vouchers.classes.index');
    Route::post('/vouchers/classes', [VoucherClassController::class, 'store'])->name('vouchers.classes.store');
    Route::delete('/vouchers/classes/{voucherClass}', [VoucherClassController::class, 'destroy'])->name('vouchers.classes.destroy');
    Route::get('/vouchers/other', [VoucherController::class, 'other'])->name('vouchers.other');
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
    Route::get('/vouchers/journal/create', [VoucherController::class, 'create'])->name('vouchers.journal.create');
    Route::get('/vouchers/payment/create', [VoucherController::class, 'create'])->name('vouchers.payment.create');
    Route::get('/vouchers/receipt/create', [VoucherController::class, 'create'])->name('vouchers.receipt.create');
    Route::get('/vouchers/contra/create', [VoucherController::class, 'create'])->name('vouchers.contra.create');
    Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
    Route::post('/vouchers/journal', [VoucherController::class, 'store'])->name('vouchers.journal.store');
    Route::post('/vouchers/payment', [VoucherController::class, 'store'])->name('vouchers.payment.store');
    Route::post('/vouchers/receipt', [VoucherController::class, 'store'])->name('vouchers.receipt.store');
    Route::post('/vouchers/contra', [VoucherController::class, 'store'])->name('vouchers.contra.store');
    Route::get('/vouchers/{voucher}/entry', [VoucherController::class, 'entry'])->name('vouchers.entry');
    Route::put('/vouchers/{voucher}/alter', [VoucherController::class, 'alter'])->name('vouchers.alter');
    Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
    Route::get('/vouchers/{voucher}/print', [VoucherController::class, 'print'])->name('vouchers.print');
    Route::get('/vouchers/{voucher}/pdf', [VoucherController::class, 'pdf'])->name('vouchers.pdf');
    Route::get('/vouchers/{voucher}/edit', [VoucherController::class, 'edit'])->name('vouchers.edit');
    Route::put('/vouchers/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
    Route::post('/vouchers/{voucher}/post', [VoucherController::class, 'post'])->name('vouchers.post');
    Route::post('/vouchers/{voucher}/cancel', [VoucherController::class, 'cancel'])->name('vouchers.cancel');
    Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');

    Route::get('/banking', [BankController::class, 'menu'])->name('banking.menu');
    Route::get('/banking/cheques', [\Tally\Http\Controllers\BankingInstrumentController::class, 'register'])->name('banking.cheques');
    Route::get('/banking/cheques/printing', [\Tally\Http\Controllers\BankingInstrumentController::class, 'printing'])->name('banking.cheques.printing');
    Route::get('/banking/cheques/post-dated', [\Tally\Http\Controllers\BankingInstrumentController::class, 'postDated'])->name('banking.cheques.post-dated');
    Route::post('/banking/cheques', [\Tally\Http\Controllers\BankingInstrumentController::class, 'store'])->name('banking.cheques.store');
    Route::put('/banking/cheques/{instrument}', [\Tally\Http\Controllers\BankingInstrumentController::class, 'update'])->name('banking.cheques.update');
    Route::get('/banking/cheques/{instrument}/print', [\Tally\Http\Controllers\BankingInstrumentController::class, 'print'])->name('banking.cheques.print');
    Route::get('/banking/deposit-slips', [\Tally\Http\Controllers\BankingInstrumentController::class, 'deposits'])->name('banking.deposits');
    Route::post('/banking/deposit-slips', [\Tally\Http\Controllers\BankingInstrumentController::class, 'storeDeposit'])->name('banking.deposits.store');
    Route::get('/banking/payment-advices', [\Tally\Http\Controllers\BankingInstrumentController::class, 'advices'])->name('banking.advices');
    Route::post('/banking/payment-advices', [\Tally\Http\Controllers\BankingInstrumentController::class, 'storeAdvice'])->name('banking.advices.store');
    Route::get('/banking/gateway', [\Tally\Http\Controllers\BankingInstrumentController::class, 'gateway'])->name('banking.gateway');
    Route::post('/banking/gateway', [\Tally\Http\Controllers\BankingInstrumentController::class, 'storeGateway'])->name('banking.gateway.store');
    Route::post('/banking/gateway/{settlement}/reconcile', [\Tally\Http\Controllers\BankingInstrumentController::class, 'reconcileGateway'])->name('banking.gateway.reconcile');
    Route::get('/banking/activities', [BankController::class, 'activities'])->name('banking.activities');
    Route::get('/banking/accounts', [BankController::class, 'accounts'])->name('banking.accounts');
    Route::get('/banking/transactions', [BankController::class, 'transactions'])->name('banking.transactions');
    Route::get('/banking/statements', [BankStatementController::class, 'index'])->name('bank-statements.index');
    Route::post('/banking/statements', [BankStatementController::class, 'store'])->name('bank-statements.store');
    Route::post('/banking/statements/{line}/match', [BankStatementController::class, 'match'])->name('bank-statements.match');
    Route::get('/banking/reconciliation', [BankController::class, 'reconciliation'])->name('banking.reconciliation');
    Route::post('/banking/reconciliation/{voucherEntry}', [BankController::class, 'reconcile'])->name('banking.reconcile');
    Route::delete('/banking/reconciliation/{voucherEntry}', [BankController::class, 'unreconcile'])->name('banking.unreconcile');

    Route::get('/masters/cost-categories', [CostCategoryController::class, 'index'])->name('cost-categories.index');
    Route::get('/masters/cost-categories/create', [CostCategoryController::class, 'create'])->name('cost-categories.create');
    Route::post('/masters/cost-categories', [CostCategoryController::class, 'store'])->name('cost-categories.store');
    Route::get('/masters/cost-categories/{costCategory}/edit', [CostCategoryController::class, 'edit'])->name('cost-categories.edit');
    Route::put('/masters/cost-categories/{costCategory}', [CostCategoryController::class, 'update'])->name('cost-categories.update');
    Route::patch('/masters/cost-categories/{costCategory}/activation', [CostCategoryController::class, 'updateActivation'])->name('cost-categories.activation');
    Route::delete('/masters/cost-categories/{costCategory}', [CostCategoryController::class, 'destroy'])->name('cost-categories.destroy');

    Route::get('/masters/cost-centres', [CostCentreController::class, 'index'])->name('cost-centres.index');
    Route::get('/masters/cost-centres/create', [CostCentreController::class, 'create'])->name('cost-centres.create');
    Route::post('/masters/cost-centres', [CostCentreController::class, 'store'])->name('cost-centres.store');
    Route::get('/masters/cost-centres/{costCentre}/edit', [CostCentreController::class, 'edit'])->name('cost-centres.edit');
    Route::put('/masters/cost-centres/{costCentre}', [CostCentreController::class, 'update'])->name('cost-centres.update');
    Route::patch('/masters/cost-centres/{costCentre}/activation', [CostCentreController::class, 'updateActivation'])->name('cost-centres.activation');
    Route::delete('/masters/cost-centres/{costCentre}', [CostCentreController::class, 'destroy'])->name('cost-centres.destroy');

    Route::get('/masters/budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::get('/masters/budgets/create', [BudgetController::class, 'create'])->name('budgets.create');
    Route::post('/masters/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::get('/masters/budgets/{budget}/edit', [BudgetController::class, 'edit'])->name('budgets.edit');
    Route::put('/masters/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('/masters/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    Route::get('/masters/price-lists', [PriceListController::class, 'index'])->name('price-lists.index');
    Route::get('/masters/price-lists/create', [PriceListController::class, 'create'])->name('price-lists.create');
    Route::post('/masters/price-lists', [PriceListController::class, 'store'])->name('price-lists.store');
    Route::get('/masters/price-lists/{priceList}/edit', [PriceListController::class, 'edit'])->name('price-lists.edit');
    Route::put('/masters/price-lists/{priceList}', [PriceListController::class, 'update'])->name('price-lists.update');
    Route::delete('/masters/price-lists/{priceList}', [PriceListController::class, 'destroy'])->name('price-lists.destroy');

    Route::get('/masters/deduction-sections', [DeductionSectionController::class, 'index'])->name('deduction-sections.index');
    Route::get('/masters/deduction-sections/create', [DeductionSectionController::class, 'create'])->name('deduction-sections.create');
    Route::post('/masters/deduction-sections', [DeductionSectionController::class, 'store'])->name('deduction-sections.store');
    Route::get('/masters/deduction-sections/{deductionSection}/edit', [DeductionSectionController::class, 'edit'])->name('deduction-sections.edit');
    Route::put('/masters/deduction-sections/{deductionSection}', [DeductionSectionController::class, 'update'])->name('deduction-sections.update');
    Route::patch('/masters/deduction-sections/{deductionSection}/activation', [DeductionSectionController::class, 'updateActivation'])->name('deduction-sections.activation');
    Route::delete('/masters/deduction-sections/{deductionSection}', [DeductionSectionController::class, 'destroy'])->name('deduction-sections.destroy');

    Route::get('/reports/day-book', [AccountingReportController::class, 'dayBook'])->name('reports.day-book');
    Route::get('/reports/ledger', [AccountingReportController::class, 'ledger'])->name('reports.ledger');
    Route::get('/reports/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('/reports/profit-and-loss', [AccountingReportController::class, 'profitAndLoss'])->name('reports.profit-and-loss');
    Route::get('/reports/balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('/reports/ratio-analysis', [AccountingReportController::class, 'ratioAnalysis'])->name('reports.ratio-analysis');
    Route::get('/reports/gst', [AccountingReportController::class, 'gst'])->name('reports.gst');
    Route::get('/reports/gstr-1', [ParityReportController::class, 'gstr1'])->name('reports.gstr-1');
    Route::get('/reports/gstr-1.json', [ParityReportController::class, 'gstr1File'])->name('reports.gstr-1.file');
    Route::get('/reports/gstr-3b', [ParityReportController::class, 'gstr3b'])->name('reports.gstr-3b');
    Route::get('/reports/gstr-3b.json', [ParityReportController::class, 'gstr3bFile'])->name('reports.gstr-3b.file');
    Route::get('/reports/tds', [ParityReportController::class, 'tds'])->name('reports.tds');
    Route::get('/reports/budget-variance', [ParityReportController::class, 'budgetVariance'])->name('reports.budget-variance');
    Route::get('/reports/cash-flow', [ParityReportController::class, 'cashFlow'])->name('reports.cash-flow');
    Route::get('/reports/funds-flow', [ParityReportController::class, 'fundsFlow'])->name('reports.funds-flow');
    Route::get('/reports/account-books', [ParityReportController::class, 'accountBooks'])->name('reports.account-books');
    Route::get('/reports/statements-of-accounts', [ParityReportController::class, 'statementsOfAccounts'])->name('reports.statements-of-accounts');
    Route::get('/reports/inventory-books', [ParityReportController::class, 'inventoryBooks'])->name('reports.inventory-books');
    Route::get('/reports/statements-of-inventory', [ParityReportController::class, 'statementsOfInventory'])->name('reports.statements-of-inventory');
    Route::get('/reports/exceptions', [ParityReportController::class, 'exceptions'])->name('reports.exceptions');
    Route::get('/reports/analysis', [ParityReportController::class, 'analysis'])->name('reports.analysis');
    Route::get('/reports/edit-log', [ParityReportController::class, 'editLog'])->name('reports.edit-log');
    Route::get('/reports/outstanding', [AccountingReportController::class, 'outstanding'])->name('reports.outstanding');
    Route::get('/reports/cost-centres', [AccountingReportController::class, 'costCentres'])->name('reports.cost-centres');
    Route::get('/reports/interest', [AccountingReportController::class, 'interest'])->name('reports.interest');

    Route::get('/reports/stock-summary', [InventoryReportController::class, 'summary'])->name('reports.stock-summary');
    Route::get('/reports/stock-ledger', [InventoryReportController::class, 'ledger'])->name('reports.stock-ledger');
    Route::get('/reports/godown-stock', [InventoryReportController::class, 'godowns'])->name('reports.godown-stock');
    Route::get('/reports/stock-movements', [InventoryReportController::class, 'movements'])->name('reports.stock-movements');
    Route::get('/reports/low-stock', [InventoryReportController::class, 'lowStock'])->name('reports.low-stock');
    Route::get('/reports/stock-valuation', [AdvancedInventoryController::class, 'valuation'])->name('reports.stock-valuation');
    Route::get('/reports/expiry', [AdvancedInventoryController::class, 'expiry'])->name('reports.expiry');
    Route::get('/reports/stock-limits', [AdvancedInventoryController::class, 'limits'])->name('reports.stock-limits');
    Route::get('/reports/batch-history', [AdvancedInventoryController::class, 'history'])->name('reports.batch-history');

    Route::get('/masters/boms', [BillOfMaterialController::class, 'index'])->name('boms.index');
    Route::get('/masters/boms/create', [BillOfMaterialController::class, 'create'])->name('boms.create');
    Route::post('/masters/boms', [BillOfMaterialController::class, 'store'])->name('boms.store');
    Route::get('/masters/boms/{bom}', [BillOfMaterialController::class, 'show'])->name('boms.show');
    Route::get('/masters/boms/{bom}/edit', [BillOfMaterialController::class, 'edit'])->name('boms.edit');
    Route::put('/masters/boms/{bom}', [BillOfMaterialController::class, 'update'])->name('boms.update');
    Route::patch('/masters/boms/{bom}/activation', [BillOfMaterialController::class, 'updateActivation'])->name('boms.activation');
    Route::delete('/masters/boms/{bom}', [BillOfMaterialController::class, 'destroy'])->name('boms.destroy');

    Route::get('/transactions/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/transactions/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::post('/transactions/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('/transactions/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::get('/transactions/purchase-orders/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
    Route::get('/transactions/purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchase-orders.pdf');

    Route::get('/transactions/sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('/transactions/sales-orders/report', [SalesOrderController::class, 'report'])->name('sales-orders.report');
    Route::get('/transactions/sales-orders/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
    Route::post('/transactions/sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');
    Route::get('/transactions/sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::get('/transactions/sales-orders/{salesOrder}/edit', [SalesOrderController::class, 'edit'])->name('sales-orders.edit');
    Route::put('/transactions/sales-orders/{salesOrder}', [SalesOrderController::class, 'update'])->name('sales-orders.update');
    Route::patch('/transactions/sales-orders/{salesOrder}/status', [SalesOrderController::class, 'status'])->name('sales-orders.status');
    Route::delete('/transactions/sales-orders/{salesOrder}', [SalesOrderController::class, 'destroy'])->name('sales-orders.destroy');
    Route::get('/transactions/sales-orders/{salesOrder}/print', [SalesOrderController::class, 'print'])->name('sales-orders.print');
    Route::get('/transactions/sales-orders/{salesOrder}/pdf', [SalesOrderController::class, 'pdf'])->name('sales-orders.pdf');

    Route::get('/transactions/payroll/attendance', [PayrollController::class, 'attendance'])->name('payroll.attendance');
    Route::post('/transactions/payroll/attendance', [PayrollController::class, 'storeAttendance'])->name('payroll.attendance.store');
    Route::get('/transactions/payroll/report', [PayrollController::class, 'report'])->name('payroll.report');
    Route::get('/transactions/payroll/challan', [PayrollController::class, 'challan'])->name('payroll.challan');
    Route::post('/transactions/payroll/{payroll}/challan', [PayrollController::class, 'storeChallan'])->name('payroll.challan.store');
    Route::get('/transactions/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/transactions/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
    Route::post('/transactions/payroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::get('/transactions/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::post('/transactions/payroll/{payroll}/process', [PayrollController::class, 'process'])->name('payroll.process');
    Route::delete('/transactions/payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');

    Route::get('/transactions/payment-requests', [ParityMasterController::class, 'paymentRequests'])->name('payment-requests.index');
    Route::get('/transactions/payment-requests/create', [ParityMasterController::class, 'createPaymentRequest'])->name('payment-requests.create');
    Route::post('/transactions/payment-requests', [ParityMasterController::class, 'storePaymentRequest'])->name('payment-requests.store');
    Route::get('/transactions/payment-requests/{paymentRequest}/edit', [ParityMasterController::class, 'editPaymentRequest'])->name('payment-requests.edit');
    Route::put('/transactions/payment-requests/{paymentRequest}', [ParityMasterController::class, 'updatePaymentRequest'])->name('payment-requests.update');
    Route::delete('/transactions/payment-requests/{paymentRequest}', [ParityMasterController::class, 'destroyPaymentRequest'])->name('payment-requests.destroy');

    foreach ([
        'currencies' => ['currencies', 'Currency'],
        'voucher-types' => ['voucherTypes', 'VoucherType'],
        'gst-registrations' => ['gstRegistrations', 'GstRegistration'],
        'merchant-profiles' => ['merchants', 'Merchant'],
        'employees' => ['employees', 'Employee'],
        'pay-heads' => ['payHeads', 'PayHead'],
    ] as $slug => [$index, $action]) {
        Route::get("/masters/{$slug}", [ParityMasterController::class, $index])->name("{$slug}.index");
        Route::get("/masters/{$slug}/create", [ParityMasterController::class, 'create'.$action])->name("{$slug}.create");
        Route::post("/masters/{$slug}", [ParityMasterController::class, 'store'.$action])->name("{$slug}.store");
    }

    Route::get('/masters/currencies/{currency}/edit', [ParityMasterController::class, 'editCurrency'])->name('currencies.edit');
    Route::put('/masters/currencies/{currency}', [ParityMasterController::class, 'updateCurrency'])->name('currencies.update');
    Route::delete('/masters/currencies/{currency}', [ParityMasterController::class, 'destroyCurrency'])->name('currencies.destroy');
    Route::get('/masters/voucher-types/{voucherType}/edit', [ParityMasterController::class, 'editVoucherType'])->name('voucher-types.edit');
    Route::put('/masters/voucher-types/{voucherType}', [ParityMasterController::class, 'updateVoucherType'])->name('voucher-types.update');
    Route::delete('/masters/voucher-types/{voucherType}', [ParityMasterController::class, 'destroyVoucherType'])->name('voucher-types.destroy');
    Route::get('/masters/gst-registrations/{gstRegistration}/edit', [ParityMasterController::class, 'editGstRegistration'])->name('gst-registrations.edit');
    Route::put('/masters/gst-registrations/{gstRegistration}', [ParityMasterController::class, 'updateGstRegistration'])->name('gst-registrations.update');
    Route::delete('/masters/gst-registrations/{gstRegistration}', [ParityMasterController::class, 'destroyGstRegistration'])->name('gst-registrations.destroy');
    Route::get('/masters/merchant-profiles/{merchantProfile}/edit', [ParityMasterController::class, 'editMerchant'])->name('merchant-profiles.edit');
    Route::put('/masters/merchant-profiles/{merchantProfile}', [ParityMasterController::class, 'updateMerchant'])->name('merchant-profiles.update');
    Route::delete('/masters/merchant-profiles/{merchantProfile}', [ParityMasterController::class, 'destroyMerchant'])->name('merchant-profiles.destroy');
    Route::get('/masters/employees/{employee}/edit', [ParityMasterController::class, 'editEmployee'])->name('employees.edit');
    Route::put('/masters/employees/{employee}', [ParityMasterController::class, 'updateEmployee'])->name('employees.update');
    Route::delete('/masters/employees/{employee}', [ParityMasterController::class, 'destroyEmployee'])->name('employees.destroy');
    Route::get('/masters/pay-heads/{payHead}/edit', [ParityMasterController::class, 'editPayHead'])->name('pay-heads.edit');
    Route::put('/masters/pay-heads/{payHead}', [ParityMasterController::class, 'updatePayHead'])->name('pay-heads.update');
    Route::delete('/masters/pay-heads/{payHead}', [ParityMasterController::class, 'destroyPayHead'])->name('pay-heads.destroy');

    Route::get('/transactions/manufacturing', [ManufacturingController::class, 'index'])->name('manufacturing.index');
    Route::get('/transactions/manufacturing/create', [ManufacturingController::class, 'create'])->name('manufacturing.create');
    Route::post('/transactions/manufacturing', [ManufacturingController::class, 'store'])->name('manufacturing.store');
    Route::get('/transactions/manufacturing/{manufacturing}', [ManufacturingController::class, 'show'])->name('manufacturing.show');
    Route::post('/transactions/manufacturing/{manufacturing}/cancel', [ManufacturingController::class, 'cancel'])->name('manufacturing.cancel');
    Route::get('/reports/production', [ManufacturingController::class, 'production'])->name('reports.production');
    Route::get('/reports/consumption', [ManufacturingController::class, 'consumption'])->name('reports.consumption');

    Route::get('/utilities/import', [ImportController::class, 'index'])->name('utilities.import');
    Route::post('/utilities/import/tally', [ImportController::class, 'tally'])->name('utilities.import.tally');
    Route::get('/utilities/import/{dataset}/template', [ImportController::class, 'template'])->name('utilities.import.template');
    Route::post('/utilities/import/{dataset}', [ImportController::class, 'store'])->name('utilities.import.store');

    Route::get('/utilities/export', [ExportController::class, 'index'])->name('utilities.export');
    Route::get('/utilities/export/{dataset}', [ExportController::class, 'download'])->name('utilities.export.download');

    Route::get('/utilities/backup', [BackupController::class, 'index'])->name('utilities.backup');
    Route::post('/utilities/backup', [BackupController::class, 'store'])->name('utilities.backup.store');
    Route::get('/utilities/backup/{backup}/download', [BackupController::class, 'download'])->name('utilities.backup.download');
    Route::get('/utilities/backup/{backup}/restore', [BackupController::class, 'restore'])->middleware('permission:backup.restore')->name('utilities.backup.restore');
    Route::post('/utilities/backup/{backup}/restore', [BackupController::class, 'apply'])->middleware('permission:backup.restore')->name('utilities.backup.restore.store');

    Route::get('/settings/audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('/settings/audit/security', [AuditController::class, 'security'])->name('audit.security');
    Route::get('/settings/audit/{auditLog}', [AuditController::class, 'show'])->name('audit.show');

    Route::get('/settings/keyboard-shortcuts', [KeyboardShortcutController::class, 'index'])->name('settings.shortcuts');
    Route::put('/settings/keyboard-shortcuts', [KeyboardShortcutController::class, 'update'])->name('settings.shortcuts.update');
    Route::post('/settings/keyboard-shortcuts/restore', [KeyboardShortcutController::class, 'restoreAll'])->name('settings.shortcuts.restore-all');
    Route::post('/settings/keyboard-shortcuts/{shortcut}/restore', [KeyboardShortcutController::class, 'restore'])->name('settings.shortcuts.restore');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::middleware('permission:parties.view')->group(function () {
        Route::get('/masters/parties', [PartyController::class, 'index'])->name('parties.index');
        Route::get('/masters/parties/create', [PartyController::class, 'create'])->middleware('permission:parties.create')->name('parties.create');
        Route::post('/masters/parties', [PartyController::class, 'store'])->middleware('permission:parties.create')->name('parties.store');
        Route::get('/masters/parties/{party}', [PartyController::class, 'show'])->name('parties.show');
        Route::get('/masters/parties/{party}/edit', [PartyController::class, 'edit'])->middleware('permission:parties.edit')->name('parties.edit');
        Route::put('/masters/parties/{party}', [PartyController::class, 'update'])->middleware('permission:parties.edit')->name('parties.update');
        Route::patch('/masters/parties/{party}/activation', [PartyController::class, 'updateActivation'])->middleware('permission:parties.edit')->name('parties.activation');
        Route::delete('/masters/parties/{party}', [PartyController::class, 'destroy'])->middleware('permission:parties.delete')->name('parties.destroy');
    });

    Route::middleware('permission:users.view')->group(function () {
        Route::get('/settings/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/settings/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
        Route::post('/settings/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::get('/settings/users/{account}', [UserController::class, 'show'])->name('users.show');
        Route::get('/settings/users/{account}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('users.edit');
        Route::put('/settings/users/{account}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('users.update');
        Route::patch('/settings/users/{account}/activation', [UserController::class, 'updateActivation'])->middleware('permission:users.edit')->name('users.activation');
        Route::put('/settings/users/{account}/password', [UserController::class, 'resetPassword'])->middleware('permission:users.edit')->name('users.password');
        Route::delete('/settings/users/{account}', [UserController::class, 'destroy'])->middleware('permission:users.edit')->name('users.destroy');
    });

    Route::middleware('permission:roles.view')->group(function () {
        Route::get('/settings/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/settings/roles/create', [RoleController::class, 'create'])->middleware('permission:roles.edit')->name('roles.create');
        Route::post('/settings/roles', [RoleController::class, 'store'])->middleware('permission:roles.edit')->name('roles.store');
        Route::get('/settings/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.edit')->name('roles.edit');
        Route::put('/settings/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit')->name('roles.update');
        Route::patch('/settings/roles/{role}/activation', [RoleController::class, 'updateActivation'])->middleware('permission:roles.edit')->name('roles.activation');
        Route::delete('/settings/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.edit')->name('roles.destroy');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings/preferences', [PreferenceController::class, 'edit'])->name('preferences.edit');
        Route::put('/settings/preferences', [PreferenceController::class, 'update'])->name('preferences.update');
    });

    Route::get('/inventory/barcode', [BarcodeController::class, 'lookup'])->middleware('permission:inventory.view')->name('barcodes.lookup');
    Route::get('/masters/products/{product}/barcode', [BarcodeController::class, 'print'])->middleware('permission:inventory.view')->name('products.barcode');

    foreach (app(Navigation::class)->placeholders() as $page) {
        Route::get($page['uri'], [PlaceholderController::class, 'show'])
            ->defaults('page', $page['key'])
            ->name($page['route']);
    }
});
