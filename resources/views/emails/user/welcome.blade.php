<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang</title>
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
        .alert-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
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
            <h1>Selamat Datang di {{ $hospitalName }}</h1>
        </div>

        <div class="content">
            <p>Halo <strong>{{ $userName }}</strong>,</p>

            <p>Selamat! Akun Anda telah berhasil dibuat di sistem {{ $hospitalName }}. Anda sekarang dapat mengakses sistem manajemen rumah sakit.</p>

            <div class="info-box">
                <h3 style="margin-top: 0;">Informasi Akun</h3>
                <table>
                    <tr>
                        <td>Nama</td>
                        <td>{{ $userName }}</td>
                    </tr>
                    @if($employeeName)
                    <tr>
                        <td>Nama Karyawan</td>
                        <td>{{ $employeeName }}</td>
                    </tr>
                    @endif
                    @if($employeeCode)
                    <tr>
                        <td>Kode Karyawan</td>
                        <td>{{ $employeeCode }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Email/Username</td>
                        <td><span class="highlight">{{ $email }}</span></td>
                    </tr>
                    <tr>
                        <td>Password Sementara</td>
                        <td><span class="highlight">{{ $temporaryPassword }}</span></td>
                    </tr>
                    <tr>
                        <td>Role</td>
                        <td>{{ $role }}</td>
                    </tr>
                </table>
            </div>

            <div class="alert-box">
                <h3 style="margin-top: 0; color: #856404;">🔐 Keamanan Akun</h3>
                <ul>
                    <li><strong>Segera ubah password</strong> setelah login pertama kali</li>
                    <li>Jangan bagikan password kepada siapapun</li>
                    <li>Gunakan password yang kuat (minimal 8 karakter, huruf besar, kecil, dan angka)</li>
                    <li>Logout setelah selesai menggunakan sistem</li>
                </ul>
            </div>

            <div class="text-center">
                <a href="{{ $loginUrl }}" class="button">Login ke Sistem</a>
            </div>

            <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                <h4 style="margin-top: 0;">Fitur yang Tersedia:</h4>
                <ul>
                    <li>Manajemen Data Pasien</li>
                    <li>Manajemen Kunjungan dan Antrian</li>
                    <li>Rekam Medis Elektronik</li>
                    <li>Manajemen Farmasi dan Stok Obat</li>
                    <li>Manajemen Keuangan dan Tagihan</li>
                    <li>Laporan dan Statistik</li>
                </ul>
            </div>

            <p>Jika Anda mengalami kesulitan login atau memiliki pertanyaan, silakan hubungi tim IT Support:</p>
            <ul>
                <li>Telepon: {{ $hospitalPhone }}</li>
                <li>Email: {{ $supportEmail }}</li>
            </ul>

            <p>Selamat bekerja!<br>
            <strong>Tim {{ $hospitalName }}</strong></p>
        </div>

        <div class="footer">
            <p><strong>{{ $hospitalName }}</strong><br>
            Telp: {{ $hospitalPhone }}</p>
            <p>Email ini dikirim secara otomatis oleh sistem.<br>
            Mohon untuk tidak membalas email ini.</p>
            <p>&copy; {{ date('Y') }} {{ $hospitalName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
