<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'father_name')) {
                $table->string('father_name')->nullable()->after('date_of_birth');
            }
            if (! Schema::hasColumn('employees', 'blood_group')) {
                $table->string('blood_group', 10)->nullable()->after('father_name');
            }
            if (! Schema::hasColumn('employees', 'aadhaar_number')) {
                $table->string('aadhaar_number', 20)->nullable()->after('blood_group');
            }
            if (! Schema::hasColumn('employees', 'pan_number')) {
                $table->string('pan_number', 20)->nullable()->after('aadhaar_number');
            }
            if (! Schema::hasColumn('employees', 'personal_email')) {
                $table->string('personal_email')->nullable()->after('email');
            }
            if (! Schema::hasColumn('employees', 'contact_number')) {
                $table->string('contact_number', 20)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('employees', 'alternate_contact_number')) {
                $table->string('alternate_contact_number', 20)->nullable()->after('contact_number');
            }
            if (! Schema::hasColumn('employees', 'present_address')) {
                $table->text('present_address')->nullable()->after('address');
            }
            if (! Schema::hasColumn('employees', 'permanent_address')) {
                $table->text('permanent_address')->nullable()->after('present_address');
            }
            if (! Schema::hasColumn('employees', 'emergency_contact_number')) {
                $table->string('emergency_contact_number', 20)->nullable()->after('permanent_address');
            }
            if (! Schema::hasColumn('employees', 'emergency_contact_relationship')) {
                $table->string('emergency_contact_relationship', 80)->nullable()->after('emergency_contact_number');
            }
            if (! Schema::hasColumn('employees', 'bank_account_holder')) {
                $table->string('bank_account_holder')->nullable()->after('emergency_contact_relationship');
            }
            if (! Schema::hasColumn('employees', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('bank_account_holder');
            }
            if (! Schema::hasColumn('employees', 'bank_branch')) {
                $table->string('bank_branch')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('employees', 'bank_account_number')) {
                $table->string('bank_account_number', 40)->nullable()->after('bank_branch');
            }
            if (! Schema::hasColumn('employees', 'bank_account_type')) {
                $table->string('bank_account_type', 40)->nullable()->after('bank_account_number');
            }
            if (! Schema::hasColumn('employees', 'bank_ifsc')) {
                $table->string('bank_ifsc', 20)->nullable()->after('bank_account_type');
            }
            if (! Schema::hasColumn('employees', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('bank_ifsc');
            }
            if (! Schema::hasColumn('employees', 'cancelled_cheque_path')) {
                $table->string('cancelled_cheque_path')->nullable()->after('photo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $cols = [
                'father_name', 'blood_group', 'aadhaar_number', 'pan_number', 'personal_email',
                'contact_number', 'alternate_contact_number', 'present_address', 'permanent_address',
                'emergency_contact_number', 'emergency_contact_relationship',
                'bank_account_holder', 'bank_name', 'bank_branch', 'bank_account_number',
                'bank_account_type', 'bank_ifsc', 'photo_path', 'cancelled_cheque_path',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
