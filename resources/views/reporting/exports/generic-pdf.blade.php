<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 9px; color:#111; margin:0; padding:0; }
        .title { font-size: 14px; font-weight: bold; margin-bottom: 4px; text-transform: uppercase; }
        .meta { font-size: 8px; color:#4b5563; margin-bottom: 8px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; }
        th { background:#f3f4f6; text-align:left; }
        tr.total td { background: #f9fafb; font-weight: bold; }
        .text-right { text-align: right; }
        .footer { margin-top: 12px; font-size: 8px; color: #6b7280; }
    </style>
</head>
<body>
<div class="title">{{ $title }}</div>
@if(! empty($meta))
    <div class="meta">
        @foreach($meta as $k => $v)
            <strong>{{ $k }}:</strong> {{ $v }} &nbsp;·&nbsp;
        @endforeach
        <strong>Generated:</strong> {{ now()->format('d M Y H:i') }}
    </div>
@endif

<table>
    <thead>
        <tr>
            @foreach($headers as $h)<th>{{ $h }}</th>@endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr @class(['total' => is_array($row) && ($row['_total'] ?? false)])>
                @foreach($row as $key => $cell)
                    @if($key === '_total') @continue @endif
                    <td @class(['text-right' => is_numeric($cell)])>{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">DMS · {{ config('app.name') }} · Exported by {{ auth()->user()?->name ?? 'system' }}</div>
</body>
</html>
