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

        $this->assertSame('SONY PROFESSIONAL DISPLAY', $model->name);
        $this->assertSame('AVIT DIGITAL', $model->company_name);
        $this->assertSame('BULK FOODS DEPOT', $model->transporter_name);
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

        $this->assertSame('APPLE INC', $model->name);
        $this->assertSame('APPLE OPERATIONS', $model->legal_name);
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
        $this->assertSame('JOHN SMITH', $arr['name']);
        $this->assertSame('JOHN WAREHOUSE', $arr['shipping_name']);
        $this->assertSame('john@example.com', $arr['email']);

        $json = json_decode($model->toJson(), true);
        $this->assertSame('JOHN SMITH', $json['name']);
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

        $this->assertSame('BULK FOODS DEPOT', $model->getAttributes()['name']);
        $this->assertSame('AVIT DIGITAL', $model->getAttributes()['company_name']);
    }
}
