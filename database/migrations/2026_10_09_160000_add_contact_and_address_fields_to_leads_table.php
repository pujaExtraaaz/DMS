<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'title')) {
                $table->string('title', 100)->nullable()->after('name');
            }
            if (! Schema::hasColumn('leads', 'company_name')) {
                $table->string('company_name', 255)->nullable()->after('organization');
            }
            if (! Schema::hasColumn('leads', 'secondary_email')) {
                $table->string('secondary_email', 255)->nullable()->after('email');
            }
            if (! Schema::hasColumn('leads', 'secondary_mobile')) {
                $table->string('secondary_mobile', 50)->nullable()->after('mobile');
            }
            if (! Schema::hasColumn('leads', 'phone')) {
                $table->string('phone', 50)->nullable()->after('secondary_mobile');
            }
            if (! Schema::hasColumn('leads', 'landline')) {
                $table->string('landline', 50)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('leads', 'tag')) {
                $table->string('tag', 255)->nullable()->after('landline');
            }
            if (! Schema::hasColumn('leads', 'sub_category_id')) {
                $table->foreignId('sub_category_id')->nullable()->after('tag')->constrained('sub_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('leads', 'sub_category')) {
                $table->string('sub_category', 255)->nullable()->after('sub_category_id');
            }
            if (! Schema::hasColumn('leads', 'street')) {
                $table->text('street')->nullable()->after('sub_category');
            }
            if (! Schema::hasColumn('leads', 'zip')) {
                $table->string('zip', 30)->nullable()->after('state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'sub_category_id')) {
                $table->dropForeign(['sub_category_id']);
                $table->dropColumn('sub_category_id');
            }
            $cols = [
                'title',
                'company_name',
                'secondary_email',
                'secondary_mobile',
                'phone',
                'landline',
                'tag',
                'sub_category',
                'street',
                'zip',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('leads', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

