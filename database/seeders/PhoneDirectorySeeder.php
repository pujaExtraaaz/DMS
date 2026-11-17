<?php

namespace Database\Seeders;

use App\Domains\PhoneDirectory\Models\PhoneDirectoryCategory;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryContact;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryDepartment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PhoneDirectorySeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::query()->value('id');

        $dept = fn (string $name) => PhoneDirectoryDepartment::query()->firstOrCreate(
            ['name' => $name],
            ['is_active' => true, 'created_by' => $creatorId]
        )->id;

        $cat = fn (string $name, string $color = 'violet') => PhoneDirectoryCategory::query()->firstOrCreate(
            ['name' => $name],
            ['color' => $color, 'is_active' => true, 'created_by' => $creatorId]
        )->id;

        $contacts = [
            [
                'contact_name' => 'Rajesh Kumar Sharma',
                'contact_type' => 'employee',
                'designation' => 'Sales Manager',
                'department' => 'Sales',
                'category' => 'Internal',
                'company_name' => 'Avit Digital',
                'mobile_number' => '9876543210',
                'email' => 'rajesh.sharma@avit.test',
                'state' => 'Maharashtra',
                'city' => 'Mumbai',
                'area' => 'Andheri East',
                'pincode' => '400069',
                'is_important' => true,
            ],
            [
                'contact_name' => 'Priya Nair',
                'contact_type' => 'customer',
                'designation' => 'Purchase Head',
                'department' => 'Purchase',
                'category' => 'Key Client',
                'company_name' => 'Kerala Traders Pvt Ltd',
                'mobile_number' => '9847012345',
                'email' => 'priya.nair@keralatraders.test',
                'state' => 'Kerala',
                'city' => 'Kochi',
                'area' => 'Edappally',
                'pincode' => '682024',
                'is_important' => true,
            ],
            [
                'contact_name' => 'Amitabh Singh',
                'contact_type' => 'supplier',
                'designation' => 'Vendor Manager',
                'department' => 'Purchase',
                'category' => 'Key Vendor',
                'company_name' => 'Lucknow Components',
                'mobile_number' => '9415011122',
                'email' => 'amitabh@lucknowcomponents.test',
                'state' => 'Uttar Pradesh',
                'city' => 'Lucknow',
                'area' => 'Gomti Nagar',
                'pincode' => '226010',
                'is_important' => false,
            ],
            [
                'contact_name' => 'Sneha Reddy',
                'contact_type' => 'dealer',
                'designation' => 'Regional Dealer',
                'department' => 'Sales',
                'category' => 'Partner',
                'company_name' => 'Hyderabad Auto Hub',
                'mobile_number' => '9000012345',
                'email' => 'sneha.reddy@hydautohub.test',
                'state' => 'Telangana',
                'city' => 'Hyderabad',
                'area' => 'Banjara Hills',
                'pincode' => '500034',
                'is_important' => false,
            ],
            [
                'contact_name' => 'Vikram Patel',
                'contact_type' => 'distributor',
                'designation' => 'Distributor',
                'department' => 'Logistics',
                'category' => 'Partner',
                'company_name' => 'Gujarat Distributors',
                'mobile_number' => '9825012345',
                'email' => 'vikram.patel@gjdist.test',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
                'area' => 'Satellite',
                'pincode' => '380015',
                'is_important' => true,
            ],
            [
                'contact_name' => 'Ananya Banerjee',
                'contact_type' => 'business_partner',
                'designation' => 'Business Head',
                'department' => 'Management',
                'category' => 'VIP',
                'company_name' => 'Bengal Alliance',
                'mobile_number' => '9830011223',
                'email' => 'ananya@bengalalliance.test',
                'state' => 'West Bengal',
                'city' => 'Kolkata',
                'area' => 'Salt Lake',
                'pincode' => '700091',
                'is_important' => true,
            ],
            [
                'contact_name' => 'Karthik Iyer',
                'contact_type' => 'service_provider',
                'designation' => 'Service Lead',
                'department' => 'IT',
                'category' => 'General',
                'company_name' => 'Chennai Tech Services',
                'mobile_number' => '9884012345',
                'email' => 'karthik.iyer@chennaitech.test',
                'state' => 'Tamil Nadu',
                'city' => 'Chennai',
                'area' => 'T Nagar',
                'pincode' => '600017',
                'is_important' => false,
            ],
            [
                'contact_name' => 'Meera Joshi',
                'contact_type' => 'management',
                'designation' => 'Operations Director',
                'department' => 'Operations',
                'category' => 'Internal',
                'company_name' => 'Avit Digital',
                'mobile_number' => '9811012345',
                'email' => 'meera.joshi@avit.test',
                'state' => 'Delhi',
                'city' => 'New Delhi',
                'area' => 'Connaught Place',
                'pincode' => '110001',
                'is_important' => true,
            ],
            [
                'contact_name' => 'Harpreet Singh',
                'contact_type' => 'government_official',
                'designation' => 'Compliance Officer',
                'department' => 'Administration',
                'category' => 'General',
                'company_name' => 'Punjab Regulatory Desk',
                'mobile_number' => '9872212345',
                'email' => 'harpreet.singh@punjabdesk.test',
                'state' => 'Punjab',
                'city' => 'Chandigarh',
                'area' => 'Sector 17',
                'pincode' => '160017',
                'is_important' => false,
            ],
            [
                'contact_name' => 'Fatima Sheikh',
                'contact_type' => 'customer',
                'designation' => 'Store Owner',
                'department' => 'Sales',
                'category' => 'Key Client',
                'company_name' => 'Jaipur Retail Mart',
                'mobile_number' => '9829012345',
                'email' => 'fatima@jaipurmart.test',
                'state' => 'Rajasthan',
                'city' => 'Jaipur',
                'area' => 'Malviya Nagar',
                'pincode' => '302017',
                'is_important' => false,
            ],
        ];

        foreach ($contacts as $row) {
            PhoneDirectoryContact::query()->updateOrCreate(
                ['mobile_number' => $row['mobile_number']],
                [
                    'contact_name' => $row['contact_name'],
                    'contact_type' => $row['contact_type'],
                    'designation' => $row['designation'],
                    'department_id' => $dept($row['department']),
                    'category_id' => $cat($row['category']),
                    'company_name' => $row['company_name'],
                    'email' => $row['email'],
                    'whatsapp_number' => $row['mobile_number'],
                    'country' => 'India',
                    'state' => $row['state'],
                    'city' => $row['city'],
                    'area' => $row['area'],
                    'pincode' => $row['pincode'],
                    'address' => $row['area'].', '.$row['city'].', '.$row['state'],
                    'is_important' => $row['is_important'],
                    'status' => 'active',
                    'notes' => 'Seeded sample directory contact.',
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'archived_at' => null,
                ]
            );
        }

        $this->command?->info('Seeded 10 phone directory contacts across different states.');
    }
}
