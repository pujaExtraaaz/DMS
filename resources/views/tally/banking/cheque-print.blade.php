<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Cheque {{ $instrument->number }}</title>
    <style>
        body { font-family: "Segoe UI", sans-serif; margin: 32px; }
        .cheque { border: 1px solid #1a2332; width: 720px; padding: 16px 20px; }
        .row { display: flex; justify-content: space-between; margin: 10px 0; }
        strong { font-size: 18px; }
    </style>
</head>
<body>
    <div class="cheque">
        <div class="row"><span>{{ $instrument->bankAccount?->bank_name }}</span><span>{{ $instrument->instrument_date->format('d-M-Y') }}</span></div>
        <div class="row"><span>Pay</span><strong>{{ $instrument->favouring }}</strong></div>
        <div class="row"><span>Rupees</span><strong>{{ $instrument->amount }}</strong></div>
        <div class="row"><span>A/c {{ $instrument->bankAccount?->account_number }}</span><span>Cheque {{ $instrument->number }}</span></div>
        @if ($instrument->narration)
            <p>{{ $instrument->narration }}</p>
        @endif
    </div>
    <script>window.print()</script>
</body>
</html>
