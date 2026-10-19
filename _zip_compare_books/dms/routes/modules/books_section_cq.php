<?php

/**
 * Section C–Q Books routes (procurement through period-end).
 * Included inside the books.tally route group in books.php.
 */

use Illuminate\Support\Facades\Route;
use Tally\Http\Controllers\AccruedExpenseController;
use Tally\Http\Controllers\AdvanceTaxController;
use Tally\Http\Controllers\AnomalyController;
use Tally\Http\Controllers\BreakEvenController;
use Tally\Http\Controllers\ApprovalController;
use Tally\Http\Controllers\ApprovalPolicyController;
use Tally\Http\Controllers\AssetVerificationController;
use Tally\Http\Controllers\BalanceSheetReconController;
use Tally\Http\Controllers\BankCategorizationController;
use Tally\Http\Controllers\BankSignatoryController;
use Tally\Http\Controllers\CashForecastController;
use Tally\Http\Controllers\CloseChecklistController;
use Tally\Http\Controllers\CollectionReminderController;
use Tally\Http\Controllers\ComparativeReportController;
use Tally\Http\Controllers\ConsolidationController;
use Tally\Http\Controllers\ContingentLiabilityController;
use Tally\Http\Controllers\CustomFieldController;
use Tally\Http\Controllers\DepreciationController;
use Tally\Http\Controllers\ElectronicPaymentController;
use Tally\Http\Controllers\EmployeeAdvanceController;
use Tally\Http\Controllers\EmployeeLoanController;
use Tally\Http\Controllers\EquityTransactionController;
use Tally\Http\Controllers\ExpenseClaimController;
use Tally\Http\Controllers\FixedAssetCategoryController;
use Tally\Http\Controllers\FixedAssetController;
use Tally\Http\Controllers\GoodsInTransitController;
use Tally\Http\Controllers\GoodsReceiptController;
use Tally\Http\Controllers\GstChallanController;
use Tally\Http\Controllers\Gstr2bController;
use Tally\Http\Controllers\IndustryContractController;
use Tally\Http\Controllers\IntegrationEndpointController;
use Tally\Http\Controllers\InventoryAgeingController;
use Tally\Http\Controllers\InventoryImpairmentController;
use Tally\Http\Controllers\ItcController;
use Tally\Http\Controllers\JobWorkController;
use Tally\Http\Controllers\KpiDashboardController;
use Tally\Http\Controllers\LandedCostController;
use Tally\Http\Controllers\LoanController;
use Tally\Http\Controllers\ManagementPackController;
use Tally\Http\Controllers\PayablesReportController;
use Tally\Http\Controllers\PaymentBatchController;
use Tally\Http\Controllers\PayrollProvisionController;
use Tally\Http\Controllers\PrepaidExpenseController;
use Tally\Http\Controllers\ProfitabilityController;
use Tally\Http\Controllers\PurchaseOrderController;
use Tally\Http\Controllers\PurchaseRequisitionController;
use Tally\Http\Controllers\RelatedPartyController;
use Tally\Http\Controllers\RfqController;
use Tally\Http\Controllers\SalaryStructureController;
use Tally\Http\Controllers\StatutoryCalendarController;
use Tally\Http\Controllers\StockCountController;
use Tally\Http\Controllers\TdsComplianceController;
use Tally\Http\Controllers\VendorRetentionController;

// --- C: Procurement / AP ---
Route::get('/transactions/purchase-requisitions', [PurchaseRequisitionController::class, 'index'])->name('purchase-requisitions.index');
Route::get('/transactions/purchase-requisitions/create', [PurchaseRequisitionController::class, 'create'])->name('purchase-requisitions.create');
Route::post('/transactions/purchase-requisitions', [PurchaseRequisitionController::class, 'store'])->name('purchase-requisitions.store');
Route::get('/transactions/purchase-requisitions/{purchaseRequisition}', [PurchaseRequisitionController::class, 'show'])->name('purchase-requisitions.show');
Route::post('/transactions/purchase-requisitions/{purchaseRequisition}/convert-rfq', [PurchaseRequisitionController::class, 'convertToRfq'])->name('purchase-requisitions.convert-rfq');

