<?php

namespace Tally\Invoicing;

use Tally\Accounting\VoucherNumberer;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;

/**
 * Sales and purchase numbers use the voucher sequences, scoped by company, branch,
 * financial year, and transaction type. Nothing here is a fixed number.
 */
class InvoiceNumberer
{
    public function __construct(private readonly VoucherNumberer $numbers) {}

    public function peek(Company $company, Branch $branch, FinancialYear $financialYear, InvoiceKind $kind): string
    {
        return $this->numbers->peek($company, $branch, $financialYear, $kind->voucherType(), null);
    }

    public function allocate(Company $company, Branch $branch, FinancialYear $financialYear, InvoiceKind $kind, ?\DateTimeInterface $on = null): string
    {
        return $this->numbers->allocate($company, $branch, $financialYear, $kind->voucherType(), $on);
    }

    public function resolve(Company $company, Branch $branch, FinancialYear $financialYear, InvoiceKind $kind, ?string $typed, ?\DateTimeInterface $on = null): string
    {
        return $this->numbers->resolve($company, $branch, $financialYear, $kind->voucherType(), $typed, $on, 'invoice_number');
    }

    public function method(Company $company, InvoiceKind $kind): string
    {
        return $this->numbers->method($company, $kind->voucherType());
    }
}
