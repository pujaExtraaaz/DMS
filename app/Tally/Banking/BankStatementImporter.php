<?php

namespace Tally\Banking;

use Tally\DataExchange\Spreadsheet;
use Tally\Models\BankAccount;
use Tally\Models\BankStatementLine;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class BankStatementImporter
{
    /**
     * @return array{imported: int, duplicates: int, errors: list<string>}
     */
    public function import(BankAccount $account, UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            throw ValidationException::withMessages(['file' => 'The statement file could not be read.']);
        }

        return $this->importPath($account, $path, strtolower($file->getClientOriginalExtension()));
    }

    /**
     * @return array{imported: int, duplicates: int, errors: list<string>}
     */
    public function importPath(BankAccount $account, string $path, ?string $extension = null): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw ValidationException::withMessages(['file' => 'The statement file could not be read.']);
        }

        $extension ??= strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $table = $this->tableFromPath($path, $extension);

        return $this->importRows($account, $table);
    }

    /**
     * @param  list<list<string>>  $table
     * @return array{imported: int, duplicates: int, errors: list<string>}
     */
    public function importRows(BankAccount $account, array $table): array
    {
        $header = array_shift($table) ?: [];
        $map = $this->map($header);
        $imported = 0;
        $duplicates = 0;
        $errors = [];
        $row = 1;

        foreach ($table as $cells) {
            $row++;

            if ($this->blank($cells)) {
                continue;
            }

            try {
                $date = $this->date($cells[$map['date']] ?? '');
                $valueDate = isset($map['value_date']) ? $this->date($cells[$map['value_date']] ?? '', true) : null;
                $narration = trim((string) ($cells[$map['narration']] ?? ''));
                [$debit, $credit] = $this->sides($cells, $map);
                $reference = isset($map['reference']) ? trim((string) ($cells[$map['reference']] ?? '')) : '';
                $transactionId = isset($map['transaction_id']) ? trim((string) ($cells[$map['transaction_id']] ?? '')) : '';

                if ($narration === '') {
                    throw new \InvalidArgumentException('Narration is required.');
                }

                if ($debit === '0.00' && $credit === '0.00') {
                    throw new \InvalidArgumentException('Enter a debit or a credit.');
                }

                $fingerprint = hash('sha256', implode('|', [$date, $valueDate, $narration, $debit, $credit, $reference, $transactionId]));
                $exists = BankStatementLine::query()
                    ->where('bank_account_id', $account->id)
                    ->where('fingerprint', $fingerprint)
                    ->exists();

                if ($exists) {
                    $duplicates++;

                    continue;
                }

                BankStatementLine::query()->create([
                    'company_id' => $account->company_id,
                    'bank_account_id' => $account->id,
                    'statement_date' => $date,
                    'value_date' => $valueDate,
                    'narration' => $narration,
                    'debit' => $debit,
                    'credit' => $credit,
                    'reference' => $reference === '' ? null : $reference,
                    'transaction_id' => $transactionId === '' ? null : $transactionId,
                    'fingerprint' => $fingerprint,
                ]);
                $imported++;
            } catch (\Throwable $exception) {
                $errors[] = 'Row '.$row.': '.$exception->getMessage();
            }
        }

        return ['imported' => $imported, 'duplicates' => $duplicates, 'errors' => $errors];
    }

    /**
     * @return list<list<string>>
     */
    private function tableFromPath(string $path, string $extension): array
    {
        return match ($extension) {
            'csv', 'txt' => $this->csvPath($path),
            'xlsx' => $this->xlsxPath($path),
            'xls' => $this->xlsPath($path),
            'ofx', 'qfx' => $this->ofxPath($path),
            default => throw ValidationException::withMessages(['file' => 'Import a CSV, OFX, QFX, XLS, or XLSX statement.']),
        };
    }

    /**
     * @return list<list<string>>
     */
    private function csvPath(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'The statement file could not be read.']);
        }

        $rows = [];

        while (($cells = fgetcsv($handle)) !== false) {
            $rows[] = array_map(fn ($cell) => (string) $cell, $cells);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function xlsxPath(string $path): array
    {
        $zip = new \ZipArchive;

        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'The XLSX file could not be opened.']);
        }

        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedXml) {
            $xml = simplexml_load_string($sharedXml);

            foreach ($xml->si ?? [] as $item) {
                $shared[] = trim((string) $item->t) !== '' ? (string) $item->t : implode('', array_map(fn ($part) => (string) $part, $item->xpath('.//t') ?: []));
            }
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $sheet) {
            throw ValidationException::withMessages(['file' => 'The XLSX file has no first worksheet.']);
        }

        $xml = simplexml_load_string($sheet);
        $rows = [];

        foreach ($xml->sheetData->row ?? [] as $row) {
            $cells = [];

            foreach ($row->c as $cell) {
                $column = preg_replace('/\d+/', '', (string) $cell['r']) ?: 'A';
                $index = 0;

                foreach (str_split($column) as $letter) {
                    $index = ($index * 26) + (ord($letter) - 64);
                }

                $value = (string) $cell->v;

                if ((string) $cell['t'] === 's') {
                    $value = $shared[(int) $value] ?? '';
                }

                if ((string) $cell['t'] === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                }

                $cells[$index - 1] = $value;
            }

            if ($cells === []) {
                continue;
            }

            $filled = [];

            foreach (range(0, max(array_keys($cells))) as $index) {
                $filled[] = $cells[$index] ?? '';
            }

            $rows[] = $filled;
        }

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function xlsPath(string $path): array
    {
        try {
            return Spreadsheet::sheet($path);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }
    }

    /**
     * Minimal OFX/QFX SGML parser for bank statement transactions.
     *
     * @return list<list<string>>
     */
    private function ofxPath(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            throw ValidationException::withMessages(['file' => 'The OFX file could not be read.']);
        }

        if (! preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/si', $raw, $matches)) {
            throw ValidationException::withMessages(['file' => 'No OFX transactions were found.']);
        }

        $rows = [['Date', 'Narration', 'Amount', 'Reference', 'Transaction ID']];

        foreach ($matches[1] as $block) {
            $posted = $this->ofxTag($block, 'DTPOSTED');
            $amount = $this->ofxTag($block, 'TRNAMT');
            $memo = $this->ofxTag($block, 'MEMO') ?: $this->ofxTag($block, 'NAME') ?: 'OFX transaction';
            $fitid = $this->ofxTag($block, 'FITID');
            $ref = $this->ofxTag($block, 'CHECKNUM') ?: $this->ofxTag($block, 'REFNUM');

            if ($posted === '' || $amount === '') {
                continue;
            }

            $date = $this->ofxDate($posted);
            $rows[] = [$date, $memo, $amount, $ref, $fitid];
        }

        if (count($rows) < 2) {
            throw ValidationException::withMessages(['file' => 'No OFX transactions were found.']);
        }

        return $rows;
    }

    private function ofxTag(string $block, string $tag): string
    {
        if (preg_match('/<'.$tag.'>([^<\r\n]+)/i', $block, $match)) {
            return trim($match[1]);
        }

        return '';
    }

    private function ofxDate(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) < 8) {
            throw new \InvalidArgumentException('OFX date "'.$value.'" is not valid.');
        }

        return substr($digits, 0, 4).'-'.substr($digits, 4, 2).'-'.substr($digits, 6, 2);
    }

    /**
     * @param  list<string|null>  $header
     * @return array<string, int|null>
     */
    private function map(array $header): array
    {
        $index = [];

        foreach ($header as $position => $label) {
            $key = strtolower(trim((string) $label));
            $index[$key] = $position;
        }

        $date = $index['date'] ?? $index['statement date'] ?? null;
        $narration = $index['narration'] ?? $index['description'] ?? null;
        $debit = $index['debit'] ?? $index['withdrawal'] ?? null;
        $credit = $index['credit'] ?? $index['deposit'] ?? null;
        $amount = $index['amount'] ?? null;

        if ($date === null || $narration === null || (($debit === null || $credit === null) && $amount === null)) {
            throw ValidationException::withMessages([
                'file' => 'The statement needs Date, Narration, and either Debit and Credit or Amount. Value Date, Reference, and Transaction ID are optional.',
            ]);
        }

        return [
            'date' => $date,
            'narration' => $narration,
            'debit' => $debit,
            'credit' => $credit,
            'amount' => $amount,
            'value_date' => $index['value date'] ?? $index['value_date'] ?? null,
            'reference' => $index['reference'] ?? $index['cheque'] ?? null,
            'transaction_id' => $index['transaction id'] ?? $index['transaction_id'] ?? $index['txn id'] ?? null,
        ];
    }

    /**
     * @param  list<string|null>  $cells
     * @param  array<string, int|null>  $map
     * @return array{0: string, 1: string}
     */
    private function sides(array $cells, array $map): array
    {
        if ($map['debit'] !== null || $map['credit'] !== null) {
            return [
                $this->amount($cells[$map['debit']] ?? '0'),
                $this->amount($cells[$map['credit']] ?? '0'),
            ];
        }

        $raw = str_replace([',', ' '], '', trim((string) ($cells[$map['amount']] ?? '0')));
        $negative = str_starts_with($raw, '-');
        $amount = $this->amount(ltrim($raw, '-'));

        return $negative ? [$amount, '0.00'] : ['0.00', $amount];
    }

    private function date(string $value, bool $optional = false): ?string
    {
        $value = trim($value);

        if ($value === '') {
            if ($optional) {
                return null;
            }

            throw new \InvalidArgumentException('Date is required.');
        }

        $parsed = strtotime(str_replace('/', '-', $value));

        if ($parsed === false) {
            throw new \InvalidArgumentException('Date "'.$value.'" is not valid.');
        }

        return date('Y-m-d', $parsed);
    }

    private function amount(string $value): string
    {
        $value = str_replace([',', ' '], '', trim($value));

        if ($value === '') {
            return '0.00';
        }

        if (! is_numeric($value) || (float) $value < 0) {
            throw new \InvalidArgumentException('Amount "'.$value.'" is not valid.');
        }

        return number_format((float) $value, 2, '.', '');
    }

    /**
     * @param  list<string|null>|false  $cells
     */
    private function blank(array|false $cells): bool
    {
        if ($cells === false) {
            return true;
        }

        return implode('', array_map(fn ($cell) => trim((string) $cell), $cells)) === '';
    }
}
