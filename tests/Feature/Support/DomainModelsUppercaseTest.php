<?php

namespace Tests\Feature\Support;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Deal\Models\ExpenseType;
use App\Domains\Hrms\Models\Department;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Driver;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Route;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Models\Vehicle;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Scheme\Models\Scheme;
use App\Models\User;
use Tally\Models\Party as TallyParty;
use Tests\TestCase;

class DomainModelsUppercaseTest extends TestCase
{
    public function test_domain_models_uppercase_assigned_names(): void
    {
        $brand = new Brand(['name' => 'apple']);
        $this->assertSame('APPLE', $brand->name);

        $category = new Category(['name' => 'jeans']);
        $this->assertSame('JEANS', $category->name);

        $subCategory = new SubCategory(['name' => 'slim fit']);
        $this->assertSame('SLIM FIT', $subCategory->name);

        $product = new Product(['name' => 'Sony professional Display']);
        $this->assertSame('SONY PROFESSIONAL DISPLAY', $product->name);

        $customer = new Customer(['name' => 'avit digital', 'shipping_name' => 'avit warehouse']);
        $this->assertSame('AVIT DIGITAL', $customer->name);
        $this->assertSame('AVIT WAREHOUSE', $customer->shipping_name);

        $company = new Company(['name' => 'Sam manufacturing', 'legal_name' => 'sam pvt ltd']);
        $this->assertSame('SAM MANUFACTURING', $company->name);
        $this->assertSame('SAM PVT LTD', $company->legal_name);

        $warehouse = new Warehouse(['name' => 'midc pune warehouse']);
        $this->assertSame('MIDC PUNE WAREHOUSE', $warehouse->name);

        $branch = new Branch(['name' => 'pune branch']);
        $this->assertSame('PUNE BRANCH', $branch->name);

        $businessGroup = new BusinessGroup(['name' => 'trojan distributors']);
        $this->assertSame('TROJAN DISTRIBUTORS', $businessGroup->name);

        $freightBill = new FreightBill(['transporter_name' => 'vrl logistics']);
        $this->assertSame('VRL LOGISTICS', $freightBill->transporter_name);

        $expenseType = new ExpenseType(['name' => 'installation']);
        $this->assertSame('INSTALLATION', $expenseType->name);

        $scheme = new Scheme(['name' => 'festive discount', 'created_by_name' => 'john doe']);
        $this->assertSame('FESTIVE DISCOUNT', $scheme->name);
        $this->assertSame('JOHN DOE', $scheme->created_by_name);

        $employee = new Employee(['name' => 'john smith']);
        $this->assertSame('JOHN SMITH', $employee->name);

        $user = new User(['name' => 'admin user']);
        $this->assertSame('ADMIN USER', $user->name);

        $tallyParty = new TallyParty(['legal_name' => 'Bulk Foods Depot', 'contact_person' => 'ravi kumar']);
        $this->assertSame('BULK FOODS DEPOT', $tallyParty->legal_name);
        $this->assertSame('RAVI KUMAR', $tallyParty->contact_person);
    }

    public function test_existing_database_records_display_in_uppercase(): void
    {
        $product = new Product();
        $product->setRawAttributes([
            'id' => 100,
            'name' => 'Premium Basmati Rice 25kg',
            'sku' => 'SKU-RICE-01',
            'selling_price' => 1200.00,
        ]);

        $this->assertSame('PREMIUM BASMATI RICE 25KG', $product->name);
        $this->assertSame('SKU-RICE-01', $product->sku);
    }
}

