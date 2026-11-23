<?php

namespace App\Domains\Sales\Services;

use App\Support\DocumentNumberService;
use Illuminate\Support\Carbon;

class InvoiceNumberGenerator
{
    public function __construct(
        protected DocumentNumberService $documentNumberService
    ) {}

    public function generate(?string $prefix = 'INV', ?Carbon $date = null, ?int $companyId = null): string
    {
        $type = strtoupper(trim((string) ($prefix ?: 'INV'))) ?: 'INV';

        return $this->documentNumberService->nextDocument(
            documentType: $type,
            date: $date ?? now(),
            padding: 4,
            companyId: $companyId,
        );
    }
}