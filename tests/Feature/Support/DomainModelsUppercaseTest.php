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
        $this->assertSame('Apple', $brand->name);

        $category = new Category(['name' => 'jeans']);
        $this->assertSame('Jeans', $category->name);

        $subCategory = new SubCategory(['name' => 'slim fit']);
        $this->assertSame('Slim Fit', $subCategory->name);

        $product = new Product(['name' => 'Sony professional Display']);
        $this->assertSame('Sony Professional Display', $product->name);

        $customer = new Customer(['name' => 'avit digital', 'shipping_name' => 'avit warehouse']);
        $this->assertSame('Avit Digital', $customer->name);
        $this->assertSame('Avit Warehouse', $customer->shipping_name);

        $company = new Company(['name' => 'Sam manufacturing', 'legal_name' => 'sam pvt ltd']);
        $this->assertSame('Sam Manufacturing', $company->name);
        $this->assertSame('Sam Pvt Ltd', $company->legal_name);

        $warehouse = new Warehouse(['name' => 'midc pune warehouse']);
        $this->assertSame('MIDC Pune Warehouse', $warehouse->name);

        $branch = new Branch(['name' => 'pune branch']);
        $this->assertSame('Pune Branch', $branch->name);

        $businessGroup = new BusinessGroup(['name' => 'trojan distributors']);
        $this->assertSame('Trojan Distributors', $businessGroup->name);

        $freightBill = new FreightBill(['transporter_name' => 'vrl logistics']);
        $this->assertSame('Vrl Logistics', $freightBill->transporter_name);

        $expenseType = new ExpenseType(['name' => 'installation']);
        $this->assertSame('Installation', $expenseType->name);

        $scheme = new Scheme(['name' => 'festive discount', 'created_by_name' => 'john doe']);
        $this->assertSame('Festive Discount', $scheme->name);
        $this->assertSame('John Doe', $scheme->created_by_name);

        $employee = new Employee(['name' => 'john smith']);
        $this->assertSame('John Smith', $employee->name);

        $user = new User(['name' => 'admin user']);
        $this->assertSame('Admin User', $user->name);

        $tallyParty = new TallyParty(['legal_name' => 'Bulk Foods Depot', 'contact_person' => 'ravi kumar']);
        $this->assertSame('Bulk Foods Depot', $tallyParty->legal_name);
        $this->assertSame('Ravi Kumar', $tallyParty->contact_person);
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

        $this->assertSame('Premium Basmati Rice 25kg', $product->name);
        $this->assertSame('SKU-RICE-01', $product->sku);
    }
}
