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
   * Always use lightweight HTML for tabular PDFs (Blade + borders is slow on shared hosting).
   */
  private const FAST_PDF_ROW_THRESHOLD = 0;

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
    DompdfConfigurator::bootstrap();

    $rowList = is_array($rows) ? $rows : iterator_to_array($rows, false);
    [$rowList, $capNote] = DompdfConfigurator::capRows($rowList);
    if ($capNote !== null) {
      $meta['Note'] = $capNote;
    }

    if (count($headers) > 14) {
      $headers = array_slice($headers, 0, 14);
      $rowList = array_map(
        static fn (array $row) => array_slice(array_values($row), 0, 14),
        $rowList
      );
      $meta['Columns'] = 'Limited to 14 columns in PDF (use Excel for all fields).';
    }

    $rowList = self::trimCellValues($rowList);

    $dompdf = count($rowList) >= self::FAST_PDF_ROW_THRESHOLD
      ? Pdf::loadHTML(self::buildFastTabularHtml($title, $headers, $rowList, $meta))
      : Pdf::loadView('reporting.exports.generic-pdf', [
        'title' => $title,
        'headers' => $headers,
        'rows' => $rowList,
        'meta' => $meta,
      ]);

    $pdf = DompdfConfigurator::apply($dompdf)->setPaper('a4', 'landscape');

    return $pdf->download($filename);
  }

  /**
   * @param  array<int, array<int, string|int|float|null>>  $rows
   * @return array<int, array<int, string|int|float|null>>
   */
  private static function trimCellValues(array $rows): array
  {
    foreach ($rows as $i => $row) {
      foreach ($row as $k => $cell) {
        if ($k === '_total') {
          continue;
        }
        if (is_string($cell) && strlen($cell) > 80) {
          $rows[$i][$k] = substr($cell, 0, 77).'...';
        }
      }
    }

    return $rows;
  }

  /**
   * Lightweight HTML for large tabular PDFs (avoids Blade + heavy table borders).
   *
   * @param  array<int, string>  $headers
   * @param  array<int, array<int, string|int|float|null>>  $rows
   * @param  array<string, string|int|float|null>  $meta
   */
  private static function buildFastTabularHtml(string $title, array $headers, array $rows, array $meta): string
  {
    $esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $metaParts = [];
    foreach ($meta as $key => $value) {
      if ($key === '_title') {
        continue;
      }
      $metaParts[] = '<strong>'.$esc($key).':</strong> '.$esc($value);
    }
    $metaParts[] = '<strong>Generated:</strong> '.$esc(now()->format('d M Y H:i'));
    $metaHtml = implode(' · ', $metaParts);

    $headerCells = '';
    foreach ($headers as $header) {
      $headerCells .= '<th>'.$esc($header).'</th>';
    }

    $bodyRows = '';
    foreach ($rows as $row) {
      $bodyRows .= '<tr>';
      foreach ($row as $key => $cell) {
        if ($key === '_total') {
          continue;
        }
        $bodyRows .= '<td>'.$esc($cell ?? '').'</td>';
      }
      $bodyRows .= '</tr>';
    }

    return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
      .'@page { size: A4 landscape; margin: 6mm; }'
      .'body { font-family: DejaVu Sans, sans-serif; font-size: 7px; margin: 0; color: #111; }'
      .'h1 { font-size: 12px; margin: 0 0 4px; }'
      .'.meta { font-size: 7px; color: #555; margin-bottom: 6px; }'
      .'table { width: 100%; table-layout: fixed; border-collapse: collapse; }'
      .'th, td { padding: 2px 3px; text-align: left; border-bottom: 1px solid #ddd; word-wrap: break-word; vertical-align: top; }'
      .'th { font-weight: bold; border-bottom: 1px solid #999; }'
      .'</style></head><body>'
      .'<h1>'.$esc($title).'</h1>'
      .'<div class="meta">'.$metaHtml.'</div>'
      .'<table><thead><tr>'.$headerCells.'</tr></thead><tbody>'.$bodyRows.'</tbody></table>'
      .'</body></html>';
  }

}