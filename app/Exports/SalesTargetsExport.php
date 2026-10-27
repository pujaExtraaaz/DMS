<?php

namespace App\Exports;

use App\Domains\Target\Models\SalesTarget;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesTargetsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Collection $targets,
        protected ?string $title = 'Sales Targets Report'
    ) {}

    public function collection(): Collection
    {
        return $this->targets;
    }

    public function headings(): array
    {
        return [
            'Target ID',
            'Target Name',
            'Owner Type',
            'Salesman / Team / Branch',
            'Period Type',
            'Start Date',
            'End Date',
            'Target Type',
            'Target Value (₹)',
            'Achieved Value (₹)',
            'Pending Value (₹)',
            'Achievement %',
            'Target Qty',
            'Achieved Qty',
            'Pending Qty',
            'Qty Achievement %',
            'Status',
            'Invoices Count',
        ];
    }

    /**
     * @param SalesTarget $target
     */
    public function map($target): array
    {
        $ach = $target->achievement;
        $targetAmt = (float) $target->target_amount;
        $achAmt = $ach ? (float) $ach->achieved_amount : 0.0;
        $pendAmt = $ach ? (float) $ach->pending_amount : max(0, $targetAmt - $achAmt);
        $percent = $ach ? (float) $ach->achievement_percent : 0.0;

        $targetQty = (float) $target->target_quantity;
        $achQty = $ach ? (float) $ach->achieved_quantity : 0.0;
        $pendQty = $ach ? (float) $ach->pending_quantity : max(0, $targetQty - $achQty);
        $qtyPercent = $ach ? (float) $ach->quantity_achievement_percent : 0.0;

        return [
            'TGT-' . str_pad((string)$target->id, 5, '0', STR_PAD_LEFT),
            $target->name,
            ucfirst($target->owner_type),
            $target->owner_name,
            ucfirst(str_replace('_', ' ', $target->period_type)),
            $target->starts_on->format('d M Y'),
            $target->ends_on->format('d M Y'),
            ucfirst($target->target_type),
            number_format($targetAmt, 2, '.', ''),
            number_format($achAmt, 2, '.', ''),
            number_format($pendAmt, 2, '.', ''),
            $percent . '%',
            $targetQty > 0 ? number_format($targetQty, 2, '.', '') : '—',
            $targetQty > 0 ? number_format($achQty, 2, '.', '') : '—',
            $targetQty > 0 ? number_format($pendQty, 2, '.', '') : '—',
            $targetQty > 0 ? $qtyPercent . '%' : '—',
            ucfirst(str_replace('_', ' ', $target->status)),
            $ach ? $ach->invoices_count : 0,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'],
                ],
            ],
        ];
    }
}

