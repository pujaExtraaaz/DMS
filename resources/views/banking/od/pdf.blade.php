<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>OD Report - {{ $report['odAccount']->bank_name }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 8px; color: #1e293b; margin: 0; padding: 0; }
        .header { margin-bottom: 10px; border-bottom: 2px solid #3b82f6; padding-bottom: 6px; }
        .company-name { font-size: 14px; font-weight: bold; color: #0f172a; }
        .report-title { font-size: 11px; font-weight: bold; color: #1e40af; margin-top: 2px; text-transform: uppercase; }
        .meta-text { font-size: 8px; color: #64748b; margin-top: 2px; }
        
        .summary-box { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .summary-box td { border: 1px solid #cbd5e1; padding: 5px 8px; font-size: 8px; background: #f8fafc; }
        .summary-label { font-size: 7px; text-transform: uppercase; color: #64748b; font-weight: bold; }
        .summary-val { font-size: 10px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .text-exceeded { color: #dc2626 !important; font-weight: bold; }
        
        .alert-exceeded { background: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 4px 8px; font-size: 8px; font-weight: bold; margin-bottom: 8px; border-radius: 4px; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 3px 5px; font-size: 7.5px; }
        table.data-table th { background: #f1f5f9; text-transform: uppercase; font-size: 7px; color: #334155; }
        table.data-table tr.opening td { background: #fef3c7; font-weight: bold; }
        table.data-table tr.totals td { background: #e2e8f0; font-weight: bold; font-size: 8px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 10px; font-size: 7px; color: #94a3b8; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="company-name">{{ $company?->name ?? 'Company' }}</div>
                    <div class="report-title">Overdraft (OD) Limit &amp; Interest Statement</div>
                    <div class="meta-text">
                        Bank: <strong>{{ $report['odAccount']->bank_name }}</strong> &nbsp;|&nbsp;
                        Account No: <strong>{{ $report['odAccount']->account_number }}</strong>
                        @if($report['odAccount']->ifsc_code) &nbsp;|&nbsp; IFSC: {{ $report['odAccount']->ifsc_code }} @endif
                        &nbsp;|&nbsp; Period: {{ \Carbon\Carbon::parse($report['fromDate'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($report['toDate'])->format('d M Y') }}
                    </div>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <div class="meta-text">Generated: {{ now()->format('d M Y H:i') }}</div>
                    <div class="meta-text">Calculation Method: Simple Daily</div>
                </td>
            </tr>
        </table>
    </div>

    @if($report['isExceeded'])
        <div class="alert-exceeded">
            WARNING: OD LIMIT EXCEEDED BY ₹{{ number_format($report['exceededAmount'], 2) }}
        </div>
    @endif

    <!-- Summary Box -->
    <table class="summary-box">
        <tr>
            <td>
                <div class="summary-label">Configured OD Limit</div>
                <div class="summary-val">₹{{ number_format($report['odLimit'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Annual Interest Rate</div>
                <div class="summary-val">{{ number_format($report['interestRate'], 2) }}% p.a.</div>
            </td>
            <td>
                <div class="summary-label">Est. Annual Interest</div>
                <div class="summary-val">₹{{ number_format($report['estimatedAnnualInterest'] ?? 0, 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Est. Monthly Interest</div>
                <div class="summary-val">₹{{ number_format($report['estimatedMonthlyInterest'] ?? 0, 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Current Running Balance</div>
                <div class="summary-val {{ $report['isExceeded'] ? 'text-exceeded' : '' }}">
                    ₹{{ number_format($report['currentUtilized'], 2) }}
                </div>
            </td>
            <td>
                <div class="summary-label">Available OD Limit</div>
                <div class="summary-val" style="color: #059669;">
                    ₹{{ number_format($report['availableOd'], 2) }}
                </div>
            </td>
            <td>
                <div class="summary-label">Total Period Interest</div>
                <div class="summary-val" style="color: #4338ca;">
                    ₹{{ number_format($report['totalPeriodInterest'], 2) }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Transaction Table -->
    @php
        $pdfRows = !empty($report['displayRows']) && $report['displayRows']->isNotEmpty() ? $report['displayRows'] : $report['rows'];
    @endphp
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">Sr.</th>
                <th style="width: 55px;">Date</th>
                <th style="width: 75px;">Voucher / Ref No</th>
                <th>Particulars / Description</th>
                <th style="width: 55px;">Type</th>
                <th class="text-right" style="width: 60px;">Debit (₹)</th>
                <th class="text-right" style="width: 60px;">Credit (₹)</th>
                <th class="text-right" style="width: 65px;">Running Bal.</th>
                <th class="text-right" style="width: 65px;">Available OD</th>
                <th class="text-center" style="width: 30px;">Days</th>
                <th class="text-right" style="width: 50px;">Daily Int.</th>
                <th class="text-right" style="width: 55px;">Cumul. Int.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pdfRows as $idx => $row)
                <tr class="{{ ($row['is_opening'] ?? false) ? 'opening' : '' }}">
                    <td class="text-center">{{ $row['sr_no'] ?? ($idx + 1) }}</td>
                    <td>{{ $row['date'] instanceof \Carbon\Carbon ? $row['date']->format('d/m/Y') : $row['date'] }}</td>
                    <td>{{ $row['transaction_no'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $row['transaction_type'])) }}</td>
                    <td class="text-right">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}</td>
                    <td class="text-right">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}</td>
                    <td class="text-right {{ ($row['is_exceeded'] ?? false) ? 'text-exceeded' : '' }}">
                        {{ number_format($row['running_balance'] ?? $row['od_utilized'], 2) }}
                    </td>
                    <td class="text-right">{{ number_format($row['available_od'], 2) }}</td>
                    <td class="text-center">{{ $row['days'] ?? 0 }}</td>
                    <td class="text-right">{{ number_format($row['daily_interest'] ?? 0, 2) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($row['cumulative_interest'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="5" class="text-right">TOTALS / CLOSING:</td>
                <td class="text-right">{{ number_format($report['totalDebit'], 2) }}</td>
                <td class="text-right">{{ number_format($report['totalCredit'], 2) }}</td>
                <td class="text-right">{{ number_format($report['currentUtilized'], 2) }}</td>
                <td class="text-right">{{ number_format($report['availableOd'], 2) }}</td>
                <td colspan="2" class="text-right">PERIOD INTEREST:</td>
                <td class="text-right" style="font-size: 9px; color: #312e81;">₹{{ number_format($report['totalPeriodInterest'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        DMS Financial Modules &bull; Daily Interest = OD Utilized Amount &times; Annual Rate &divide; 365 &divide; 100 &bull; Page 1 of 1
    </div>
</body>
</html>

