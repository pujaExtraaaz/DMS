<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Phone Directory Departments
        if (! Schema::hasTable('phone_directory_departments')) {
            Schema::create('phone_directory_departments', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });

            // Seed default departments
            $defaultDepartments = [
                'Accounts',
                'Administration',
                'HR',
                'Sales',
                'Marketing',
                'Purchase',
                'Logistics',
                'IT',
                'Management',
                'Operations',
                'Other',
            ];

            foreach ($defaultDepartments as $dept) {
                DB::table('phone_directory_departments')->insert([
                    'name' => $dept,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Phone Directory Categories
        if (! Schema::hasTable('phone_directory_categories')) {
            Schema::create('phone_directory_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('color')->default('violet');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });

            // Seed default categories
            $defaultCategories = [
                ['name' => 'General', 'color' => 'slate'],
                ['name' => 'VIP', 'color' => 'amber'],
                ['name' => 'Key Client', 'color' => 'violet'],
                ['name' => 'Key Vendor', 'color' => 'indigo'],
                ['name' => 'Internal', 'color' => 'emerald'],
                ['name' => 'Partner', 'color' => 'rose'],
            ];

            foreach ($defaultCategories as $cat) {
                DB::table('phone_directory_categories')->insert([
                    'name' => $cat['name'],
                    'color' => $cat['color'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Phone Directory Contacts
        if (! Schema::hasTable('phone_directory_contacts')) {
            Schema::create('phone_directory_contacts', function (Blueprint $table) {
                $table->id();
                $table->string('contact_name');
                $table->string('profile_photo')->nullable();
                $table->string('contact_type')->default('other');
                $table->string('designation')->nullable();
                $table->foreignId('department_id')->nullable()->constrained('phone_directory_departments')->nullOnDelete();
                $table->string('company_name')->nullable();
                $table->foreignId('category_id')->nullable()->constrained('phone_directory_categories')->nullOnDelete();

                // Contact Information
                $table->string('mobile_number');
                $table->string('alternate_mobile')->nullable();
                $table->string('office_phone')->nullable();
                $table->string('extension_number')->nullable();
                $table->string('whatsapp_number')->nullable();
                $table->string('email')->nullable();
                $table->string('alternate_email')->nullable();

                // Address Details
                $table->string('country')->default('India');
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->string('area')->nullable();
                $table->text('address')->nullable();
                $table->string('pincode', 20)->nullable();

                // Additional Details
                $table->boolean('is_important')->default(false);
                $table->string('status')->default('active'); // active / inactive
                $table->text('notes')->nullable();

                // Audit & Archive
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index('contact_name');
                $table->index('mobile_number');
                $table->index('email');
                $table->index('contact_type');
                $table->index('is_important');
                $table->index('status');
                $table->index('archived_at');
                $table->index('company_name');
                $table->index('state');
                $table->index('city');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_directory_contacts');
        Schema::dropIfExists('phone_directory_categories');
        Schema::dropIfExists('phone_directory_departments');
    }
};
