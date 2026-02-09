<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Tersedia</title>
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
        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
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
        .highlight {
            background-color: #e7f3ff;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Laporan Telah Selesai Dibuat</h1>
        </div>

        <div class="content">
            <p>Halo <strong>{{ $generatedBy }}</strong>,</p>

            <p>Laporan yang Anda minta telah selesai dibuat dan siap diunduh.</p>

            <div class="success-box">
                <h3 style="margin-top: 0; color: #155724;">✓ Laporan Tersedia</h3>
            </div>

            <div class="info-box">
                <h3 style="margin-top: 0;">Detail Laporan</h3>
                <table>
                    <tr>
                        <td>Nama Laporan</td>
                        <td><strong>{{ $reportName }}</strong></td>
                    </tr>
                    <tr>
                        <td>Tipe</td>
                        <td>{{ $reportType }}</td>
                    </tr>
                    <tr>
                        <td>Dibuat Oleh</td>
                        <td>{{ $generatedBy }}</td>
                    </tr>
                    <tr>
                        <td>Waktu</td>
                        <td>{{ $generatedAt }}</td>
                    </tr>
                    @if($periodStart && $periodEnd)
                    <tr>
                        <td>Periode</td>
                        <td>{{ $periodStart }} s/d {{ $periodEnd }}</td>
                    </tr>
                    @endif
                    @if($fileSize)
                    <tr>
                        <td>Ukuran File</td>
                        <td>{{ $fileSize }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            @if($summary && !empty($summary))
            <div class="info-box">
                <h3 style="margin-top: 0;">Ringkasan</h3>
                <table>
                    @foreach($summary as $key => $value)
                    <tr>
                        <td>{{ ucfirst($key) }}</td>
                        <td>{{ $value }}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
            @endif

            <div class="text-center">
                <a href="{{ $downloadUrl }}" class="button">Unduh Laporan</a>
            </div>

            <p style="margin-top: 30px; font-size: 12px; color: #666;">
                Laporan ini juga tersedia di menu laporan sistem. File dapat diunduh selama 7 hari ke depan.
            </p>
        </div>

        <div class="footer">
            <p>Email ini dikirim secara otomatis oleh sistem.<br>
            Mohon untuk tidak membalas email ini.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
