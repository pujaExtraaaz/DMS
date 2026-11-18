<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pay_heads')) {
            Schema::create('pay_heads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('code', 40)->nullable();
                $table->string('type', 20)->default('earning'); // earning | deduction
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(100);
                $table->timestamps();

                $table->unique(['company_id', 'code']);
            });
        }

        if (! Schema::hasTable('salary_structure_lines')) {
            Schema::create('salary_structure_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('salary_structure_id')->constrained()->cascadeOnDelete();
                $table->foreignId('pay_head_id')->constrained('pay_heads')->restrictOnDelete();
                $table->decimal('amount', 12, 2)->default(0);
                $table->timestamps();

                $table->unique(['salary_structure_id', 'pay_head_id']);
            });
        }

        if (Schema::hasTable('salary_structures') && ! Schema::hasColumn('salary_structures', 'notes')) {
            Schema::table('salary_structures', function (Blueprint $table) {
                $table->string('name')->nullable()->after('employee_id');
                $table->text('notes')->nullable()->after('is_active');
            });
        }

        $this->seedStandardPayHeads();
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structure_lines');
        Schema::dropIfExists('pay_heads');

        if (Schema::hasTable('salary_structures')) {
            Schema::table('salary_structures', function (Blueprint $table) {
                if (Schema::hasColumn('salary_structures', 'notes')) {
                    $table->dropColumn('notes');
                }
                if (Schema::hasColumn('salary_structures', 'name')) {
                    $table->dropColumn('name');
                }
            });
        }
    }

    protected function seedStandardPayHeads(): void
    {
        if (! Schema::hasTable('pay_heads')) {
            return;
        }

        $now = now();
        $heads = [
            ['code' => 'BASIC', 'name' => 'Basic Salary', 'type' => 'earning', 'sort_order' => 10],
            ['code' => 'HRA', 'name' => 'HRA', 'type' => 'earning', 'sort_order' => 20],
            ['code' => 'ALLOWANCES', 'name' => 'Allowances', 'type' => 'earning', 'sort_order' => 30],
        ];

        foreach ($heads as $head) {
            $exists = DB::table('pay_heads')
                ->whereNull('company_id')
                ->where('code', $head['code'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('pay_heads')->insert([
                'company_id' => null,
                'name' => $head['name'],
                'code' => $head['code'],
                'type' => $head['type'],
                'is_system' => true,
                'is_active' => true,
                'sort_order' => $head['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
