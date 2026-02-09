<!DOCTYPE html>
<html>
<head>
    <title>Satu Sehat Logs</title>
</head>
<body>
    <h1>Satu Sehat Logs</h1>
    @foreach($logs as $log)
        <p>{{ $log->resource_type }} - {{ $log->status }} - {{ $log->fhir_id }}</p>
    @endforeach
</body>
</html>
