<?php

namespace Tally\DataExchange\Export;

use Tally\DataExchange\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExporter
{
    public function __construct(private readonly ExportCatalog $catalog) {}

    public function download(string $key, string $format): StreamedResponse
    {
        $format = $format === 'xlsx' ? 'xlsx' : 'csv';
        $table = $this->catalog->build($key);
        $filename = $table['filename'].'.'.$format;
        $type = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv; charset=UTF-8';

        return response()->streamDownload(function () use ($table, $format) {
            $path = tempnam(sys_get_temp_dir(), 'tally-export');

            if ($path === false) {
                throw new \RuntimeException('The export file could not be created.');
            }

            $target = $path.'.'.$format;
            @unlink($path);
            Spreadsheet::write($target, $table['headers'], $table['rows'], $format);
            readfile($target);
            @unlink($target);
        }, $filename, ['Content-Type' => $type]);
    }
}