Route::get('/transactions/rfqs', [RfqController::class, 'index'])->name('rfqs.index');
Route::get('/transactions/rfqs/create', [RfqController::class, 'create'])->name('rfqs.create');
Route::post('/transactions/rfqs', [RfqController::class, 'store'])->name('rfqs.store');
Route::get('/transactions/rfqs/{rfq}', [RfqController::class, 'show'])->name('rfqs.show');
Route::post('/transactions/rfqs/{rfq}/vendor-quotes', [RfqController::class, 'storeVendorQuote'])->name('rfqs.vendor-quotes.store');
Route::post('/transactions/rfqs/{rfq}/vendor-quotes/{vendorQuotation}/select', [RfqController::class, 'selectVendor'])->name('rfqs.select-vendor');
Route::post('/transactions/rfqs/{rfq}/convert-po', [RfqController::class, 'convertToPurchaseOrder'])->name('rfqs.convert-po');

Route::post('/transactions/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');

Route::get('/transactions/goods-receipts', [GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
Route::get('/transactions/goods-receipts/create', [GoodsReceiptController::class, 'create'])->name('goods-receipts.create');
Route::post('/transactions/goods-receipts', [GoodsReceiptController::class, 'store'])->name('goods-receipts.store');
Route::get('/transactions/goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'show'])->name('goods-receipts.show');
Route::post('/transactions/goods-receipts/{goodsReceipt}/post', [GoodsReceiptController::class, 'post'])->name('goods-receipts.post');
Route::post('/transactions/goods-receipts/{goodsReceipt}/create-bill', [GoodsReceiptController::class, 'createBill'])->name('goods-receipts.create-bill');

Route::get('/transactions/expense-claims', [ExpenseClaimController::class, 'index'])->name('expense-claims.index');
Route::get('/transactions/expense-claims/create', [ExpenseClaimController::class, 'create'])->name('expense-claims.create');
Route::post('/transactions/expense-claims', [ExpenseClaimController::class, 'store'])->name('expense-claims.store');
Route::get('/transactions/expense-claims/{expenseClaim}', [ExpenseClaimController::class, 'show'])->name('expense-claims.show');
Route::post('/transactions/expense-claims/{expenseClaim}/approve', [ExpenseClaimController::class, 'approve'])->name('expense-claims.approve');
Route::post('/transactions/expense-claims/{expenseClaim}/pay', [ExpenseClaimController::class, 'pay'])->name('expense-claims.pay');

Route::get('/transactions/landed-costs', [LandedCostController::class, 'index'])->name('landed-costs.index');
Route::get('/transactions/landed-costs/create', [LandedCostController::class, 'create'])->name('landed-costs.create');
Route::post('/transactions/landed-costs', [LandedCostController::class, 'store'])->name('landed-costs.store');
Route::get('/transactions/landed-costs/{landedCost}', [LandedCostController::class, 'show'])->name('landed-costs.show');
Route::post('/transactions/landed-costs/{landedCost}/post', [LandedCostController::class, 'post'])->name('landed-costs.post');

Route::get('/utilities/prepaid-expenses', [PrepaidExpenseController::class, 'index'])->name('prepaid-expenses.index');
Route::post('/utilities/prepaid-expenses', [PrepaidExpenseController::class, 'store'])->name('prepaid-expenses.store');
Route::post('/utilities/prepaid-expenses/recognize', [PrepaidExpenseController::class, 'recognize'])->name('prepaid-expenses.recognize');
Route::get('/utilities/accrued-expenses', [AccruedExpenseController::class, 'index'])->name('accrued-expenses.index');
Route::post('/utilities/accrued-expenses', [AccruedExpenseController::class, 'store'])->name('accrued-expenses.store');
Route::post('/utilities/accrued-expenses/{accruedExpense}/post', [AccruedExpenseController::class, 'post'])->name('accrued-expenses.post');
Route::get('/utilities/employee-advances', [EmployeeAdvanceController::class, 'index'])->name('employee-advances.index');
Route::post('/utilities/employee-advances', [EmployeeAdvanceController::class, 'store'])->name('employee-advances.store');

Route::get('/transactions/payment-batches', [PaymentBatchController::class, 'index'])->name('payment-batches.index');
Route::get('/transactions/payment-batches/create', [PaymentBatchController::class, 'create'])->name('payment-batches.create');
Route::post('/transactions/payment-batches', [PaymentBatchController::class, 'store'])->name('payment-batches.store');
Route::post('/transactions/payment-batches/from-bills', [PaymentBatchController::class, 'fromBills'])->name('payment-batches.from-bills');
Route::get('/transactions/payment-batches/{paymentBatch}', [PaymentBatchController::class, 'show'])->name('payment-batches.show');
Route::post('/transactions/payment-batches/{paymentBatch}/submit', [PaymentBatchController::class, 'submit'])->name('payment-batches.submit');
Route::post('/transactions/payment-batches/{paymentBatch}/approve', [PaymentBatchController::class, 'approve'])->name('payment-batches.approve');
Route::post('/transactions/payment-batches/{paymentBatch}/pay', [PaymentBatchController::class, 'pay'])->name('payment-batches.pay');
Route::get('/transactions/payment-batches/{paymentBatch}/export-neft', [PaymentBatchController::class, 'exportNeft'])->name('payment-batches.export-neft');

Route::get('/reports/supplier-ageing', [PayablesReportController::class, 'ageing'])->name('reports.supplier-ageing');
Route::get('/reports/supplier-statement', [PayablesReportController::class, 'statement'])->name('reports.supplier-statement');
Route::get('/reports/supplier-confirmation', [PayablesReportController::class, 'confirmation'])->name('reports.supplier-confirmation');
Route::get('/reports/payment-due-calendar', [PayablesReportController::class, 'dueCalendar'])->name('reports.payment-due-calendar');
Route::get('/reports/msme-payables', [PayablesReportController::class, 'msmePayables'])->name('reports.msme-payables');

Route::get('/utilities/vendor-retentions', [VendorRetentionController::class, 'index'])->name('vendor-retentions.index');
Route::post('/utilities/vendor-retentions', [VendorRetentionController::class, 'store'])->name('vendor-retentions.store');
Route::post('/utilities/vendor-retentions/{vendorRetention}/release', [VendorRetentionController::class, 'release'])->name('vendor-retentions.release');

// --- D: Banking / treasury gaps ---
Route::get('/banking/rules', [BankCategorizationController::class, 'index'])->name('banking.rules');
Route::post('/banking/rules', [BankCategorizationController::class, 'store'])->name('banking.rules.store');
Route::post('/banking/rules/apply', [BankCategorizationController::class, 'apply'])->name('banking.rules.apply');
Route::delete('/banking/rules/{rule}', [BankCategorizationController::class, 'destroy'])->name('banking.rules.destroy');

Route::get('/banking/electronic-payments', [ElectronicPaymentController::class, 'index'])->name('banking.electronic-payments');
Route::post('/banking/electronic-payments', [ElectronicPaymentController::class, 'store'])->name('banking.electronic-payments.store');
Route::get('/banking/electronic-payments/export-neft', [ElectronicPaymentController::class, 'exportNeft'])->name('banking.electronic-payments.export-neft');
Route::put('/banking/electronic-payments/{payment}', [ElectronicPaymentController::class, 'update'])->name('banking.electronic-payments.update');

Route::get('/banking/forecasts', [CashForecastController::class, 'index'])->name('banking.forecasts');
Route::post('/banking/forecasts', [CashForecastController::class, 'store'])->name('banking.forecasts.store');

Route::get('/banking/signatories', [BankSignatoryController::class, 'index'])->name('banking.signatories');
Route::post('/banking/signatories', [BankSignatoryController::class, 'store'])->name('banking.signatories.store');
Route::put('/banking/signatories/{signatory}', [BankSignatoryController::class, 'update'])->name('banking.signatories.update');

// --- E: GST / statutory ---
Route::get('/tax/gstr2b', [Gstr2bController::class, 'index'])->name('tax.gstr2b');
Route::post('/tax/gstr2b/import', [Gstr2bController::class, 'import'])->name('tax.gstr2b.import');
Route::post('/tax/gstr2b/match', [Gstr2bController::class, 'match'])->name('tax.gstr2b.match');

Route::get('/tax/itc', [ItcController::class, 'index'])->name('tax.itc');
Route::post('/tax/itc', [ItcController::class, 'store'])->name('tax.itc.store');

Route::get('/tax/gst-challans', [GstChallanController::class, 'index'])->name('tax.gst-challans');
Route::post('/tax/gst-challans', [GstChallanController::class, 'store'])->name('tax.gst-challans.store');

Route::get('/tax/tds-compliance', [TdsComplianceController::class, 'index'])->name('tax.tds-compliance');
Route::post('/tax/tds-compliance/challans', [TdsComplianceController::class, 'storeChallan'])->name('tax.tds-compliance.challans.store');
Route::post('/tax/tds-compliance/certificates', [TdsComplianceController::class, 'storeCertificate'])->name('tax.tds-compliance.certificates.store');

Route::get('/tax/statutory-calendar', [StatutoryCalendarController::class, 'index'])->name('tax.statutory-calendar');
Route::post('/tax/statutory-calendar', [StatutoryCalendarController::class, 'store'])->name('tax.statutory-calendar.store');
Route::post('/tax/statutory-calendar/seed', [StatutoryCalendarController::class, 'seed'])->name('tax.statutory-calendar.seed');

// --- F: Inventory gaps ---
Route::get('/transactions/stock-counts', [StockCountController::class, 'index'])->name('stock-counts.index');
Route::get('/transactions/stock-counts/create', [StockCountController::class, 'create'])->name('stock-counts.create');
Route::post('/transactions/stock-counts', [StockCountController::class, 'store'])->name('stock-counts.store');
Route::get('/transactions/stock-counts/{stockCount}', [StockCountController::class, 'show'])->name('stock-counts.show');
Route::get('/transactions/stock-counts/{stockCount}/edit', [StockCountController::class, 'edit'])->name('stock-counts.edit');
Route::put('/transactions/stock-counts/{stockCount}', [StockCountController::class, 'update'])->name('stock-counts.update');

Route::get('/transactions/goods-in-transit', [GoodsInTransitController::class, 'index'])->name('goods-in-transit.index');
Route::get('/transactions/goods-in-transit/create', [GoodsInTransitController::class, 'create'])->name('goods-in-transit.create');
Route::post('/transactions/goods-in-transit', [GoodsInTransitController::class, 'store'])->name('goods-in-transit.store');
Route::get('/transactions/goods-in-transit/{goodsInTransit}', [GoodsInTransitController::class, 'show'])->name('goods-in-transit.show');
Route::post('/transactions/goods-in-transit/{goodsInTransit}/receive', [GoodsInTransitController::class, 'receive'])->name('goods-in-transit.receive');

Route::get('/transactions/job-works', [JobWorkController::class, 'index'])->name('job-works.index');
Route::get('/transactions/job-works/create', [JobWorkController::class, 'create'])->name('job-works.create');
Route::post('/transactions/job-works', [JobWorkController::class, 'store'])->name('job-works.store');
Route::get('/transactions/job-works/{jobWork}', [JobWorkController::class, 'show'])->name('job-works.show');
Route::post('/transactions/job-works/{jobWork}/receive', [JobWorkController::class, 'receive'])->name('job-works.receive');

Route::get('/reports/inventory-ageing', InventoryAgeingController::class)->name('reports.inventory-ageing');

// --- G: Fixed assets ---
Route::get('/masters/fixed-asset-categories', [FixedAssetCategoryController::class, 'index'])->name('fixed-asset-categories.index');
Route::get('/masters/fixed-asset-categories/create', [FixedAssetCategoryController::class, 'create'])->name('fixed-asset-categories.create');
Route::post('/masters/fixed-asset-categories', [FixedAssetCategoryController::class, 'store'])->name('fixed-asset-categories.store');
Route::get('/masters/fixed-asset-categories/{fixedAssetCategory}/edit', [FixedAssetCategoryController::class, 'edit'])->name('fixed-asset-categories.edit');
Route::put('/masters/fixed-asset-categories/{fixedAssetCategory}', [FixedAssetCategoryController::class, 'update'])->name('fixed-asset-categories.update');

Route::get('/masters/fixed-assets', [FixedAssetController::class, 'index'])->name('fixed-assets.index');
Route::get('/masters/fixed-assets/create', [FixedAssetController::class, 'create'])->name('fixed-assets.create');
Route::post('/masters/fixed-assets', [FixedAssetController::class, 'store'])->name('fixed-assets.store');
Route::get('/masters/fixed-assets/{fixedAsset}', [FixedAssetController::class, 'show'])->name('fixed-assets.show');
Route::post('/masters/fixed-assets/{fixedAsset}/capitalize', [FixedAssetController::class, 'capitalize'])->name('fixed-assets.capitalize');
Route::post('/masters/fixed-assets/{fixedAsset}/dispose', [FixedAssetController::class, 'dispose'])->name('fixed-assets.dispose');

Route::get('/utilities/inventory-impairments', [InventoryImpairmentController::class, 'index'])->name('inventory-impairments.index');
Route::post('/utilities/inventory-impairments', [InventoryImpairmentController::class, 'store'])->name('inventory-impairments.store');

Route::get('/utilities/collection-reminders', [CollectionReminderController::class, 'index'])->name('collection-reminders.index');
Route::post('/utilities/collection-reminders/run', [CollectionReminderController::class, 'run'])->name('collection-reminders.run');

Route::get('/tax/advance-tax', [AdvanceTaxController::class, 'index'])->name('advance-tax.index');
Route::post('/tax/advance-tax', [AdvanceTaxController::class, 'store'])->name('advance-tax.store');

Route::get('/reports/break-even', BreakEvenController::class)->name('reports.break-even');
Route::get('/reports/management-pack', [ManagementPackController::class, 'index'])->name('management-pack.index');

Route::get('/utilities/depreciation', [DepreciationController::class, 'index'])->name('depreciation.index');
Route::post('/utilities/depreciation/run', [DepreciationController::class, 'run'])->name('depreciation.run');

Route::get('/utilities/asset-verifications', [AssetVerificationController::class, 'index'])->name('asset-verifications.index');
Route::post('/utilities/asset-verifications', [AssetVerificationController::class, 'store'])->name('asset-verifications.store');

// --- H: Payroll gaps ---
Route::get('/masters/salary-structures', [SalaryStructureController::class, 'index'])->name('salary-structures.index');
Route::post('/masters/salary-structures', [SalaryStructureController::class, 'store'])->name('salary-structures.store');
Route::get('/masters/salary-structures/{salaryStructure}', [SalaryStructureController::class, 'show'])->name('salary-structures.show');

Route::get('/transactions/employee-loans', [EmployeeLoanController::class, 'index'])->name('employee-loans.index');
Route::post('/transactions/employee-loans', [EmployeeLoanController::class, 'store'])->name('employee-loans.store');
Route::get('/transactions/employee-loans/{employeeLoan}', [EmployeeLoanController::class, 'show'])->name('employee-loans.show');

Route::get('/utilities/payroll-provisions', [PayrollProvisionController::class, 'index'])->name('payroll-provisions.index');
Route::post('/utilities/payroll-provisions', [PayrollProvisionController::class, 'store'])->name('payroll-provisions.store');

// --- I/J: Reporting / profitability ---
Route::get('/reports/comparative', [ComparativeReportController::class, 'index'])->name('reports.comparative');
Route::get('/reports/profitability', [ProfitabilityController::class, 'index'])->name('profitability.index');
Route::post('/reports/profitability', [ProfitabilityController::class, 'store'])->name('profitability.store');

// --- K: Loans / equity ---
Route::get('/masters/loans', [LoanController::class, 'index'])->name('loans.index');
Route::post('/masters/loans', [LoanController::class, 'store'])->name('loans.store');
Route::get('/masters/loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
Route::post('/masters/loans/{loan}/disburse', [LoanController::class, 'disburse'])->name('loans.disburse');
Route::post('/masters/loans/{loan}/schedules/{schedule}/pay', [LoanController::class, 'paySchedule'])->name('loans.schedules.pay');

Route::get('/masters/contingent-liabilities', [ContingentLiabilityController::class, 'index'])->name('contingent-liabilities.index');
Route::post('/masters/contingent-liabilities', [ContingentLiabilityController::class, 'store'])->name('contingent-liabilities.store');

Route::get('/utilities/equity-transactions', [EquityTransactionController::class, 'index'])->name('equity-transactions.index');
Route::post('/utilities/equity-transactions', [EquityTransactionController::class, 'store'])->name('equity-transactions.store');

Route::get('/masters/related-parties', [RelatedPartyController::class, 'index'])->name('related-parties.index');
Route::post('/masters/related-parties', [RelatedPartyController::class, 'store'])->name('related-parties.store');

// --- L: Multi-entity ---
Route::get('/utilities/consolidation', [ConsolidationController::class, 'index'])->name('consolidation.index');
Route::post('/utilities/consolidation', [ConsolidationController::class, 'store'])->name('consolidation.store');
Route::get('/utilities/consolidation/worksheet', [ConsolidationController::class, 'worksheet'])->name('consolidation.worksheet');
Route::post('/utilities/consolidation/eliminations', [ConsolidationController::class, 'postEliminations'])->name('consolidation.eliminations');

// --- M/N: Controls / workflow ---
Route::get('/settings/approval-policies', [ApprovalPolicyController::class, 'index'])->name('approval-policies.index');
Route::post('/settings/approval-policies', [ApprovalPolicyController::class, 'store'])->name('approval-policies.store');

Route::get('/utilities/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
Route::post('/utilities/approvals', [ApprovalController::class, 'store'])->name('approvals.store');
Route::get('/utilities/approvals/{approvalRequest}', [ApprovalController::class, 'show'])->name('approvals.show');
Route::post('/utilities/approvals/{approvalRequest}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
Route::post('/utilities/approvals/{approvalRequest}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');

Route::get('/settings/custom-fields', [CustomFieldController::class, 'index'])->name('custom-fields.index');
Route::post('/settings/custom-fields', [CustomFieldController::class, 'store'])->name('custom-fields.store');

Route::get('/utilities/integration-endpoints', [IntegrationEndpointController::class, 'index'])->name('integration-endpoints.index');
Route::post('/utilities/integration-endpoints', [IntegrationEndpointController::class, 'store'])->name('integration-endpoints.store');
Route::get('/utilities/integration-endpoints/{integrationEndpoint}', [IntegrationEndpointController::class, 'show'])->name('integration-endpoints.show');
Route::post('/utilities/integration-endpoints/{integrationEndpoint}/sync', [IntegrationEndpointController::class, 'sync'])->name('integration-endpoints.sync');

// --- O: Management intelligence ---
Route::get('/reports/kpi-dashboard', [KpiDashboardController::class, 'index'])->name('kpi-dashboard.index');
Route::post('/reports/kpi-dashboard', [KpiDashboardController::class, 'store'])->name('kpi-dashboard.store');

Route::get('/utilities/anomalies', [AnomalyController::class, 'index'])->name('anomalies.index');
Route::post('/utilities/anomalies', [AnomalyController::class, 'store'])->name('anomalies.store');

// --- P: Industry ---
Route::get('/masters/industry-contracts', [IndustryContractController::class, 'index'])->name('industry-contracts.index');
Route::post('/masters/industry-contracts', [IndustryContractController::class, 'store'])->name('industry-contracts.store');
Route::get('/masters/industry-contracts/{industryContract}', [IndustryContractController::class, 'show'])->name('industry-contracts.show');

// --- Q: Period-end close ---
Route::get('/utilities/close-checklists', [CloseChecklistController::class, 'index'])->name('close-checklists.index');
Route::post('/utilities/close-checklists', [CloseChecklistController::class, 'store'])->name('close-checklists.store');
Route::get('/utilities/close-checklists/{closeChecklist}', [CloseChecklistController::class, 'show'])->name('close-checklists.show');

Route::get('/utilities/balance-sheet-recons', [BalanceSheetReconController::class, 'index'])->name('balance-sheet-recons.index');
Route::post('/utilities/balance-sheet-recons', [BalanceSheetReconController::class, 'store'])->name('balance-sheet-recons.store');
