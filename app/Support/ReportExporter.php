<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Small helper for exporting report data as either CSV or a landscape PDF.
 *
 * Reports call this from their controller `?export=csv|pdf` branch — the
 * caller decides how to shape the rows so the exporter stays report-agnostic.
 */
class ReportExporter
{
    /**
     * @param  array<int, string>  $headers  Column labels for row 1.
     * @param  Collection<int, array<int, string|int|float|null>>|array  $rows
     */
    public static function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     * @param  array<string, string|int|float|null>  $meta  optional key/value block above the table
     */
    public static function pdf(string $filename, string $title, array $headers, iterable $rows, array $meta = []): Response
    {
        $pdf = Pdf::loadView('reporting.exports.generic-pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'meta' => $meta,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
