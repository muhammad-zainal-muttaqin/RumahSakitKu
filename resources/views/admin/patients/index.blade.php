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
</body>
</html>
