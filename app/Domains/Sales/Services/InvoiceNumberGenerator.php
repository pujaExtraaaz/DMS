<?php

namespace App\Domains\Sales\Services;

use App\Domains\Sales\Models\Invoice;
use Illuminate\Support\Str;

class InvoiceNumberGenerator
{
    public function generate(?string $prefix = 'INV'): string
    {
        $date = now()->format('Ymd');
        $pattern = "{$prefix}-{$date}-%";

        $lastSequence = Invoice::query()
            ->where('invoice_no', 'like', $pattern)
            ->orderByDesc('invoice_no')
            ->value('invoice_no');

        $sequence = 1;

        if ($lastSequence) {
            $sequence = (int) Str::afterLast($lastSequence, '-') + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
