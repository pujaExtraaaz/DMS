<?php

namespace App\Support;

use App\Domains\Organization\Models\Company;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentExporter
{
    /**
     * Convert a numeric amount into Indian Currency Words.
     * E.g. 154250.50 => "Rupees One Lakh Fifty Four Thousand Two Hundred Fifty and Fifty Paise Only"
     */
    public static function numberToIndianWords(float|int|string|null $amount): string
    {
        $amount = (float) ($amount ?? 0);
        if ($amount < 0) {
            return 'Minus ' . self::numberToIndianWords(abs($amount));
        }

        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        if ($rupees === 0 && $paise === 0) {
            return 'Rupees Zero Only';
        }

        $words = [];
        if ($rupees > 0) {
            $words[] = self::convertNumberToWords($rupees);
        }

        $result = 'Rupees ' . implode(' ', $words);

        if ($paise > 0) {
            $result .= ($rupees > 0 ? ' and ' : ' ') . self::convertNumberToWords($paise) . ' Paise';
        }

        return trim($result) . ' Only';
    }

    private static function convertNumberToWords(int $num): string
    {
        if ($num === 0) {
            return 'Zero';
        }

        $units = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
        ];

        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        ];

        $parts = [];

        // Crores (1,00,00,000)
        if ($num >= 10000000) {
            $crores = (int) floor($num / 10000000);
            $parts[] = self::convertNumberToWords($crores) . ' Crore';
            $num %= 10000000;
        }

        // Lakhs (1,00,000)
        if ($num >= 100000) {
            $lakhs = (int) floor($num / 100000);
            $parts[] = self::convertNumberToWords($lakhs) . ' Lakh';
            $num %= 100000;
        }

        // Thousands (1,000)
        if ($num >= 1000) {
            $thousands = (int) floor($num / 1000);
            $parts[] = self::convertNumberToWords($thousands) . ' Thousand';
            $num %= 1000;
        }

        // Hundreds (100)
        if ($num >= 100) {
            $hundreds = (int) floor($num / 100);
            $parts[] = $units[$hundreds] . ' Hundred';
            $num %= 100;
        }

        // Below 100
        if ($num > 0) {
            if ($num < 20) {
                $parts[] = $units[$num];
            } else {
                $t = (int) floor($num / 10);
                $u = $num % 10;
                $parts[] = $tens[$t] . ($u > 0 ? ' ' . $units[$u] : '');
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Export view as PDF download.
     */
    public static function exportPdf(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait'): Response
    {
        $pdf = Pdf::loadView($view, $data)->setPaper($paper, $orientation);

        return $pdf->download($filename);
    }

    /**
     * Export data as UTF-8 CSV with BOM for universal Excel compatibility.
     */
    public static function exportCsv(string $filename, array $headers, iterable $rows, array $meta = [], array $totals = []): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $meta, $totals) {
            $out = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fwrite($out, "\xEF\xBB\xBF");

            if (!empty($meta)) {
                foreach ($meta as $key => $val) {
                    if (is_int($key)) {
                        fputcsv($out, (array) $val);
                    } else {
                        fputcsv($out, [$key, $val]);
                    }
                }
                fputcsv($out, []); // Blank separator
            }

            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, is_array($row) ? $row : (array) $row);
            }

            if (!empty($totals)) {
                fputcsv($out, []);
                foreach ($totals as $key => $val) {
                    if (is_int($key)) {
                        fputcsv($out, (array) $val);
                    } else {
                        fputcsv($out, [$key, $val]);
                    }
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Export data as styled Excel (.xlsx) spreadsheet.
     */
    public static function exportExcel(
        string $filename,
        string $sheetTitle,
        array $headers,
        iterable $rows,
        array $meta = [],
        array $totals = []
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        $currentRow = 1;

        // Metadata block (if provided)
        if (!empty($meta)) {
            // Title
            if (isset($meta['_title'])) {
                $sheet->setCellValue('A' . $currentRow, $meta['_title']);
                $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(14);
                unset($meta['_title']);
                $currentRow += 2;
            }

            foreach ($meta as $k => $v) {
                if (is_int($k)) {
                    $sheet->setCellValue('A' . $currentRow, $v);
                } else {
                    $sheet->setCellValue('A' . $currentRow, $k . ':');
                    $sheet->setCellValue('B' . $currentRow, $v);
                    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
                }
                $currentRow++;
            }
            $currentRow++; // blank row
        }

        // Table Header
        $headerStartRow = $currentRow;
        $colIndex = 1;
        foreach ($headers as $headerText) {
            $sheet->setCellValueByColumnAndRow($colIndex, $currentRow, $headerText);
            $colIndex++;
        }
        $maxColIndex = count($headers);

        // Header style
        $headerRange = 'A' . $headerStartRow . ':' . self::columnIndexToLetter($maxColIndex) . $headerStartRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Dark slate
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension($headerStartRow)->setRowHeight(24);

        $currentRow++;
        $dataStartRow = $currentRow;

        // Data Rows
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ((array) $row as $val) {
                $sheet->setCellValueByColumnAndRow($colIndex, $currentRow, $val);
                $colIndex++;
            }
            $currentRow++;
        }
        $dataEndRow = $currentRow - 1;

        // Borders for table data
        if ($dataEndRow >= $dataStartRow) {
            $tableRange = 'A' . $headerStartRow . ':' . self::columnIndexToLetter($maxColIndex) . $dataEndRow;
            $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        }

        // Totals block
        if (!empty($totals)) {
            $currentRow++;
            foreach ($totals as $k => $v) {
                if (is_int($k)) {
                    $sheet->setCellValue('A' . $currentRow, $v);
                    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
                } else {
                    $sheet->setCellValue('A' . $currentRow, $k . ':');
                    $sheet->setCellValue('B' . $currentRow, $v);
                    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
                    $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true);
                }
                $currentRow++;
            }
        }

        // Auto-fit column widths
        for ($col = 1; $col <= $maxColIndex; $col++) {
            $letter = self::columnIndexToLetter($col);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private static function columnIndexToLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $modulo = ($index - 1) % 26;
            $letter = chr(65 + $modulo) . $letter;
            $index = (int) (($index - $modulo) / 26);
        }

        return $letter;
    }

    /**
     * Export Single Purchase Order
     */
    public static function exportPurchaseOrder(PurchaseOrder $order, string $format): Response|StreamedResponse
    {
        $order->load(['supplier', 'warehouse', 'creator', 'approver', 'items.product', 'items.uom']);
        $company = $order->company ?? Company::query()->find(auth()->user()?->company_id) ?? Company::query()->first();
        $filename = 'PO-' . $order->po_no . '.' . ($format === 'excel' ? 'xlsx' : $format);

        if ($format === 'pdf') {
            return self::exportPdf('purchasing.orders.preview', [
                'order' => $order,
                'company' => $company,
                'isPdf' => true,
            ], $filename);
        }

        $headers = ['#', 'Product', 'Batch Name', 'UOM', 'Ordered Qty', 'Unit Cost (₹)', 'Tax %', 'CGST %', 'SGST %', 'Line Total (₹)'];
        $rows = [];
        $i = 1;
        foreach ($order->items as $item) {
            $rows[] = [
                $i++,
                $item->product?->name ?? '—',
                $item->batch_no ?? $item->batch_name ?? '—',
                $item->uom?->code ?? '—',
                number_format((float) $item->quantity, 2, '.', ''),
                number_format((float) $item->unit_cost, 2, '.', ''),
                number_format((float) $item->tax_percent, 2, '.', ''),
                number_format((float) $item->cgst_percent, 2, '.', ''),
                number_format((float) $item->sgst_percent, 2, '.', ''),
                number_format((float) $item->line_total, 2, '.', ''),
            ];
        }

        $meta = [
            '_title' => 'PURCHASE ORDER - ' . $order->po_no,
            'Company' => $company?->name ?? config('app.name'),
            'Company GSTIN' => $company?->gstin ?? '—',
            'Supplier' => $order->supplier?->name ?? '—',
            'Supplier GSTIN' => $order->supplier?->gstin ?? '—',
            'PO Number' => $order->po_no,
            'PO Date' => $order->po_date?->format('d/m/Y') ?? '—',
            'Expected Date' => $order->expected_date?->format('d/m/Y') ?? '—',
            'Status' => ucfirst(str_replace('_', ' ', $order->status)),
            'Billing Address' => preg_replace('/\s+/', ' ', trim($order->billing_address ?: ($order->supplier?->address ?: '—'))),
            'Delivery Address' => preg_replace('/\s+/', ' ', trim($order->shipping_address ?: ($order->billing_address ?: ($order->supplier?->address ?: '—')))),
        ];

        $totals = [
            'Subtotal (Taxable)' => '₹ ' . number_format((float) $order->subtotal, 2),
            'Tax Amount' => '₹ ' . number_format((float) $order->tax_amount, 2),
            'Grand Total' => '₹ ' . number_format((float) $order->grand_total, 2),
            'Amount in Words' => self::numberToIndianWords($order->grand_total),
        ];

        if ($format === 'csv') {
            return self::exportCsv($filename, $headers, $rows, $meta, $totals);
        }

        return self::exportExcel($filename, 'Purchase Order', $headers, $rows, $meta, $totals);
    }

    /**
     * Export Single Purchase Invoice
     */
    public static function exportPurchaseInvoice(PurchaseInvoice $invoice, string $format): Response|StreamedResponse
    {
        $invoice->load(['supplier', 'warehouse', 'purchaseOrder', 'items.product', 'items.uom']);
        $company = $invoice->company ?? Company::query()->find(auth()->user()?->company_id) ?? Company::query()->first();
        $filename = 'Purchase-Invoice-' . $invoice->invoice_no . '.' . ($format === 'excel' ? 'xlsx' : $format);

        if ($format === 'pdf') {
            $url = route('invoice.qr', ['type' => 'purchase', 'token' => $invoice->qr_token]);
            $invoiceQrDataUri = QrCodeRenderer::dataUri($url, 180);

            return self::exportPdf('purchasing.invoices.preview', [
                'invoice' => $invoice,
                'company' => $company,
                'invoiceQrDataUri' => $invoiceQrDataUri,
                'isPdf' => true,
            ], $filename);
        }

        $headers = ['#', 'Product', 'Batch No', 'Qty', 'Unit Cost (₹)', 'Tax %', 'CGST %', 'SGST %', 'CGST (₹)', 'SGST (₹)', 'Line Total (₹)'];
        $rows = [];
        $i = 1;
        foreach ($invoice->items as $item) {
            $rows[] = [
                $i++,
                $item->product?->name ?? '—',
                $item->batch_no ?? '—',
                number_format((float) $item->quantity, 2, '.', ''),
                number_format((float) $item->unit_cost, 2, '.', ''),
                number_format((float) $item->tax_percent, 2, '.', ''),
                number_format((float) $item->cgst_percent, 2, '.', ''),
                number_format((float) $item->sgst_percent, 2, '.', ''),
                number_format((float) $item->cgst_amount, 2, '.', ''),
                number_format((float) $item->sgst_amount, 2, '.', ''),
                number_format((float) $item->line_total, 2, '.', ''),
            ];
        }

        $meta = [
            '_title' => 'PURCHASE INVOICE - ' . $invoice->invoice_no,
            'Company' => $company?->name ?? config('app.name'),
            'Company GSTIN' => $company?->gstin ?? '—',
            'Supplier' => $invoice->supplier?->name ?? '—',
            'Supplier GSTIN' => $invoice->supplier?->gstin ?? '—',
            'Invoice No' => $invoice->invoice_no,
            'Supplier Inv No' => $invoice->supplier_invoice_no ?? '—',
            'Invoice Date' => $invoice->invoice_date?->format('d/m/Y') ?? '—',
            'Due Date' => $invoice->due_date?->format('d/m/Y') ?? '—',
            'PO Reference' => $invoice->purchaseOrder?->po_no ?? '—',
            'Status' => ucfirst($invoice->status),
            'Billing Address' => preg_replace('/\s+/', ' ', trim($invoice->billing_address ?: ($invoice->supplier?->address ?: '—'))),
            'Delivery Address' => preg_replace('/\s+/', ' ', trim($invoice->shipping_address ?: ($invoice->billing_address ?: ($invoice->supplier?->address ?: '—')))),
        ];

        $totals = [
            'Subtotal (Taxable)' => '₹ ' . number_format((float) $invoice->subtotal, 2),
            'Tax Amount' => '₹ ' . number_format((float) $invoice->tax_amount, 2),
            'Grand Total' => '₹ ' . number_format((float) $invoice->grand_total, 2),
            'Amount in Words' => self::numberToIndianWords($invoice->grand_total),
        ];

        if ($format === 'csv') {
            return self::exportCsv($filename, $headers, $rows, $meta, $totals);
        }

        return self::exportExcel($filename, 'Purchase Invoice', $headers, $rows, $meta, $totals);
    }

    /**
     * Export Single Sales Tax Invoice
     */
    public static function exportSalesInvoice(Invoice $invoice, string $format): Response|StreamedResponse
    {
        $invoice->load(['customer', 'salesperson', 'items.product', 'items.uom', 'eInvoice', 'eWayBill']);
        $company = Company::query()->find(auth()->user()?->company_id) ?? Company::query()->first();
        $filename = 'Tax-Invoice-' . $invoice->invoice_no . '.' . ($format === 'excel' ? 'xlsx' : $format);

        if ($format === 'pdf') {
            $invoiceUrl = route('invoice.qr', ['type' => 'sales', 'token' => $invoice->qr_token]);
            $invoiceQrDataUri = QrCodeRenderer::dataUri($invoiceUrl, 180);

            $upiQrDataUri = null;
            if ($company && filled($company->upi_id)) {
                $upiUri = QrCodeRenderer::upiIntent(
                    $company->upi_id,
                    $company->name,
                    (float) $invoice->grand_total,
                    'Inv ' . $invoice->invoice_no,
                    $invoice->invoice_no
                );
                $upiQrDataUri = QrCodeRenderer::dataUri($upiUri, 160);
            }

            return self::exportPdf('sales.invoices.preview', [
                'invoice' => $invoice,
                'company' => $company,
                'invoiceQrDataUri' => $invoiceQrDataUri,
                'upiQrDataUri' => $upiQrDataUri,
                'isPdf' => true,
            ], $filename);
        }

        $headers = ['#', 'Product', 'HSN/SAC', 'Batch No', 'Qty', 'UOM', 'Rate (₹)', 'Discount (₹)', 'Tax (₹)', 'Line Total (₹)'];
        $rows = [];
        $i = 1;
        foreach ($invoice->items as $item) {
            $rows[] = [
                $i++,
                $item->product?->name ?? '—',
                $item->hsn_code ?? $item->product?->hsn_code ?? '—',
                $item->batch_no ?? '—',
                number_format((float) $item->quantity, 2, '.', ''),
                $item->uom?->code ?? '—',
                number_format((float) $item->unit_price, 2, '.', ''),
                number_format((float) $item->discount_amount, 2, '.', ''),
                number_format((float) $item->tax_amount, 2, '.', ''),
                number_format((float) $item->line_total, 2, '.', ''),
            ];
        }

        $meta = [
            '_title' => 'TAX INVOICE - ' . $invoice->invoice_no,
            'Company' => $company?->name ?? config('app.name'),
            'Company GSTIN' => $company?->gstin ?? '—',
            'Customer' => $invoice->customer?->name ?? '—',
            'Customer GSTIN' => $invoice->customer?->gstin ?? '—',
            'Invoice No' => $invoice->invoice_no,
            'Invoice Date' => $invoice->invoice_date?->format('d/m/Y') ?? '—',
            'Due Date' => $invoice->due_date?->format('d/m/Y') ?? '—',
            'Status' => ucfirst($invoice->status),
            'Billing Address' => preg_replace('/\s+/', ' ', trim($invoice->billing_address ?: ($invoice->customer?->address ?: '—'))),
            'Delivery Address' => preg_replace('/\s+/', ' ', trim($invoice->shipping_address ?: ($invoice->billing_address ?: ($invoice->customer?->address ?: '—')))),
        ];

        $totals = [
            'Subtotal (Taxable)' => '₹ ' . number_format((float) $invoice->subtotal, 2),
            'Total Discount' => '₹ ' . number_format((float) $invoice->discount_amount, 2),
            'Total Tax' => '₹ ' . number_format((float) $invoice->tax_amount, 2),
            'Grand Total' => '₹ ' . number_format((float) $invoice->grand_total, 2),
            'Paid Amount' => '₹ ' . number_format((float) $invoice->paid_amount, 2),
            'Balance Due' => '₹ ' . number_format((float) ($invoice->grand_total - $invoice->paid_amount), 2),
            'Amount in Words' => self::numberToIndianWords($invoice->grand_total),
        ];

        if ($format === 'csv') {
            return self::exportCsv($filename, $headers, $rows, $meta, $totals);
        }

        return self::exportExcel($filename, 'Tax Invoice', $headers, $rows, $meta, $totals);
    }

    /**
     * Export Purchase Orders Listing
     */
    public static function exportPurchaseOrdersListing(Collection|iterable $orders, string $format): Response|StreamedResponse
    {
        $filename = 'Purchase-Orders-' . now()->format('Ymd-His') . '.' . ($format === 'excel' ? 'xlsx' : $format);
        $headers = ['PO No', 'Date', 'Expected Date', 'Supplier', 'Warehouse', 'Status', 'Subtotal (₹)', 'Tax Amount (₹)', 'Grand Total (₹)'];
        $rows = [];
        $totalSum = 0;

        foreach ($orders as $o) {
            $totalSum += (float) $o->grand_total;
            $rows[] = [
                $o->po_no,
                $o->po_date?->format('d/m/Y') ?? '',
                $o->expected_date?->format('d/m/Y') ?? '',
                $o->supplier?->name ?? '',
                $o->warehouse?->name ?? '',
                ucfirst(str_replace('_', ' ', $o->status)),
                number_format((float) $o->subtotal, 2, '.', ''),
                number_format((float) $o->tax_amount, 2, '.', ''),
                number_format((float) $o->grand_total, 2, '.', ''),
            ];
        }

        $meta = [
            '_title' => 'Purchase Orders Report',
            'Exported At' => now()->format('d M Y H:i:s'),
            'Total Records' => count($rows),
        ];

        $totals = [
            'Total Amount' => '₹ ' . number_format($totalSum, 2),
        ];

        if ($format === 'csv') {
            return self::exportCsv($filename, $headers, $rows, $meta, $totals);
        }

        if ($format === 'pdf') {
            return ReportExporter::pdf($filename, 'Purchase Orders Report', $headers, $rows, [
                'Generated' => now()->format('d M Y H:i'),
                'Total Amount' => '₹ ' . number_format($totalSum, 2),
            ]);
        }

        return self::exportExcel($filename, 'Purchase Orders', $headers, $rows, $meta, $totals);
    }

    /**
     * Export Purchase Invoices Listing
     */
    public static function exportPurchaseInvoicesListing(Collection|iterable $invoices, string $format): Response|StreamedResponse
    {
        $filename = 'Purchase-Invoices-' . now()->format('Ymd-His') . '.' . ($format === 'excel' ? 'xlsx' : $format);
        $headers = ['Invoice No', 'Supplier Inv No', 'Date', 'Due Date', 'Supplier', 'Warehouse', 'Status', 'Taxable (₹)', 'Tax (₹)', 'Grand Total (₹)'];
        $rows = [];
        $totalSum = 0;

        foreach ($invoices as $inv) {
            $totalSum += (float) $inv->grand_total;
            $rows[] = [
                $inv->invoice_no,
                $inv->supplier_invoice_no ?? '',
                $inv->invoice_date?->format('d/m/Y') ?? '',
                $inv->due_date?->format('d/m/Y') ?? '',
                $inv->supplier?->name ?? '',
                $inv->warehouse?->name ?? '',
                ucfirst($inv->status),
                number_format((float) $inv->subtotal, 2, '.', ''),
                number_format((float) $inv->tax_amount, 2, '.', ''),
                number_format((float) $inv->grand_total, 2, '.', ''),
            ];
        }

        $meta = [
            '_title' => 'Purchase Invoices Report',
            'Exported At' => now()->format('d M Y H:i:s'),
            'Total Records' => count($rows),
        ];

        $totals = [
            'Total Amount' => '₹ ' . number_format($totalSum, 2),
        ];

        if ($format === 'csv') {
            return self::exportCsv($filename, $headers, $rows, $meta, $totals);
        }

        if ($format === 'pdf') {
            return ReportExporter::pdf($filename, 'Purchase Invoices Report', $headers, $rows, [
                'Generated' => now()->format('d M Y H:i'),
                'Total Amount' => '₹ ' . number_format($totalSum, 2),
            ]);
        }

        return self::exportExcel($filename, 'Purchase Invoices', $headers, $rows, $meta, $totals);
    }

    /**
     * Export Sales Invoices Listing
     */
    public static function exportSalesInvoicesListing(Collection|iterable $invoices, string $format): Response|StreamedResponse
    {
        $filename = 'Sales-Invoices-' . now()->format('Ymd-His') . '.' . ($format === 'excel' ? 'xlsx' : $format);
        $headers = ['Invoice No', 'Date', 'Due Date', 'Customer', 'Status', 'Taxable (₹)', 'Discount (₹)', 'Tax (₹)', 'Grand Total (₹)', 'Paid (₹)', 'Balance (₹)'];
        $rows = [];
        $totalSum = 0;

        foreach ($invoices as $inv) {
            $totalSum += (float) $inv->grand_total;
            $rows[] = [
                $inv->invoice_no,
                $inv->invoice_date?->format('d/m/Y') ?? '',
                $inv->due_date?->format('d/m/Y') ?? '',
                $inv->customer?->name ?? '',
                ucfirst($inv->status),
                number_format((float) $inv->subtotal, 2, '.', ''),
                number_format((float) $inv->discount_amount, 2, '.', ''),
                number_format((float) $inv->tax_amount, 2, '.', ''),
                number_format((float) $inv->grand_total, 2, '.', ''),
                number_format((float) $inv->paid_amount, 2, '.', ''),
                number_format((float) ($inv->grand_total - $inv->paid_amount), 2, '.', ''),
            ];
        }

        $meta = [
            '_title' => 'Sales Invoices Report',
            'Exported At' => now()->format('d M Y H:i:s'),
            'Total Records' => count($rows),
        ];

        $totals = [
            'Total Amount' => '₹ ' . number_format($totalSum, 2),
        ];

        if ($format === 'csv') {
            return self::exportCsv($filename, $headers, $rows, $meta, $totals);
        }

        if ($format === 'pdf') {
            return ReportExporter::pdf($filename, 'Sales Invoices Report', $headers, $rows, [
                'Generated' => now()->format('d M Y H:i'),
                'Total Amount' => '₹ ' . number_format($totalSum, 2),
            ]);
        }

        return self::exportExcel($filename, 'Sales Invoices', $headers, $rows, $meta, $totals);
    }
}

