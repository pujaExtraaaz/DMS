<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branch_types')) {
            Schema::create('branch_types', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $defaults = ['Head Office', 'Warehouse', 'Sales Office', 'Regional Office'];
        foreach ($defaults as $name) {
            $exists = DB::table('branch_types')
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->exists();
            if (! $exists) {
                DB::table('branch_types')->insert([
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (! Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'alternate_phone')) {
                $table->string('alternate_phone', 30)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('branches', 'email')) {
                $table->string('email', 255)->nullable()->after('alternate_phone');
            }
            if (! Schema::hasColumn('branches', 'alternate_email')) {
                $table->string('alternate_email', 255)->nullable()->after('email');
            }
            if (! Schema::hasColumn('branches', 'address_line_2')) {
                $table->string('address_line_2', 255)->nullable()->after('address');
            }
            if (! Schema::hasColumn('branches', 'city')) {
                $table->string('city', 100)->nullable()->after('address_line_2');
            }
            if (! Schema::hasColumn('branches', 'country')) {
                $table->string('country', 100)->nullable()->after('state');
            }
            if (! Schema::hasColumn('branches', 'branch_type')) {
                $table->string('branch_type', 100)->nullable()->after('code');
            }
            if (! Schema::hasColumn('branches', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('branches', 'notes')) {
                $table->text('notes')->nullable()->after('is_default');
            }
        });

        // Backfill country for existing rows.
        if (Schema::hasColumn('branches', 'country')) {
            DB::table('branches')->whereNull('country')->orWhere('country', '')->update(['country' => 'India']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                foreach (['alternate_phone', 'email', 'alternate_email', 'address_line_2', 'city', 'country', 'branch_type', 'is_default', 'notes'] as $col) {
                    if (Schema::hasColumn('branches', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('branch_types');
    }
};
