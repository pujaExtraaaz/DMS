<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_oem_centres')) {
            Schema::create('service_oem_centres', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('brand')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('mobile', 20)->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_sla_policies')) {
            Schema::create('service_sla_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('priority', ['critical', 'high', 'normal', 'low']);
                $table->unsignedInteger('resolution_hours')->default(72);
                $table->unsignedInteger('escalate_after_hours')->nullable();
                $table->foreignId('escalate_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'priority']);
            });
        }

        if (! Schema::hasTable('service_tickets')) {
            Schema::create('service_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_no', 40)->unique();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('request_type', ['complaint', 'installation', 'service']);
                $table->enum('party_type', ['dealer', 'end_user', 'company'])->default('end_user');
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->string('customer_name');
                $table->string('contact_person')->nullable();
                $table->string('contact_mobile', 20)->nullable();
                $table->string('alt_contact_person')->nullable();
                $table->string('alt_contact_mobile', 20)->nullable();
                $table->text('billing_address')->nullable();
                $table->text('site_address')->nullable();
                $table->string('brand')->nullable();
                $table->string('product_category')->nullable();
                $table->string('model_number')->nullable();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->string('serial_no')->nullable()->index();
                $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->nullOnDelete();
                $table->string('invoice_no')->nullable();
                $table->date('invoice_date')->nullable();
                $table->enum('warranty_status', ['in_warranty', 'out_of_warranty', 'unknown'])->default('unknown');
                $table->date('warranty_start')->nullable();
                $table->date('warranty_expiry')->nullable();
                $table->text('problem_description')->nullable();
                $table->enum('priority', ['critical', 'high', 'normal', 'low'])->default('normal');
                $table->enum('status', [
                    'new', 'assigned', 'visit', 'pending', 'completed', 'confirmed', 'closed', 'cancelled',
                ])->default('new')->index();
                $table->enum('handler_type', ['avit', 'oem', 'both'])->default('avit');
                $table->foreignId('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->foreignId('oem_centre_id')->nullable()->constrained('service_oem_centres')->nullOnDelete();
                $table->timestamp('expected_visit_at')->nullable();
                $table->timestamp('sla_due_at')->nullable()->index();
                $table->timestamp('sla_escalated_at')->nullable();
                $table->text('completion_remarks')->nullable();
                $table->string('completion_status')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->unsignedTinyInteger('feedback_rating')->nullable();
                $table->text('feedback_comments')->nullable();
                $table->timestamp('feedback_at')->nullable();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_ticket_attachments')) {
            Schema::create('service_ticket_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_ticket_id')->constrained()->cascadeOnDelete();
                $table->string('kind', 40)->default('other');
                $table->string('disk')->default('public');
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->string('mime')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_ticket_assignments')) {
            Schema::create('service_ticket_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_ticket_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->foreignId('oem_centre_id')->nullable()->constrained('service_oem_centres')->nullOnDelete();
                $table->string('notes')->nullable();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_ticket_activities')) {
            Schema::create('service_ticket_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_ticket_id')->constrained()->cascadeOnDelete();
                $table->string('event', 60);
                $table->string('from_status', 40)->nullable();
                $table->string('to_status', 40)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_visits')) {
            Schema::create('service_visits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_ticket_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->timestamp('visited_at')->nullable();
                $table->text('customer_complaint')->nullable();
                $table->text('problem_identified')->nullable();
                $table->text('action_taken')->nullable();
                $table->text('parts_replaced')->nullable();
                $table->string('before_photo_path')->nullable();
                $table->string('after_photo_path')->nullable();
                $table->text('customer_signature')->nullable();
                $table->boolean('next_visit_required')->default(false);
                $table->timestamp('next_visit_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_installations')) {
            Schema::create('service_installations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_ticket_id')->unique()->constrained()->cascadeOnDelete();
                $table->enum('installation_type', ['free', 'chargeable', 'oem', 'avit_digital'])->default('free');
                $table->string('project_name')->nullable();
                $table->text('site_address')->nullable();
                $table->decimal('quotation_amount', 14, 2)->nullable();
                $table->boolean('customer_approved')->default(false);
                $table->timestamp('customer_approved_at')->nullable();
                $table->decimal('installation_charges', 14, 2)->nullable();
                $table->text('material_consumed')->nullable();
                $table->string('completion_certificate_path')->nullable();
                $table->boolean('customer_acknowledged')->default(false);
                $table->timestamp('customer_acknowledged_at')->nullable();
                $table->text('customer_signature')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_charges')) {
            Schema::create('service_charges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_ticket_id')->unique()->constrained()->cascadeOnDelete();
                $table->boolean('is_paid_service')->default(false);
                $table->decimal('technician_charges', 14, 2)->default(0);
                $table->decimal('transportation_charges', 14, 2)->default(0);
                $table->decimal('parts_total', 14, 2)->default(0);
                $table->decimal('grand_total', 14, 2)->default(0);
                $table->boolean('customer_approved')->default(false);
                $table->timestamp('customer_approved_at')->nullable();
                $table->enum('payment_status', ['pending', 'partial', 'paid', 'waived'])->default('pending');
                $table->decimal('paid_amount', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_charge_lines')) {
            Schema::create('service_charge_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_charge_id')->constrained()->cascadeOnDelete();
                $table->enum('line_type', ['spare', 'labour', 'other'])->default('spare');
                $table->string('item_name');
                $table->decimal('quantity', 12, 4)->default(1);
                $table->decimal('rate', 14, 2)->default(0);
                $table->decimal('amount', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_warranties')) {
            Schema::create('service_warranties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_ticket_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('oem_centre_id')->nullable()->constrained('service_oem_centres')->nullOnDelete();
                $table->string('warranty_claim_no')->nullable();
                $table->string('oem_ticket_no')->nullable();
                $table->enum('oem_status', ['pending', 'approved', 'rejected', 'closed'])->default('pending');
                $table->text('parts_covered')->nullable();
                $table->boolean('chargeable')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('document_number_series')) {
            $exists = DB::table('document_number_series')
                ->where('document_type', 'SRV')
                ->whereNull('company_id')
                ->exists();
            if (! $exists) {
                $now = now();
                DB::table('document_number_series')->insert([
                    'company_id' => null,
                    'document_type' => 'SRV',
                    'name' => 'Service Ticket',
                    'prefix' => 'AVITSRV',
                    'suffix' => null,
                    'include_financial_year' => true,
                    'financial_year_style' => 'yy_yy',
                    'separator' => '-',
                    'padding' => 4,
                    'starting_number' => 1,
                    'prefix_zero' => true,
                    'numbering_method' => 'automatic',
                    'restart_each_fy' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Default SLA policies (global)
        if (Schema::hasTable('service_sla_policies')) {
            foreach ([
                ['critical', 24, 12],
                ['high', 48, 24],
                ['normal', 72, 48],
                ['low', 168, 120],
            ] as [$priority, $hours, $escalate]) {
                $exists = DB::table('service_sla_policies')
                    ->whereNull('company_id')
                    ->where('priority', $priority)
                    ->exists();
                if (! $exists) {
                    DB::table('service_sla_policies')->insert([
                        'company_id' => null,
                        'priority' => $priority,
                        'resolution_hours' => $hours,
                        'escalate_after_hours' => $escalate,
                        'escalate_to_user_id' => null,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_warranties');
        Schema::dropIfExists('service_charge_lines');
        Schema::dropIfExists('service_charges');
        Schema::dropIfExists('service_installations');
        Schema::dropIfExists('service_visits');
        Schema::dropIfExists('service_ticket_activities');
        Schema::dropIfExists('service_ticket_assignments');
        Schema::dropIfExists('service_ticket_attachments');
        Schema::dropIfExists('service_tickets');
        Schema::dropIfExists('service_sla_policies');
        Schema::dropIfExists('service_oem_centres');
    }
};
