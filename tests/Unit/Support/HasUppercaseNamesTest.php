<?php

namespace Tests\Unit\Support;

use App\Support\NameCase;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class TestUppercaseModel extends Model
{
    use HasUppercaseNames;

    protected $guarded = [];
}

class HasUppercaseNamesTest extends TestCase
{
    public function test_name_fields_are_converted_to_uppercase_on_assignment(): void
    {
        $model = new TestUppercaseModel();
        $model->name = 'Sony professional Display';
        $model->company_name = 'avit digital';
        $model->transporter_name = 'Bulk Foods Depot';
        $model->email = 'contact@example.com';
        $model->sku = 'SKU-001';

        $this->assertSame('Sony Professional Display', $model->name);
        $this->assertSame('Avit Digital', $model->company_name);
        $this->assertSame('Bulk Foods Depot', $model->transporter_name);
        $this->assertSame('contact@example.com', $model->email);
        $this->assertSame('SKU-001', $model->sku);
    }

    public function test_existing_raw_database_records_are_retrieved_in_uppercase(): void
    {
        $model = new TestUppercaseModel();
        $model->setRawAttributes([
            'id' => 1,
            'name' => 'apple inc',
            'legal_name' => 'apple operations',
            'email' => 'apple@example.com',
            'code' => 'br-001',
        ]);

        $this->assertSame('Apple Inc', $model->name);
        $this->assertSame('Apple Operations', $model->legal_name);
        $this->assertSame('apple@example.com', $model->email);
        $this->assertSame('br-001', $model->code);
    }

    public function test_to_array_and_to_json_output_uppercase_names(): void
    {
        $model = new TestUppercaseModel();
        $model->setRawAttributes([
            'id' => 2,
            'name' => 'john smith',
            'shipping_name' => 'john warehouse',
            'email' => 'john@example.com',
        ]);

        $arr = $model->toArray();
        $this->assertSame('John Smith', $arr['name']);
        $this->assertSame('John Warehouse', $arr['shipping_name']);
        $this->assertSame('john@example.com', $arr['email']);

        $json = json_decode($model->toJson(), true);
        $this->assertSame('John Smith', $json['name']);
    }

    public function test_uppercase_name_attributes_modifies_underlying_raw_attributes(): void
    {
        $model = new TestUppercaseModel();
        $model->setRawAttributes([
            'id' => 3,
            'name' => 'Bulk Foods Depot',
            'company_name' => 'avit digital',
        ]);

        $model->uppercaseNameAttributes();

        $this->assertSame('Bulk Foods Depot', $model->getAttributes()['name']);
        $this->assertSame('Avit Digital', $model->getAttributes()['company_name']);
    }

    public function test_json_payloads_and_cast_attributes_are_never_corrupted(): void
    {
        $model = new class extends Model {
            use HasUppercaseNames;
            protected $guarded = [];
            protected function casts(): array
            {
                return [
                    'additional_details' => 'array',
                ];
            }
        };

        $model->name = 'acme corp';
        $model->additional_details = [
            'contacts' => [],
            'bank_accounts' => [
                [
                    'bank_name' => 'hdfc bank',
                    'bank_account_no' => '50200115516401',
                    'bank_ifsc' => 'HDFC0001390',
                    'upi_id' => null,
                    'od_limit' => 10000000,
                    'interest_rate' => 10.25,
                ],
            ],
        ];

        $model->uppercaseNameAttributes();

        $rawAttributes = $model->getAttributes();
        $this->assertSame('Acme Corp', $rawAttributes['name']);

        // The additional_details raw attribute must still be valid JSON and have snake_case keys and lowercase null
        $json = $rawAttributes['additional_details'];
        $this->assertIsString($json);
        $this->assertStringContainsString('"bank_name"', $json);
        $this->assertStringContainsString('"upi_id":null', $json);
        $this->assertStringNotContainsString('"UPI_ID":Null', $json);
        $this->assertStringNotContainsString('"Bank_Accounts"', $json);
        $this->assertTrue(json_validate($json));
    }
}
