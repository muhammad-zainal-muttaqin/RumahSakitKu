<!DOCTYPE html>
<html>
<head>
    <title>Patients</title>
</head>
<body>
    <h1>Patients</h1>
    @if(session('success'))
        <div>{{ session('success') }}</div>
    @endif
    <ul>
        @foreach($patients as $patient)
            <li>{{ $patient->name }}</li>
        @endforeach
    </ul>
</body>
</html>
