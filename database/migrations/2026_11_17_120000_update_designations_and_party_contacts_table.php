<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure description column exists on designations table
        if (Schema::hasTable('designations')) {
            Schema::table('designations', function (Blueprint $table) {
                if (! Schema::hasColumn('designations', 'description')) {
                    $table->text('description')->nullable()->after('code');
                }
            });
        }

        // 2. Add foreign key designation_id to party_contacts table
        if (Schema::hasTable('party_contacts')) {
            Schema::table('party_contacts', function (Blueprint $table) {
                if (! Schema::hasColumn('party_contacts', 'designation_id')) {
                    $table->foreignId('designation_id')->nullable()->after('role')->constrained('designations')->nullOnDelete();
                }
            });
        }

        // 3. Seed initial standard designations if not already present
        $standardDesignations = [
            'Accounts' => 'ACC',
            'Architect' => 'ARC',
            'ASM' => 'ASM',
            'Branch Manager' => 'BM',
            'CEO' => 'CEO',
            'Customer' => 'CUST',
            'Director' => 'DIR',
            'Gen. Manger' => 'GM',
            'General Manager' => 'GENMGR',
            'Logistics' => 'LOG',
            'Marketing' => 'MKT',
            'MD' => 'MD',
            'Owner' => 'OWN',
            'Partner' => 'PRT',
            'Purchase Dept.' => 'PUR',
            'Salesman' => 'SLS',
            'Store Manager' => 'SM',
            'Other' => 'OTH',
        ];

        foreach ($standardDesignations as $name => $code) {
            $exists = DB::table('designations')
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->exists();

            if (! $exists) {
                // Ensure unique code
                $candidateCode = $code;
                $suffix = 1;
                while (DB::table('designations')->where('code', $candidateCode)->exists()) {
                    $candidateCode = $code . '_' . $suffix;
                    $suffix++;
                }

                DB::table('designations')->insert([
                    'name' => $name,
                    'code' => $candidateCode,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 4. Map existing party_contacts to their corresponding designation_id
        if (Schema::hasTable('party_contacts') && Schema::hasColumn('party_contacts', 'designation_id')) {
            $contacts = DB::table('party_contacts')
                ->whereNull('designation_id')
                ->whereNotNull('role')
                ->where('role', '!=', '')
                ->get(['id', 'role']);

            foreach ($contacts as $contact) {
                $role = trim((string) $contact->role);
                if ($role === '') {
                    continue;
                }

                $designationId = DB::table('designations')
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($role)])
                    ->value('id');

                if ($designationId) {
                    DB::table('party_contacts')
                        ->where('id', $contact->id)
                        ->update(['designation_id' => $designationId]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('party_contacts') && Schema::hasColumn('party_contacts', 'designation_id')) {
            Schema::table('party_contacts', function (Blueprint $table) {
                $table->dropForeign(['designation_id']);
                $table->dropColumn('designation_id');
            });
        }

        if (Schema::hasTable('designations') && Schema::hasColumn('designations', 'description')) {
            Schema::table('designations', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
