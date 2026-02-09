<!DOCTYPE html>
<html>
<head>
    <title>Invoice {{ $invoice->invoice_number }}</title>
</head>
<body>
    <h1>Invoice {{ $invoice->invoice_number }}</h1>
    <p>Total: {{ number_format($invoice->total_amount, 0, ',', '.') }}</p>
</body>
</html>
