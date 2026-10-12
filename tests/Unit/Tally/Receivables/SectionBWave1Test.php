<?php

namespace Tests\Unit\Tally\Receivables;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\PaymentTerm;
use Tally\Models\PartyAddress;
use Tally\Parties\PartyService;
use Tally\Selling\QuotationService;

class SectionBWave1Test extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_payment_term_and_party_address_can_be_saved(): void
    {
        $company = $this->makeCompany();

        $term = PaymentTerm::query()->create([
            'company_id' => $company->id,
            'name' => 'Net 45',
            'days' => 45,
            'due_on_receipt' => false,
            'is_active' => true,
        ]);

        $party = app(PartyService::class)->save($company, [
            'type' => 'customer',
            'name' => 'Acme Buyer',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'payment_term_id' => $term->id,
            'credit_days' => 30,
            'is_active' => true,
        ]);

        $this->assertSame($term->id, $party->fresh()->payment_term_id);
        $this->assertSame($term->id, $party->ledger->fresh()->payment_term_id);

        $address = PartyAddress::query()->create([
            'company_id' => $company->id,
            'party_id' => $party->id,
            'label' => 'HO',
            'kind' => 'billing',
            'line1' => '1 Main Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'is_default_billing' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($party->fresh()->load('addresses')->defaultBillingAddress()->is($address));
        $this->assertSame(45, $term->daysForDue());
        $this->assertFalse(InvoiceKind::Proforma->postsToBooks());
        $this->assertTrue(InvoiceKind::Sales->postsToBooks());
    }

    public function test_quotation_converts_to_sales_order(): void
    {
        $company = $this->makeCompany();
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();
        $user = User::factory()->create();

        $party = app(PartyService::class)->save($company, [
            'type' => 'customer',
            'name' => 'Quote Customer',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        $quote = app(QuotationService::class)->save($company, $branch, $year, $user, [
            'quote_date' => $year->start_date->toDateString(),
            'customer_ledger_id' => $party->ledger_id,
            'status' => 'sent',
            'lines' => [
                ['item_name' => 'Service', 'quantity' => '1', 'rate' => '1000', 'discount' => '0', 'tax_amount' => '180'],
            ],
        ]);

        $order = app(QuotationService::class)->convertToSalesOrder($quote, $user);

        $this->assertSame($quote->id, $order->quotation_id);
        $this->assertSame('accepted', $quote->fresh()->status);
        $this->assertSame($order->id, $quote->fresh()->sales_order_id);
    }
}
