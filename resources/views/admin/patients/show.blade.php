<!DOCTYPE html>
<html>
<head>
    <title>Patient Details</title>
</head>
<body>
    <h1>{{ $patient->name }}</h1>
    <p>NIK: {{ $patient->nik }}</p>
    <p>MRN: {{ $patient->medical_record_number }}</p>
</body>
</html>
