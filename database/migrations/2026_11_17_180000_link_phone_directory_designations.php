<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phone_directory_contacts') || ! Schema::hasTable('designations')) {
            return;
        }

        Schema::table('phone_directory_contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('phone_directory_contacts', 'designation_id')) {
                $table->foreignId('designation_id')
                    ->nullable()
                    ->after('designation')
                    ->constrained('designations')
                    ->nullOnDelete();
            }
        });

        // Backfill designation_id from existing free-text values.
        // Creates a master designation when an exact match is missing.
        $contacts = DB::table('phone_directory_contacts')
            ->whereNull('designation_id')
            ->whereNotNull('designation')
            ->where('designation', '!=', '')
            ->select('id', 'designation')
            ->get();

        foreach ($contacts as $contact) {
            $name = trim((string) $contact->designation);
            if ($name === '' || strcasecmp($name, 'Other') === 0) {
                continue;
            }

            $masterId = DB::table('designations')
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->value('id');

            if (! $masterId) {
                $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr(strtoupper($name), 0, 8)) ?: 'DES');
                $base = $code;
                $suffix = 1;
                while (DB::table('designations')->where('code', $code)->exists()) {
                    $code = $base.$suffix;
                    $suffix++;
                }

                $masterId = DB::table('designations')->insertGetId([
                    'name' => $name,
                    'code' => $code,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('phone_directory_contacts')
                ->where('id', $contact->id)
                ->update(['designation_id' => $masterId]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('phone_directory_contacts')) {
            return;
        }

        Schema::table('phone_directory_contacts', function (Blueprint $table) {
            if (Schema::hasColumn('phone_directory_contacts', 'designation_id')) {
                $table->dropConstrainedForeignId('designation_id');
            }
        });
    }
};
