<!DOCTYPE html>
<html>
<head>
    <title>Invoices</title>
</head>
<body>
    <h1>Invoices</h1>
    @if(session('success'))
        <div>{{ session('success') }}</div>
    @endif
    @foreach($invoices as $invoice)
        <p>{{ $invoice->invoice_number }} - {{ number_format($invoice->total_amount, 0, ',', '.') }}</p>
    @endforeach
</body>
</html>
