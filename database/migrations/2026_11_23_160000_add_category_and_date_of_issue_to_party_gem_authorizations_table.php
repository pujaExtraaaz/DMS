<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('party_gem_authorizations')) {
            return;
        }

        Schema::table('party_gem_authorizations', function (Blueprint $table) {
            if (! Schema::hasColumn('party_gem_authorizations', 'category')) {
                $table->string('category', 255)->nullable()->after('authorization_number');
            }
            if (! Schema::hasColumn('party_gem_authorizations', 'date_of_issue')) {
                $table->date('date_of_issue')->nullable()->after('category');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('party_gem_authorizations')) {
            return;
        }

        Schema::table('party_gem_authorizations', function (Blueprint $table) {
            if (Schema::hasColumn('party_gem_authorizations', 'date_of_issue')) {
                $table->dropColumn('date_of_issue');
            }
            if (Schema::hasColumn('party_gem_authorizations', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
