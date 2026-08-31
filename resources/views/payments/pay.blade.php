<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pay {{ $paymentLink->invoice->invoice_no }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 font-sans antialiased">
<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h1 class="text-lg font-semibold text-gray-900">Pay Invoice</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $paymentLink->invoice->invoice_no }}</p>
        <p class="text-sm text-gray-600 mt-4">{{ $paymentLink->invoice->customer->name }}</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">₹{{ number_format($paymentLink->amount, 2) }}</p>
        <p class="text-xs text-gray-500 break-all mt-4">UPI: {{ $paymentLink->url }}</p>
        @if (session('status'))
            <p class="mt-4 text-sm text-emerald-600">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('payment-links.complete', $paymentLink->token) }}" class="mt-6">
            @csrf
            <button type="submit" class="w-full inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Mark as Paid (Stub)</button>
        </form>
    </div>
</div>
</body>
</html>
