<!DOCTYPE html>
<html>
<head>
    <title>Today's Payments Report</title>
</head>
<body>
    <h1>Today's Payments Report</h1>
    <p>Total: {{ number_format($totalPayments ?? 0, 0, ',', '.') }}</p>
</body>
</html>
