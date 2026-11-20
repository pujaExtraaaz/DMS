<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // —— Phase 1: Holidays ——
        if (! Schema::hasTable('holiday_calendars')) {
            Schema::create('holiday_calendars', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedSmallInteger('year');
                $table->string('name');
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'branch_id', 'year', 'name'], 'holiday_calendars_scope_unique');
            });
        }

        if (! Schema::hasTable('holidays')) {
            Schema::create('holidays', function (Blueprint $table) {
                $table->id();
                $table->foreignId('holiday_calendar_id')->constrained()->cascadeOnDelete();
                $table->date('holiday_date');
                $table->string('name');
                $table->string('category', 30)->default('festival'); // national|state|festival|other
                $table->string('kind', 30)->default('compulsory'); // compulsory|optional|restricted
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['holiday_calendar_id', 'holiday_date', 'name'], 'holidays_calendar_date_name_unique');
            });
        }

        // —— Phase 1: Leave policies ——
        if (! Schema::hasTable('leave_policies')) {
            Schema::create('leave_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->decimal('annual_entitlement', 8, 2)->default(0);
                $table->string('accrual_frequency', 20)->default('yearly'); // monthly|yearly
                $table->decimal('carry_forward_max', 8, 2)->default(0);
                $table->boolean('allow_encashment')->default(false);
                $table->decimal('encashment_max', 8, 2)->nullable();
                $table->boolean('allow_half_day')->default(true);
                $table->boolean('allow_short_leave')->default(false);
                $table->decimal('short_leave_hours', 4, 2)->default(2);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('leave_policy_rules')) {
            Schema::create('leave_policy_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('leave_policy_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
                $table->string('employee_category', 50)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('leave_balances')) {
            Schema::table('leave_balances', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_balances', 'accrued_balance')) {
                    $table->decimal('accrued_balance', 8, 2)->default(0)->after('opening_balance');
                }
                if (! Schema::hasColumn('leave_balances', 'encashed_balance')) {
                    $table->decimal('encashed_balance', 8, 2)->default(0)->after('used_balance');
                }
                if (! Schema::hasColumn('leave_balances', 'carried_forward')) {
                    $table->decimal('carried_forward', 8, 2)->default(0)->after('opening_balance');
                }
            });
        }

        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_requests', 'is_half_day')) {
                    $table->boolean('is_half_day')->default(false)->after('days');
                }
                if (! Schema::hasColumn('leave_requests', 'is_short_leave')) {
                    $table->boolean('is_short_leave')->default(false)->after('is_half_day');
                }
                if (! Schema::hasColumn('leave_requests', 'short_leave_hours')) {
                    $table->decimal('short_leave_hours', 4, 2)->nullable()->after('is_short_leave');
                }
                if (! Schema::hasColumn('leave_requests', 'session')) {
                    $table->string('session', 20)->nullable()->after('short_leave_hours'); // first_half|second_half
                }
            });
        }

        if (! Schema::hasTable('leave_encashments')) {
            Schema::create('leave_encashments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('year');
                $table->decimal('days', 8, 2);
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('status', 30)->default('pending'); // pending|approved|rejected|paid
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // —— Phase 2: Shifts & attendance extensions ——
        if (! Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('code', 30)->nullable();
                $table->time('start_time');
                $table->time('end_time');
                $table->unsignedSmallInteger('break_minutes')->default(0);
                $table->unsignedSmallInteger('grace_minutes')->default(0);
                $table->unsignedSmallInteger('half_day_minutes')->default(240);
                $table->unsignedSmallInteger('full_day_minutes')->default(480);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_shifts')) {
            Schema::create('employee_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->json('weekly_offs')->nullable(); // e.g. [0] Sunday, or rotational pattern
                $table->string('weekly_off_mode', 20)->default('fixed'); // fixed|rotational
                $table->timestamps();
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (! Schema::hasColumn('attendances', 'late_minutes')) {
                    $table->unsignedSmallInteger('late_minutes')->default(0)->after('hours_worked');
                }
                if (! Schema::hasColumn('attendances', 'early_minutes')) {
                    $table->unsignedSmallInteger('early_minutes')->default(0)->after('late_minutes');
                }
                if (! Schema::hasColumn('attendances', 'ot_minutes')) {
                    $table->unsignedSmallInteger('ot_minutes')->default(0)->after('early_minutes');
                }
                if (! Schema::hasColumn('attendances', 'source')) {
                    $table->string('source', 20)->default('manual')->after('ot_minutes'); // manual|web|essl|system
                }
                if (! Schema::hasColumn('attendances', 'is_regularised')) {
                    $table->boolean('is_regularised')->default(false)->after('source');
                }
                if (! Schema::hasColumn('attendances', 'shift_id')) {
                    $table->foreignId('shift_id')->nullable()->after('employee_id')->constrained()->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('attendance_regularisations')) {
            Schema::create('attendance_regularisations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->date('attendance_date');
                $table->string('type', 30)->default('missing_punch'); // missing_punch|correction|other
                $table->time('requested_check_in')->nullable();
                $table->time('requested_check_out')->nullable();
                $table->text('reason')->nullable();
                $table->string('status', 30)->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('approval_notes')->nullable();
                $table->timestamps();
            });
        }

        // —— Phase 3: Biometric punches ——
        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'biometric_code')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('biometric_code', 50)->nullable()->after('employee_code');
                $table->index('biometric_code');
            });
        }

        if (! Schema::hasTable('attendance_punches')) {
            Schema::create('attendance_punches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
                $table->string('biometric_code', 50)->nullable();
                $table->dateTime('punched_at');
                $table->string('direction', 10)->nullable(); // in|out|unknown
                $table->string('source', 20)->default('essl'); // essl|web|manual
                $table->string('device_id', 80)->nullable();
                $table->string('external_id', 100)->nullable();
                $table->timestamps();
                $table->unique(['source', 'external_id']);
                $table->index(['employee_id', 'punched_at']);
                $table->index(['biometric_code', 'punched_at']);
            });
        }

        if (! Schema::hasTable('essl_sync_logs')) {
            Schema::create('essl_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->timestamp('started_at');
                $table->timestamp('finished_at')->nullable();
                $table->unsignedInteger('fetched')->default(0);
                $table->unsignedInteger('imported')->default(0);
                $table->unsignedInteger('unmatched')->default(0);
                $table->string('status', 30)->default('running'); // running|ok|failed
                $table->text('message')->nullable();
                $table->timestamps();
            });
        }

        // —— Phase 4: Payroll runs ——
        if (! Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');
                $table->string('status', 30)->default('draft'); // draft|approved|locked
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'branch_id', 'year', 'month'], 'payroll_runs_period_unique');
            });
        }

        if (! Schema::hasTable('payroll_run_lines')) {
            Schema::create('payroll_run_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('payable_days')->default(0);
                $table->unsignedSmallInteger('present_days')->default(0);
                $table->unsignedSmallInteger('leave_days')->default(0);
                $table->unsignedSmallInteger('lwp_days')->default(0);
                $table->unsignedSmallInteger('ot_minutes')->default(0);
                $table->decimal('gross_earnings', 12, 2)->default(0);
                $table->decimal('total_deductions', 12, 2)->default(0);
                $table->decimal('net_payable', 12, 2)->default(0);
                $table->json('earnings_json')->nullable();
                $table->json('deductions_json')->nullable();
                $table->timestamps();
                $table->unique(['payroll_run_id', 'employee_id']);
            });
        }

        $this->seedLeaveTypes();
        $this->seedPayHeads();
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_lines');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('essl_sync_logs');
        Schema::dropIfExists('attendance_punches');
        Schema::dropIfExists('attendance_regularisations');
        Schema::dropIfExists('employee_shifts');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('leave_encashments');
        Schema::dropIfExists('leave_policy_rules');
        Schema::dropIfExists('leave_policies');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('holiday_calendars');

        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'biometric_code')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('biometric_code');
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                foreach (['late_minutes', 'early_minutes', 'ot_minutes', 'source', 'is_regularised', 'shift_id'] as $col) {
                    if (Schema::hasColumn('attendances', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                foreach (['is_half_day', 'is_short_leave', 'short_leave_hours', 'session'] as $col) {
                    if (Schema::hasColumn('leave_requests', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('leave_balances')) {
            Schema::table('leave_balances', function (Blueprint $table) {
                foreach (['accrued_balance', 'encashed_balance', 'carried_forward'] as $col) {
                    if (Schema::hasColumn('leave_balances', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }

    protected function seedLeaveTypes(): void
    {
        if (! Schema::hasTable('leave_types')) {
            return;
        }

        $now = now();
        $types = [
            ['code' => 'CL', 'name' => 'Casual Leave', 'default_days' => 12, 'is_paid' => true],
            ['code' => 'SL', 'name' => 'Sick Leave', 'default_days' => 12, 'is_paid' => true],
            ['code' => 'EL', 'name' => 'Earned Leave', 'default_days' => 15, 'is_paid' => true],
            ['code' => 'PL', 'name' => 'Privilege Leave', 'default_days' => 15, 'is_paid' => true],
            ['code' => 'PAID', 'name' => 'Paid Leave', 'default_days' => 0, 'is_paid' => true],
            ['code' => 'LWP', 'name' => 'Leave Without Pay', 'default_days' => 0, 'is_paid' => false],
            ['code' => 'COFF', 'name' => 'Compensatory Off', 'default_days' => 0, 'is_paid' => true],
        ];

        foreach ($types as $type) {
            $exists = DB::table('leave_types')->whereNull('company_id')->where('code', $type['code'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('leave_types')->insert([
                'company_id' => null,
                'name' => $type['name'],
                'code' => $type['code'],
                'default_days' => $type['default_days'],
                'is_paid' => $type['is_paid'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function seedPayHeads(): void
    {
        if (! Schema::hasTable('pay_heads')) {
            return;
        }

        $now = now();
        $heads = [
            ['code' => 'CONVEYANCE', 'name' => 'Conveyance', 'type' => 'earning', 'sort_order' => 40],
            ['code' => 'INCENTIVES', 'name' => 'Incentives', 'type' => 'earning', 'sort_order' => 50],
            ['code' => 'OT', 'name' => 'Overtime', 'type' => 'earning', 'sort_order' => 60],
            ['code' => 'PF', 'name' => 'Provident Fund', 'type' => 'deduction', 'sort_order' => 110],
            ['code' => 'ESI', 'name' => 'ESI', 'type' => 'deduction', 'sort_order' => 120],
            ['code' => 'PT', 'name' => 'Professional Tax', 'type' => 'deduction', 'sort_order' => 130],
            ['code' => 'TDS', 'name' => 'TDS', 'type' => 'deduction', 'sort_order' => 140],
            ['code' => 'ADVANCE', 'name' => 'Salary Advance', 'type' => 'deduction', 'sort_order' => 150],
            ['code' => 'OTHER_DED', 'name' => 'Other Deductions', 'type' => 'deduction', 'sort_order' => 160],
        ];

        foreach ($heads as $head) {
            $exists = DB::table('pay_heads')->whereNull('company_id')->where('code', $head['code'])->exists();
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
