<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\VoucherType;
use Tally\Audit\AuditLogger;
use Tally\Context\WorkspaceContext;
use Tally\Models\BankAccount;
use Tally\Models\Currency;
use Tally\Models\Employee;
use Tally\Models\GstRegistration;
use Tally\Models\Ledger;
use Tally\Models\MerchantProfile;
use Tally\Models\PayHead;
use Tally\Models\PaymentRequest;
use Tally\Models\VoucherTypeMaster;
use Tally\Tax\GstRegistrationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParityMasterController extends Controller
{
    public function currencies(Request $request, WorkspaceContext $context): View
    {
        return $this->index($context, 'Currencies', 'currencies', Currency::class, ['Name' => 'name', 'Code' => 'code', 'Symbol' => 'symbol', 'Decimals' => 'decimal_places', 'Rate' => 'exchange_rate']);
    }

    public function createCurrency(WorkspaceContext $context): View
    {
        return $this->form($context, 'Currency', 'currencies.store', new Currency(['decimal_places' => 2, 'exchange_rate' => 1, 'is_base' => false]), $this->currencyFields($context));
    }

    public function storeCurrency(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:8', Rule::unique('currencies', 'code')->where('company_id', $company->id)],
            'symbol' => ['required', 'string', 'max:8'],
            'decimal_places' => ['required', 'integer', 'between:0,4'],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'is_base' => ['sometimes', 'boolean'],
        ]);
        $data['is_base'] = $request->boolean('is_base');
        $data['code'] = strtoupper($data['code']);

        if ($data['is_base']) {
            $company->currencies()->update(['is_base' => false]);
        }

        $company->currencies()->create($data);

        return redirect()->route('books.tally.currencies.index')->with('status', 'Currency created.');
    }

    public function editCurrency(WorkspaceContext $context, Currency $currency): View
    {
        $this->owns($context, $currency->company_id);

        return $this->form($context, 'Currency', 'currencies.update', $currency, $this->currencyFields($context), 'PUT');
    }

    public function updateCurrency(Request $request, WorkspaceContext $context, Currency $currency): RedirectResponse
    {
        $this->owns($context, $currency->company_id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:8', Rule::unique('currencies', 'code')->where('company_id', $currency->company_id)->ignore($currency->id)],
            'symbol' => ['required', 'string', 'max:8'],
            'decimal_places' => ['required', 'integer', 'between:0,4'],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'is_base' => ['sometimes', 'boolean'],
        ]);
        $data['is_base'] = $request->boolean('is_base');
        $data['code'] = strtoupper($data['code']);

        if ($data['is_base']) {
            Currency::query()->where('company_id', $currency->company_id)->whereKeyNot($currency->id)->update(['is_base' => false]);
        }

        $currency->update($data);

        return redirect()->route('books.tally.currencies.index')->with('status', 'Currency updated.');
    }

    public function destroyCurrency(WorkspaceContext $context, Currency $currency, AuditLogger $audit): RedirectResponse
    {
        $this->owns($context, $currency->company_id);
        $audit->record('currency_deleted', 'masters', $currency, 'Currency '.$currency->code.' deleted.');
        $currency->delete();

        return redirect()->route('books.tally.currencies.index')->with('status', 'Currency deleted.');
    }

    public function voucherTypes(WorkspaceContext $context): View
    {
        return $this->index($context, 'Voucher Types', 'voucher-types', VoucherTypeMaster::class, ['Name' => 'name', 'Abbreviation' => 'abbreviation', 'Category' => 'category', 'Prefix' => 'prefix']);
    }

    public function createVoucherType(WorkspaceContext $context): View
    {
        return $this->form($context, 'Voucher type', 'voucher-types.store', new VoucherTypeMaster(['next_number' => 1, 'padding' => 6]), $this->voucherTypeFields());
    }

    public function storeVoucherType(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        $data = $this->voucherTypeData($request, $company->id);
        $company->voucherTypeMasters()->create($data);

        return redirect()->route('books.tally.voucher-types.index')->with('status', 'Voucher type created.');
    }

    public function editVoucherType(WorkspaceContext $context, VoucherTypeMaster $voucherType): View
    {
        $this->owns($context, $voucherType->company_id);

        return $this->form($context, 'Voucher type', 'voucher-types.update', $voucherType, $this->voucherTypeFields(), 'PUT');
    }

    public function updateVoucherType(Request $request, WorkspaceContext $context, VoucherTypeMaster $voucherType): RedirectResponse
    {
        $this->owns($context, $voucherType->company_id);
        $voucherType->update($this->voucherTypeData($request, $voucherType->company_id, $voucherType->id));

        return redirect()->route('books.tally.voucher-types.index')->with('status', 'Voucher type updated.');
    }

    public function destroyVoucherType(WorkspaceContext $context, VoucherTypeMaster $voucherType): RedirectResponse
    {
        $this->owns($context, $voucherType->company_id);
        $voucherType->delete();

        return redirect()->route('books.tally.voucher-types.index')->with('status', 'Voucher type deleted.');
    }

    public function gstRegistrations(WorkspaceContext $context): View
    {
        return $this->index($context, 'GST Registrations', 'gst-registrations', GstRegistration::class, ['GSTIN' => 'gstin', 'Type' => 'registration_type', 'State' => 'state', 'From' => 'effective_from']);
    }

    public function createGstRegistration(WorkspaceContext $context): View
    {
        return $this->form($context, 'GST registration', 'gst-registrations.store', new GstRegistration(['registration_type' => 'regular']), $this->gstFields($context));
    }

    public function storeGstRegistration(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $context->company()->gstRegistrations()->create($this->gstData($request, $context->company()->id));

        return redirect()->route('books.tally.gst-registrations.index')->with('status', 'GST registration created.');
    }

    public function editGstRegistration(WorkspaceContext $context, GstRegistration $gstRegistration): View
    {
        $this->owns($context, $gstRegistration->company_id);

        return $this->form($context, 'GST registration', 'gst-registrations.update', $gstRegistration, $this->gstFields($context), 'PUT');
    }

    public function updateGstRegistration(Request $request, WorkspaceContext $context, GstRegistration $gstRegistration): RedirectResponse
    {
        $this->owns($context, $gstRegistration->company_id);
        $gstRegistration->update($this->gstData($request, $gstRegistration->company_id, $gstRegistration->id));

        return redirect()->route('books.tally.gst-registrations.index')->with('status', 'GST registration updated.');
    }

    public function destroyGstRegistration(WorkspaceContext $context, GstRegistration $gstRegistration): RedirectResponse
    {
        $this->owns($context, $gstRegistration->company_id);
        $gstRegistration->delete();

        return redirect()->route('books.tally.gst-registrations.index')->with('status', 'GST registration deleted.');
    }

    public function merchants(WorkspaceContext $context): View
    {
        return $this->index($context, 'Merchant Profiles', 'merchant-profiles', MerchantProfile::class, ['Name' => 'name', 'Code' => 'merchant_code']);
    }

    public function createMerchant(WorkspaceContext $context): View
    {
        return $this->form($context, 'Merchant profile', 'merchant-profiles.store', new MerchantProfile, $this->merchantFields($context));
    }

    public function storeMerchant(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $context->company()->merchantProfiles()->create($this->merchantData($request, $context->company()->id));

        return redirect()->route('books.tally.merchant-profiles.index')->with('status', 'Merchant profile created.');
    }

    public function editMerchant(WorkspaceContext $context, MerchantProfile $merchantProfile): View
    {
        $this->owns($context, $merchantProfile->company_id);

        return $this->form($context, 'Merchant profile', 'merchant-profiles.update', $merchantProfile, $this->merchantFields($context), 'PUT');
    }

    public function updateMerchant(Request $request, WorkspaceContext $context, MerchantProfile $merchantProfile): RedirectResponse
    {
        $this->owns($context, $merchantProfile->company_id);
        $merchantProfile->update($this->merchantData($request, $merchantProfile->company_id, $merchantProfile->id));

        return redirect()->route('books.tally.merchant-profiles.index')->with('status', 'Merchant profile updated.');
    }

    public function destroyMerchant(WorkspaceContext $context, MerchantProfile $merchantProfile): RedirectResponse
    {
        $this->owns($context, $merchantProfile->company_id);
        $merchantProfile->delete();

        return redirect()->route('books.tally.merchant-profiles.index')->with('status', 'Merchant profile deleted.');
    }

    public function paymentRequests(WorkspaceContext $context): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', ['title' => 'Payment Requests', 'message' => 'Select a company and financial year.']);
        }

        $records = PaymentRequest::query()->with('ledger')->where('company_id', $company->id)->where('financial_year_id', $year->id)->latest('due_date')->paginate(25);

        return view('tally::masters.records', [
            'title' => 'Payment Requests',
            'company' => $company,
            'records' => $records,
            'columns' => ['Party' => 'ledger.name', 'Amount' => 'amount', 'Due' => 'due_date', 'Status' => 'status'],
            'create' => tally_route('payment-requests.create'),
            'edit' => 'payment-requests.edit',
            'delete' => 'payment-requests.destroy',
        ]);
    }

    public function createPaymentRequest(WorkspaceContext $context): View
    {
        return $this->form($context, 'Payment request', 'payment-requests.store', new PaymentRequest(['status' => 'pending']), $this->paymentFields($context));
    }

    public function storePaymentRequest(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        $company->paymentRequests()->create($this->paymentData($request, $company->id) + [
            'branch_id' => $context->branch()->id,
            'financial_year_id' => $context->financialYear()->id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('books.tally.payment-requests.index')->with('status', 'Payment request created.');
    }

    public function editPaymentRequest(WorkspaceContext $context, PaymentRequest $paymentRequest): View
    {
        $this->owns($context, $paymentRequest->company_id);

        return $this->form($context, 'Payment request', 'payment-requests.update', $paymentRequest, $this->paymentFields($context), 'PUT');
    }

    public function updatePaymentRequest(Request $request, WorkspaceContext $context, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->owns($context, $paymentRequest->company_id);

        if ($paymentRequest->voucher_id) {
            return back()->with('error', 'This payment request is linked to voucher '.$paymentRequest->voucher_id.' and cannot be changed.');
        }

        $paymentRequest->update($this->paymentData($request, $paymentRequest->company_id));

        return redirect()->route('books.tally.payment-requests.index')->with('status', 'Payment request updated.');
    }

    public function destroyPaymentRequest(WorkspaceContext $context, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->owns($context, $paymentRequest->company_id);

        if ($paymentRequest->voucher_id) {
            return back()->with('error', 'This payment request is linked to a posted payment and cannot be deleted.');
        }

        $paymentRequest->delete();

        return redirect()->route('books.tally.payment-requests.index')->with('status', 'Payment request deleted.');
    }

    public function employees(WorkspaceContext $context): View
    {
        return $this->index($context, 'Employees', 'employees', Employee::class, ['Name' => 'name', 'Code' => 'employee_code', 'Designation' => 'designation']);
    }

    public function createEmployee(WorkspaceContext $context): View
    {
        return $this->form($context, 'Employee', 'employees.store', new Employee(['monthly_earnings' => 0, 'monthly_deductions' => 0]), $this->employeeFields($context));
    }

    public function storeEmployee(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $context->company()->employees()->create($this->employeeData($request, $context->company()->id));

        return redirect()->route('books.tally.employees.index')->with('status', 'Employee created.');
    }

    public function editEmployee(WorkspaceContext $context, Employee $employee): View
    {
        $this->owns($context, $employee->company_id);

        return $this->form($context, 'Employee', 'employees.update', $employee, $this->employeeFields($context), 'PUT');
    }

    public function updateEmployee(Request $request, WorkspaceContext $context, Employee $employee): RedirectResponse
    {
        $this->owns($context, $employee->company_id);
        $employee->update($this->employeeData($request, $employee->company_id, $employee->id));

        return redirect()->route('books.tally.employees.index')->with('status', 'Employee updated.');
    }

    public function destroyEmployee(WorkspaceContext $context, Employee $employee): RedirectResponse
    {
        $this->owns($context, $employee->company_id);

        if ($employee->payrollLines()->exists()) {
            return back()->with('error', 'This employee is on a payroll run and cannot be deleted.');
        }

        $employee->delete();

        return redirect()->route('books.tally.employees.index')->with('status', 'Employee deleted.');
    }

    public function payHeads(WorkspaceContext $context): View
    {
        return $this->index($context, 'Pay Heads', 'pay-heads', PayHead::class, ['Name' => 'name', 'Nature' => 'nature']);
    }

    public function createPayHead(WorkspaceContext $context): View
    {
        return $this->form($context, 'Pay head', 'pay-heads.store', new PayHead(['nature' => 'earning']), $this->payHeadFields($context));
    }

    public function storePayHead(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $context->company()->payHeads()->create($this->payHeadData($request, $context->company()->id));

        return redirect()->route('books.tally.pay-heads.index')->with('status', 'Pay head created.');
    }

    public function editPayHead(WorkspaceContext $context, PayHead $payHead): View
    {
        $this->owns($context, $payHead->company_id);

        return $this->form($context, 'Pay head', 'pay-heads.update', $payHead, $this->payHeadFields($context), 'PUT');
    }

    public function updatePayHead(Request $request, WorkspaceContext $context, PayHead $payHead): RedirectResponse
    {
        $this->owns($context, $payHead->company_id);
        $payHead->update($this->payHeadData($request, $payHead->company_id));

        return redirect()->route('books.tally.pay-heads.index')->with('status', 'Pay head updated.');
    }

    public function destroyPayHead(WorkspaceContext $context, PayHead $payHead): RedirectResponse
    {
        $this->owns($context, $payHead->company_id);
        $payHead->delete();

        return redirect()->route('books.tally.pay-heads.index')->with('status', 'Pay head deleted.');
    }

    private function index(WorkspaceContext $context, string $title, string $route, string $model, array $columns): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => $title, 'message' => 'Select a company.']);
        }

        return view('tally::masters.records', [
            'title' => $title,
            'company' => $company,
            'records' => $model::query()->where('company_id', $company->id)->orderBy('id')->paginate(25),
            'columns' => $columns,
            'create' => tally_route($route.'.create'),
            'edit' => $route.'.edit',
            'delete' => $route.'.destroy',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     */
    private function form(WorkspaceContext $context, string $title, string $action, object $record, array $fields, string $method = 'POST'): View
    {
        return view('tally::masters.record-form', [
            'title' => $title,
            'company' => $context->company(),
            'action' => $record->exists ? tally_route($action, $record) : tally_route($action),
            'method' => $method,
            'record' => $record,
            'fields' => $fields,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function currencyFields(WorkspaceContext $context): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['name' => 'code', 'label' => 'Code', 'type' => 'text'],
            ['name' => 'symbol', 'label' => 'Symbol', 'type' => 'text'],
            ['name' => 'decimal_places', 'label' => 'Decimal places', 'type' => 'number'],
            ['name' => 'exchange_rate', 'label' => 'Exchange rate', 'type' => 'text'],
            ['name' => 'is_base', 'label' => 'Base currency', 'type' => 'check'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function voucherTypeFields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['name' => 'abbreviation', 'label' => 'Abbreviation', 'type' => 'text'],
            ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => collect(VoucherType::cases())->mapWithKeys(fn (VoucherType $type) => [$type->value => $type->label()])->all()],
            ['name' => 'prefix', 'label' => 'Number prefix', 'type' => 'text'],
            ['name' => 'next_number', 'label' => 'Next number', 'type' => 'number'],
            ['name' => 'padding', 'label' => 'Padding', 'type' => 'number'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function voucherTypeData(Request $request, int $companyId, ?int $ignore = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'abbreviation' => ['required', 'string', 'max:12', Rule::unique('voucher_type_masters', 'abbreviation')->where('company_id', $companyId)->ignore($ignore)],
            'category' => ['required', Rule::in(array_column(VoucherType::cases(), 'value'))],
            'prefix' => ['required', 'string', 'max:12'],
            'next_number' => ['required', 'integer', 'min:1'],
            'padding' => ['required', 'integer', 'between:1,8'],
        ]);
        $data['abbreviation'] = strtoupper($data['abbreviation']);
        $data['prefix'] = strtoupper($data['prefix']);

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function gstFields(WorkspaceContext $context): array
    {
        return [
            ['name' => 'gstin', 'label' => 'GSTIN', 'type' => 'text'],
            ['name' => 'registration_type', 'label' => 'Registration type', 'type' => 'select', 'options' => collect(GstRegistrationType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->name])->all()],
            ['name' => 'state', 'label' => 'State', 'type' => 'text'],
            ['name' => 'branch_id', 'label' => 'Branch', 'type' => 'select', 'options' => $context->company()->branches()->orderBy('name')->pluck('name', 'id')->all(), 'optional' => true],
            ['name' => 'effective_from', 'label' => 'Effective from', 'type' => 'date'],
            ['name' => 'effective_to', 'label' => 'Effective to', 'type' => 'date', 'optional' => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gstData(Request $request, int $companyId, ?int $ignore = null): array
    {
        $data = $request->validate([
            'gstin' => ['required', 'string', 'size:15', Rule::unique('gst_registrations', 'gstin')->where('company_id', $companyId)->ignore($ignore)],
            'registration_type' => ['required', Rule::in(array_column(GstRegistrationType::cases(), 'value'))],
            'state' => ['required', 'string', 'max:80'],
            'branch_id' => ['nullable', 'integer'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);
        $data['gstin'] = strtoupper($data['gstin']);
        $data['branch_id'] = ($data['branch_id'] ?? null) ?: null;

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function merchantFields(WorkspaceContext $context): array
    {
        $ledgers = $context->company()->ledgers()->orderBy('name')->pluck('name', 'id')->all();
        $banks = BankAccount::query()->where('company_id', $context->company()->id)->orderBy('bank_name')->pluck('bank_name', 'id')->all();

        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['name' => 'legal_name', 'label' => 'Legal name', 'type' => 'text', 'optional' => true],
            ['name' => 'merchant_code', 'label' => 'Merchant code', 'type' => 'text'],
            ['name' => 'branch_id', 'label' => 'Branch', 'type' => 'select', 'options' => $context->company()->branches()->orderBy('name')->pluck('name', 'id')->all(), 'optional' => true],
            ['name' => 'settlement_ledger_id', 'label' => 'Settlement ledger', 'type' => 'select', 'options' => $ledgers, 'optional' => true],
            ['name' => 'bank_account_id', 'label' => 'Bank account', 'type' => 'select', 'options' => $banks, 'optional' => true],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'text', 'optional' => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function merchantData(Request $request, int $companyId, ?int $ignore = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'merchant_code' => ['required', 'string', 'max:40', Rule::unique('merchant_profiles', 'merchant_code')->where('company_id', $companyId)->ignore($ignore)],
            'branch_id' => ['nullable', 'integer'],
            'settlement_ledger_id' => ['nullable', 'integer'],
            'bank_account_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach (['branch_id', 'settlement_ledger_id', 'bank_account_id'] as $key) {
            $data[$key] = ($data[$key] ?? null) ?: null;
        }

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paymentFields(WorkspaceContext $context): array
    {
        return [
            ['name' => 'ledger_id', 'label' => 'Party or account', 'type' => 'select', 'options' => $context->company()->ledgers()->orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'amount', 'label' => 'Amount', 'type' => 'text'],
            ['name' => 'due_date', 'label' => 'Due date', 'type' => 'date'],
            ['name' => 'reference', 'label' => 'Reference', 'type' => 'text', 'optional' => true],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['pending' => 'Pending', 'paid' => 'Paid', 'cancelled' => 'Cancelled']],
            ['name' => 'narration', 'label' => 'Narration', 'type' => 'text', 'optional' => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentData(Request $request, int $companyId): array
    {
        $data = $request->validate([
            'ledger_id' => ['required', Rule::exists('ledgers', 'id')->where('company_id', $companyId)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'due_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['pending', 'paid', 'cancelled'])],
            'narration' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['status'] === 'paid') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Mark a request paid by linking it from the payment voucher. Leave it pending until then.',
            ]);
        }

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function employeeFields(WorkspaceContext $context): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['name' => 'employee_code', 'label' => 'Code', 'type' => 'text'],
            ['name' => 'designation', 'label' => 'Designation', 'type' => 'text', 'optional' => true],
            ['name' => 'group_name', 'label' => 'Employee group', 'type' => 'text', 'optional' => true],
            ['name' => 'pan', 'label' => 'PAN', 'type' => 'text', 'optional' => true],
            ['name' => 'joining_date', 'label' => 'Joining date', 'type' => 'date', 'optional' => true],
            ['name' => 'ledger_id', 'label' => 'Payable ledger', 'type' => 'select', 'options' => $context->company()->ledgers()->orderBy('name')->pluck('name', 'id')->all(), 'optional' => true, 'create' => tally_route('ledgers.create')],
            ['name' => 'monthly_earnings', 'label' => 'Monthly earnings', 'type' => 'text'],
            ['name' => 'monthly_deductions', 'label' => 'Monthly deductions', 'type' => 'text'],
            ['name' => 'branch_id', 'label' => 'Branch', 'type' => 'select', 'options' => $context->company()->branches()->orderBy('name')->pluck('name', 'id')->all(), 'optional' => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeData(Request $request, int $companyId, ?int $ignore = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'employee_code' => ['required', 'string', 'max:20', Rule::unique('employees', 'employee_code')->where('company_id', $companyId)->ignore($ignore)],
            'designation' => ['nullable', 'string', 'max:80'],
            'group_name' => ['nullable', 'string', 'max:80'],
            'pan' => ['nullable', 'string', 'max:10'],
            'joining_date' => ['nullable', 'date'],
            'ledger_id' => ['nullable', 'integer'],
            'monthly_earnings' => ['required', 'numeric', 'gte:0'],
            'monthly_deductions' => ['required', 'numeric', 'gte:0'],
            'branch_id' => ['nullable', 'integer'],
        ]);
        $data['ledger_id'] = ($data['ledger_id'] ?? null) ?: null;
        $data['branch_id'] = ($data['branch_id'] ?? null) ?: null;

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function payHeadFields(WorkspaceContext $context): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['name' => 'nature', 'label' => 'Nature', 'type' => 'select', 'options' => ['earning' => 'Earning', 'deduction' => 'Deduction']],
            ['name' => 'calculation', 'label' => 'Calculation', 'type' => 'select', 'options' => ['flat' => 'Flat amount', 'percent' => 'Percent of earnings']],
            ['name' => 'rate_or_amount', 'label' => 'Rate or amount', 'type' => 'text'],
            ['name' => 'ledger_id', 'label' => 'Ledger', 'type' => 'select', 'options' => $context->company()->ledgers()->orderBy('name')->pluck('name', 'id')->all(), 'create' => tally_route('ledgers.create')],
            ['name' => 'employee_id', 'label' => 'Employee', 'type' => 'select', 'options' => $context->company()->employees()->orderBy('name')->pluck('name', 'id')->all(), 'optional' => true, 'create' => tally_route('employees.create')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payHeadData(Request $request, int $companyId): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'nature' => ['required', Rule::in(['earning', 'deduction'])],
            'calculation' => ['required', Rule::in(['flat', 'percent'])],
            'rate_or_amount' => ['required', 'numeric', 'gte:0'],
            'ledger_id' => ['required', Rule::exists('ledgers', 'id')->where('company_id', $companyId)],
            'employee_id' => ['nullable', 'integer'],
        ]);
        $data['employee_id'] = ($data['employee_id'] ?? null) ?: null;

        return $data;
    }

    private function owns(WorkspaceContext $context, int $companyId): void
    {
        abort_unless($context->company() && $context->company()->id === $companyId, 404);
    }
}
