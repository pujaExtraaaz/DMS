<?php

namespace App\Http\Controllers;

use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Organization\Models\Company;
use App\Support\QrCodeRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceQrController extends Controller
{

    public function ewayBill(string $token): View
    {
        $eWayBill = \App\Domains\Sales\Models\EWayBill::query()
            ->where('qr_token', $token)
            ->with([
                'invoice.customer',
                'invoice.items.product',
                'invoice.items.uom',
            ])
            ->firstOrFail();

        return view('sales.invoices.e-way-bill', [
            'invoice' => $eWayBill->invoice,
            'eWayBill' => $eWayBill,
            'qrPublicView' => true,
        ]);
    }
    
    public function show(Request $request, string $type, string $token): View
    {
        if ($type === 'sales') {
            $invoice = Invoice::query()
                ->where('qr_token', $token)
                ->with([
                    'customer',
                    'items.product',
                    'items.uom',
                    'eInvoice',
                ])
                ->firstOrFail();

            $company = Company::query()->first();

            $url = route('invoice.qr', [
                'type' => 'sales',
                'token' => $invoice->qr_token,
            ]);

            $qrDataUri = QrCodeRenderer::dataUri($url, 180);

            return view('sales.invoices.pdf', [
                'invoice' => $invoice,
                'company' => $company,
                'invoiceQrDataUri' => $qrDataUri,
                'signedQrDataUri' => null,
                'upiQrDataUri' => null,
            ]);
        }

        if ($type === 'purchase') {
            $invoice = PurchaseInvoice::query()
                ->where('qr_token', $token)
                ->with([
                    'items.product',
                    'items.uom',
                    'supplier',
                    'warehouse',
                    'purchaseOrder',
                    'creator',
                ])
                ->firstOrFail();

            $company = Company::query()->first();

            $url = route('invoice.qr', [
                'type' => 'purchase',
                'token' => $invoice->qr_token,
            ]);

            $qrDataUri = QrCodeRenderer::dataUri($url, 180);

            return view('purchasing.invoices.preview', [
                'invoice' => $invoice,
                'company' => $company,
                'invoiceQrDataUri' => $qrDataUri,
                'qrPublicView' => true,
            ]);
        }

        abort(404);
    }
}