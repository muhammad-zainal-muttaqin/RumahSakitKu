<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header img {
            max-width: 150px;
            margin-bottom: 15px;
        }
        .content {
            padding: 30px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #666666;
            border-top: 1px solid #e9ecef;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 600;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        .alert-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        .danger-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        table td:first-child {
            font-weight: 600;
            width: 40%;
            color: #666666;
        }
        .text-center {
            text-align: center;
        }
        .text-muted {
            color: #666666;
        }
        .highlight {
            background-color: #e7f3ff;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            color: #0066cc;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .content, .header, .footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $hospitalName ?? config('app.name') }}</h1>
        </div>

        <div class="content">
            {{ $slot }}
        </div>

        <div class="footer">
            <p>
                <strong>{{ $hospitalName ?? config('app.name') }}</strong><br>
                @if(isset($hospitalAddress))
                    {{ $hospitalAddress }}<br>
                @endif
                @if(isset($hospitalPhone))
                    Telp: {{ $hospitalPhone }}
                @endif
            </p>
            <p class="text-muted">
                Email ini dikirim secara otomatis oleh sistem.<br>
                Mohon untuk tidak membalas email ini.
            </p>
            <p>&copy; {{ date('Y') }} {{ $hospitalName ?? config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
