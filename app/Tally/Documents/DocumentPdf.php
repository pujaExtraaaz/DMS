<?php

namespace Tally\Documents;

use Tally\Models\Voucher;
use Tally\Preferences\PreferenceStore;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DocumentPdf
{
    public function __construct(private readonly PreferenceStore $preferences) {}

    /**
     * @param  array<string, mixed>  $document
     */
    public function trading(array $document): Response
    {
        return $this->download('documents.pdf', ['document' => $document], $document['number'].'.pdf');
    }

    public function voucher(Voucher $voucher): Response
    {
        $voucher->loadMissing(['company', 'branch', 'financialYear', 'entries.ledger']);

        return $this->download('documents.voucher-pdf', [
            'voucher' => $voucher,
            'dateFormat' => $this->preferences->dateFormat($voucher->company, request()->user()),
        ], $voucher->voucher_number.'.pdf');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function download(string $view, array $data, string $filename): Response
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4');
        $pdf->setOption('isPhpEnabled', true);

        return $pdf->download($filename);
    }
}
