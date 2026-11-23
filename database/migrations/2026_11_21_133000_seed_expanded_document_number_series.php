<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed default voucher numbering series for document types used by DMS modules.
 * Skips types that already have a global (company_id null) series.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_number_series')) {
            return;
        }

        $defaults = [
            'PO' => ['Purchase Order', 'AVITPO'],
            'PI' => ['Purchase Invoice', 'AVITPI'],
            'GRN' => ['Goods Receipt', 'AVITGRN'],
            'RN' => ['Receipt Note', 'AVITRN'],
            'SO' => ['Sales Order', 'AVITSO'],
            'ORD' => ['Order', 'AVITORD'],
            'QTN' => ['Quotation', 'AVITQTN'],
            'INV' => ['Tax Invoice', 'AVITINV'],
            'PRF' => ['Proforma Invoice', 'AVITPRF'],
            'CN' => ['Credit Note', 'AVITCN'],
            'DN' => ['Debit Note', 'AVITDN'],
            'PAY' => ['Payment', 'AVITPAY'],
            'REC' => ['Receipt', 'AVITREC'],
            'CON' => ['Contra', 'AVITCON'],
            'JRN' => ['Journal', 'AVITJRN'],
            'ADJ' => ['Stock Journal', 'AVITADJ'],
            'TRF' => ['Stock Transfer', 'AVITTRF'],
            'PHY' => ['Physical Stock', 'AVITPHY'],
            'RJI' => ['Rejections In', 'AVITRJI'],
            'RJO' => ['Rejections Out', 'AVITRJO'],
            'DC' => ['Delivery Challan', 'AVITDC'],
            'DC-RET' => ['Delivery Challan Return', 'AVITDCR'],
            'FRT' => ['Freight Bill', 'AVITFRT'],
            'LC' => ['Landed Cost', 'AVITLC'],
            'JWO' => ['Job Work Out Order', 'AVITJWO'],
            'INT' => ['Interest', 'AVITINT'],
            'DEAL' => ['Deal', 'AVITDEAL'],
            'SCH' => ['Scheme', 'AVITSCH'],
            'SNT' => ['Stock Reclassification', 'AVITSNT'],
        ];

        $now = now();

        foreach ($defaults as $type => [$name, $prefix]) {
            $exists = DB::table('document_number_series')
                ->where('document_type', $type)
                ->whereNull('company_id')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('document_number_series')->insert([
                'company_id' => null,
                'document_type' => $type,
                'name' => $name,
                'prefix' => $prefix,
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

    public function down(): void
    {
        // Keep seeded series — they may already be customized in production.
    }
};
