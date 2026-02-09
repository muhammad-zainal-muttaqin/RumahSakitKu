<x-emails.layout>
    <h2>Selamat Datang di {{ $hospitalName }}</h2>

    <p>Halo <strong>{{ $patientName }}</strong>,</p>

    <p>Selamat! Pendaftaran Anda sebagai pasien di {{ $hospitalName }} telah berhasil. Berikut adalah detail informasi Anda:</p>

    <div class="success-box">
        <h3 style="margin-top: 0; color: #155724;">Informasi Pasien</h3>
        <table>
            <tr>
                <td>Nama Lengkap</td>
                <td>{{ $patientName }}</td>
            </tr>
            <tr>
                <td>Nomor Rekam Medis</td>
                <td><span class="highlight">{{ $mrn }}</span></td>
            </tr>
            @if($birthDate)
            <tr>
                <td>Tanggal Lahir</td>
                <td>{{ $birthDate }}</td>
            </tr>
            @endif
            @if($phone)
            <tr>
                <td>Nomor Telepon</td>
                <td>{{ $phone }}</td>
            </tr>
            @endif
            @if($email)
            <tr>
                <td>Email</td>
                <td>{{ $email }}</td>
            </tr>
            @endif
            @if($address)
            <tr>
                <td>Alamat</td>
                <td>{{ $address }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="info-box">
        <h3 style="margin-top: 0;">Langkah Selanjutnya</h3>
        <ol>
            <li>Simpan nomor rekam medis <strong>{{ $mrn }}</strong> dengan baik</li>
            <li>Bawa identitas diri (KTP/KK) saat berkunjung</li>
            <li>Datang 15 menit lebih awal dari jadjan janji temu</li>
            <li>Jika memiliki BPJS, bawa kartu BPJS yang masih berlaku</li>
        </ol>
    </div>

    @if($temporaryPassword)
    <div class="alert-box">
        <h3 style="margin-top: 0; color: #856404;">Akses Portal Pasien</h3>
        <p>Anda dapat mengakses portal pasien dengan informasi berikut:</p>
        <table>
            <tr>
                <td>Username/Email</td>
                <td>{{ $email }}</td>
            </tr>
            <tr>
                <td>Password Sementara</td>
                <td><span class="highlight">{{ $temporaryPassword }}</span></td>
            </tr>
        </table>
        <p><strong>Penting:</strong> Segera ubah password Anda setelah login pertama kali.</p>
        <a href="{{ url('/patient-portal/login') }}" class="button">Login Portal Pasien</a>
    </div>
    @endif

    <p>Terima kasih telah mempercayakan kesehatan Anda kepada kami. Jika Anda memiliki pertanyaan, silakan hubungi kami di <strong>{{ $hospitalPhone }}</strong>.</p>

    <p>Salam sehat,<br>
    <strong>Tim {{ $hospitalName }}</strong></p>
</x-emails.layout>
