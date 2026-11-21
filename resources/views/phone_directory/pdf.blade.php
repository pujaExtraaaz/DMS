<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 15mm 10mm;
            size: A4 landscape;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #1e293b;
            line-height: 1.3;
        }
        .header {
            border-bottom: 2px solid #6d28d9;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #4c1d95;
        }
        .report-title {
            font-size: 12pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 10px;
            font-size: 8pt;
            color: #64748b;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        table.data-table th {
            background-color: #6d28d9;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 5px 6px;
            border: 1px solid #5b21b6;
        }
        table.data-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-star {
            color: #b45309;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            color: #94a3b8;
            text-align: right;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="vertical-align: middle;">
                    <div class="company-name">{{ $company?->name ?? 'DMS ERP' }}</div>
                    <div class="report-title">{{ $title }}</div>
                </td>
                <td style="text-align: right; vertical-align: middle; font-size: 8pt; color: #64748b;">
                    <div>Generated: {{ $generatedAt }}</div>
                    <div>Total Records: {{ number_format($contacts->count()) }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Main Contacts Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">#</th>
                <th style="width: 120px;">Contact Name</th>
                <th style="width: 70px;">Type</th>
                <th style="width: 80px;">Classification</th>
                <th style="width: 90px;">Designation</th>
                <th style="width: 75px;">Department</th>
                <th style="width: 100px;">Company</th>
                <th style="width: 80px;">Primary Mobile</th>
                <th style="width: 100px;">Email</th>
                <th>Location</th>
                <th style="width: 45px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($contacts as $idx => $c)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $c->contact_name }}</strong>
                        @if($c->is_important)
                            <span class="badge-star">★</span>
                        @endif
                    </td>
                    <td>{{ \App\Domains\PhoneDirectory\Models\PhoneDirectoryContact::CONTACT_TYPES[$c->contact_type] ?? ucfirst($c->contact_type) }}</td>
                    <td>{{ $c->classification?->name ?? '—' }}</td>
                    <td>{{ $c->designation ?: '—' }}</td>
                    <td>{{ $c->department?->name ?? '—' }}</td>
                    <td>{{ $c->effective_company_name ?: ($c->company_name ?: '—') }}</td>
                    <td><strong>{{ $c->mobile_number }}</strong></td>
                    <td>{{ $c->email ?: '—' }}</td>
                    <td>{{ $c->formatted_location }}</td>
                    <td style="text-align: center;">{{ ucfirst($c->status ?? 'active') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 15px; color: #94a3b8;">
                        No contacts found matching the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        DMS Phone Directory &bull; Page printed from ERP &bull; Strictly Confidential
    </div>

</body>
</html>
