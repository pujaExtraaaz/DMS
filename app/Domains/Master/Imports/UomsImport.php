<?php

namespace App\Domains\Master\Imports;

use App\Domains\Master\Models\Uom;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UomsImport implements ToCollection, WithHeadingRow
{
    use HandlesBulkImportRows, Importable;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            $code = strtoupper(trim((string) ($data['code'] ?? '')));
            if ($code === '') {
                $this->skip($rowNo, 'code', 'UOM code is required');
                continue;
            }

            $name = \App\Support\NameCase::format((string) ($data['name'] ?? $code));
            $payload = [
                'code' => $code,
                'name' => $name,
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
            ];

            $existing = Uom::query()->whereRaw('UPPER(code) = ?', [$code])->first();
            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                Uom::create($payload);
                $this->created++;
            }
        }
    }
}
