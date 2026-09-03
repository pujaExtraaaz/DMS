<?php

namespace App\Domains\Sales\Services;

use App\Support\DocumentNumberService;

class InvoiceNumberGenerator
{
    public function __construct(
        protected DocumentNumberService $documentNumberService
    ) {}

    public function generate(?string $prefix = 'INV'): string
    {
        return $this->documentNumberService->next(
            prefix: $prefix ?? 'INV',
            date: now(),
            padding: 4,
        );
    }
}