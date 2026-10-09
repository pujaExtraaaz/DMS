<?php

namespace Tally\DataExchange;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

/**
 * Reads and writes CSV and the first worksheet of an .xlsx workbook.
 * Values are text so dates and codes survive a round trip through Excel.
 */
class Spreadsheet
{
    /**
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    public static function read(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $grid = match ($extension) {
            'csv', 'txt' => self::readCsv($path),
            'xlsx' => self::readXlsx($path),
            'xls' => self::sheet($path),
            default => throw new InvalidArgumentException('Upload a CSV or Excel (.xlsx or .xls) file.'),
        };

        if ($grid === []) {
            throw new InvalidArgumentException('The file is empty.');
        }

        $headers = [];

        foreach ($grid[0] as $index => $header) {
            $key = self::headerKey((string) $header);

            if ($key === '') {
                continue;
            }

            if (in_array($key, $headers, true)) {
                throw new InvalidArgumentException("The column \"{$key}\" is repeated.");
            }

            $headers[$index] = $key;
        }

        if ($headers === []) {
            throw new InvalidArgumentException('The file has no column headings.');
        }

        $rows = [];

        foreach (array_slice($grid, 1) as $line) {
            $row = [];
            $blank = true;

            foreach ($headers as $index => $key) {
                $value = trim((string) ($line[$index] ?? ''));
                $row[$key] = $value;

                if ($value !== '') {
                    $blank = false;
                }
            }

            if (! $blank) {
                $rows[] = $row;
            }
        }

        return [
            'headers' => array_values($headers),
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<string, scalar|null>>  $rows
     */
    public static function write(string $path, array $headers, array $rows, string $format): void
    {
        $format = strtolower($format);

        if ($format === 'xlsx') {
            self::writeXlsx($path, $headers, $rows);

            return;
        }

        if ($format !== 'csv') {
            throw new InvalidArgumentException('Choose CSV or Excel.');
        }

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException('The export file could not be created.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            $line = [];

            foreach ($headers as $header) {
                $line[] = (string) ($row[$header] ?? '');
            }

            fputcsv($handle, $line);
        }

        fclose($handle);
    }

    public static function headerKey(string $header): string
    {
        $header = strtolower(trim($header));
        $header = (string) preg_replace('/[^a-z0-9]+/', '_', $header);

        return trim($header, '_');
    }

    /**
     * First worksheet of a real Excel 97–2003 .xls workbook.
     *
     * @return list<list<string>>
     */
    public static function sheet(string $path): array
    {
        if (! class_exists(\Shuchkin\SimpleXLS::class)) {
            throw new InvalidArgumentException('Excel .xls import is not available.');
        }

        $sheet = \Shuchkin\SimpleXLS::parse($path);

        if ($sheet === false) {
            throw new InvalidArgumentException(\Shuchkin\SimpleXLS::parseError() ?: 'The XLS file could not be opened.');
        }

        $grid = [];

        foreach ($sheet->rows() as $line) {
            $cells = array_map(fn ($cell) => trim((string) $cell), $line);

            if (implode('', $cells) === '') {
                continue;
            }

            $grid[] = $cells;
        }

        return $grid;
    }

    /**
     * @return list<list<string>>
     */
    private static function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('The CSV file could not be read.');
        }

        $grid = [];
        $first = true;

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === false) {
                continue;
            }

            if ($first) {
                $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($line[0] ?? '')) ?? '';
                $first = false;
            }

            $grid[] = array_map(fn ($cell) => trim((string) $cell), $line);
        }

        fclose($handle);

        return $grid;
    }

    /**
     * @return list<list<string>>
     */
    private static function readXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new InvalidArgumentException('Excel import needs the PHP zip extension.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('The Excel file could not be read.');
        }

        $shared = self::sharedStrings($zip);
        $sheet = self::firstSheet($zip);
        $zip->close();

        if ($sheet === null) {
            throw new InvalidArgumentException('The Excel file has no worksheet.');
        }

        $dom = new DOMDocument;

        if (! @$dom->loadXML($sheet)) {
            throw new InvalidArgumentException('The Excel worksheet could not be read.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $grid = [];

        foreach ($xpath->query('//m:sheetData/m:row') ?: [] as $rowNode) {
            $line = [];

            foreach ($xpath->query('m:c', $rowNode) ?: [] as $cell) {
                $reference = $cell->getAttribute('r');
                $column = self::columnIndex($reference);
                $type = $cell->getAttribute('t');
                $value = '';

                if ($type === 's') {
                    $index = (int) ($xpath->evaluate('string(m:v)', $cell) ?: 0);
                    $value = $shared[$index] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) $xpath->evaluate('string(m:is)', $cell);
                } else {
                    $value = (string) $xpath->evaluate('string(m:v)', $cell);
                }

                $line[$column] = trim($value);
            }

            if ($line === []) {
                $grid[] = [];

                continue;
            }

            $width = max(array_keys($line));
            $filled = [];

            for ($index = 0; $index <= $width; $index++) {
                $filled[] = $line[$index] ?? '';
            }

            $grid[] = $filled;
        }

        return $grid;
    }

    /**
     * @return list<string>
     */
    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $dom = new DOMDocument;

        if (! @$dom->loadXML($xml)) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = [];

        foreach ($xpath->query('//m:si') ?: [] as $item) {
            $strings[] = (string) $xpath->evaluate('string(.)', $item);
        }

        return $strings;
    }

    private static function firstSheet(ZipArchive $zip): ?string
    {
        $workbook = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $target = 'xl/worksheets/sheet1.xml';

        if ($workbook !== false) {
            $dom = new DOMDocument;

            if (@$dom->loadXML($workbook)) {
                $xpath = new DOMXPath($dom);
                $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
                $node = $xpath->query('//r:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"]')->item(0);

                if ($node) {
                    $target = 'xl/'.ltrim((string) $node->attributes?->getNamedItem('Target')?->nodeValue, '/');
                }
            }
        }

        $sheet = $zip->getFromName($target);

        return $sheet === false ? null : $sheet;
    }

    private static function columnIndex(string $reference): int
    {
        $letters = strtoupper((string) preg_replace('/[^A-Z]/i', '', $reference));

        if ($letters === '') {
            return 0;
        }

        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<string, scalar|null>>  $rows
     */
    private static function writeXlsx(string $path, array $headers, array $rows): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Excel export needs the PHP zip extension.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The Excel file could not be created.');
        }

        $sheetRows = [$headers];

        foreach ($rows as $row) {
            $line = [];

            foreach ($headers as $header) {
                $line[] = (string) ($row[$header] ?? '');
            }

            $sheetRows[] = $line;
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('xl/workbook.xml', self::workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($sheetRows));
        $zip->close();
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private static function sheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $number = $rowIndex + 1;
            $xml .= '<row r="'.$number.'">';

            foreach ($row as $columnIndex => $value) {
                $cell = self::columnLetter($columnIndex).$number;
                $text = self::xmlText($value);
                $xml .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.$text.'</t></is></c>';
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function columnLetter(int $index): string
    {
        $index++;
        $letters = '';

        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)).$letters;
            $index = intdiv($index, 26);
        }

        return $letters;
    }

    private static function xmlText(string $value): string
    {
        $value = (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value);

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }
}
