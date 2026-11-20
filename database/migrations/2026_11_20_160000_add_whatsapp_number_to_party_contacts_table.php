<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('party_contacts')) {
            Schema::table('party_contacts', function (Blueprint $table) {
                if (! Schema::hasColumn('party_contacts', 'whatsapp_number')) {
                    $table->string('whatsapp_number', 30)->nullable()->after('alternate_phone');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('party_contacts')) {
            Schema::table('party_contacts', function (Blueprint $table) {
                if (Schema::hasColumn('party_contacts', 'whatsapp_number')) {
                    $table->dropColumn('whatsapp_number');
                }
            });
        }
    }
};
