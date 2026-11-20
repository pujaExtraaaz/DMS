<?php

use App\Domains\Organization\Models\DocumentNumberSeries;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_number_series')) {
            return;
        }

        $exists = DocumentNumberSeries::query()
            ->where('document_type', 'QTN')
            ->exists();

        if ($exists) {
            return;
        }

        DocumentNumberSeries::create([
            'company_id' => null,
            'document_type' => 'QTN',
            'name' => 'Sales Quotation',
            'prefix' => 'QTN',
            'suffix' => null,
            'include_financial_year' => true,
            'financial_year_style' => 'yy_yy',
            'separator' => '-',
            'padding' => 4,
            'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC,
            'restart_each_fy' => true,
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('document_number_series')) {
            return;
        }

        DocumentNumberSeries::query()
            ->where('document_type', 'QTN')
            ->where('prefix', 'QTN')
            ->whereNull('company_id')
            ->delete();
    }
};
