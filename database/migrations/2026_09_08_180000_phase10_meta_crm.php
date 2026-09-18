<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lead_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('external_campaign_id')->nullable()->index();
            $table->string('platform', 40)->nullable(); // facebook|instagram|meta|other
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('meta_lead_forms', function (Blueprint $table) {
            $table->id();
            $table->string('form_id')->unique();
            $table->string('form_name')->nullable();
            $table->string('page_id')->nullable();
            $table->foreignId('lead_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('meta_lead_form_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_lead_id')->nullable()->unique();
            $table->string('name');
            $table->string('mobile', 20)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('organization')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('interested_product')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 40)->default('new'); // new|contacted|qualified|unqualified|converted|lost
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_followup_at')->nullable();
            $table->decimal('lead_score', 8, 2)->nullable();
            $table->string('lost_reason')->nullable();
            $table->foreignId('converted_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->json('meta_payload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type', 40); // note|call|whatsapp|email|meeting|status_change
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 40)->default('manual'); // manual|round_robin|branch
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('due_at');
            $table->string('channel', 40)->nullable(); // call|whatsapp|email|meeting
            $table->string('status', 30)->default('pending'); // pending|done|skipped|overdue
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('meta_lead_logs', function (Blueprint $table) {
            $table->id();
            $table->string('external_lead_id')->nullable()->index();
            $table->string('status', 30)->default('received'); // received|processed|duplicate|failed|retried
            $table->unsignedInteger('attempts')->default(0);
            $table->json('payload')->nullable();
            $table->text('result')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_lead_logs');
        Schema::dropIfExists('lead_conversions');
        Schema::dropIfExists('lead_followups');
        Schema::dropIfExists('lead_assignments');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('meta_lead_forms');
        Schema::dropIfExists('lead_campaigns');
        Schema::dropIfExists('lead_sources');
    }
};
