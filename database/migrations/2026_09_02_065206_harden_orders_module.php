<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * document_sequences already exists in the current database.
         *
         * DO NOT recreate it here.
         */

        /*
         * audit_logs is intentionally NOT created.
         *
         * DMS uses actor-name fields on the business records.
         */

        /*
         * Add indexes only when they do not already exist.
         */
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'created_by_name')) {
                $table->string('created_by_name', 100)
                    ->nullable()
                    ->after('salesperson_id');
            }

            if (! Schema::hasColumn('orders', 'updated_by_name')) {
                $table->string('updated_by_name', 100)
                    ->nullable()
                    ->after('created_by_name');
            }

            if (! Schema::hasColumn('orders', 'approved_by_name')) {
                $table->string('approved_by_name', 100)
                    ->nullable()
                    ->after('approved_by');
            }

            if (! Schema::hasColumn('orders', 'converted_by_name')) {
                $table->string('converted_by_name', 100)
                    ->nullable()
                    ->after('approved_at');
            }

            if (! Schema::hasColumn('orders', 'cancelled_by_name')) {
                $table->string('cancelled_by_name', 100)
                    ->nullable()
                    ->after('converted_by_name');
            }
        });

        /*
         * Existing databases may already contain these indexes
         * because an earlier migration partially succeeded.
         *
         * Therefore this migration does not recreate them.
         */

        /*
         * IMPORTANT:
         *
         * Do NOT add invoices_order_id_unique yet.
         *
         * Existing duplicate data must be reconciled first.
         */
    }

    public function down(): void
    {
        /*
         * Intentionally conservative.
         *
         * This migration may run against databases where some of
         * these columns were created by separate migrations.
         *
         * Therefore no destructive rollback is performed here.
         */
    }
};