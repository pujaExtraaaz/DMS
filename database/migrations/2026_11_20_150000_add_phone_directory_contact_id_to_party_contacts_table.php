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
                if (! Schema::hasColumn('party_contacts', 'phone_directory_contact_id')) {
                    $table->foreignId('phone_directory_contact_id')
                        ->nullable()
                        ->after('customer_id')
                        ->constrained('phone_directory_contacts')
                        ->nullOnDelete();
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
                if (Schema::hasColumn('party_contacts', 'phone_directory_contact_id')) {
                    $table->dropConstrainedForeignId('phone_directory_contact_id');
                }
            });
        }
    }
};
