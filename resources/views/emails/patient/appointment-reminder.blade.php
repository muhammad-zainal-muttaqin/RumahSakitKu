<x-emails.layout>
    <h2>Pengingat Janji Temu</h2>

    <p>Halo <strong>{{ $patientName }}</strong>,</p>

    <p>Ini adalah pengingat untuk janji temu Anda di {{ $hospitalName }}:</p>

    <div class="info-box">
        <h3 style="margin-top: 0;">Detail Janji Temu</h3>
        <table>
            <tr>
                <td>Nomor Kunjungan</td>
                <td><span class="highlight">{{ $visitNumber }}</span></td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td><strong>{{ $visitDate }}</strong></td>
            </tr>
            @if($visitTime)
            <tr>
                <td>Waktu</td>
                <td><strong>{{ $visitTime }}</strong></td>
            </tr>
            @endif
            <tr>
                <td>Poliklinik</td>
                <td>{{ $polyclinicName }}</td>
            </tr>
            <tr>
                <td>Dokter</td>
                <td>{{ $doctorName }}</td>
            </tr>
            @if($complaint)
            <tr>
                <td>Keluhan</td>
                <td>{{ $complaint }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="alert-box">
        <h3 style="margin-top: 0; color: #856404;">Informasi Penting</h3>
        <ul>
            <li>Harap datang <strong>15 menit lebih awal</strong> untuk registrasi ulang</li>
            <li>Bawa <strong>Kartu Identitas</strong> (KTP/KK/Paspor)</li>
            <li>Bawa <strong>Kartu BPJS</strong> (jika berlaku)</li>
            <li>Bawa <strong>hasil pemeriksaan sebelumnya</strong> (jika ada)</li>
        </ul>
    </div>

    @if($additionalNotes)
    <div class="info-box">
        <h3 style="margin-top: 0;">Catatan Tambahan</h3>
        <p>{{ $additionalNotes }}</p>
    </div>
    @endif

    <div class="text-center">
        <a href="{{ $checkInUrl }}" class="button">Check-in Online</a>
    </div>

    <p><strong>Lokasi:</strong><br>
    {{ $hospitalAddress }}</p>

    <p>Jika Anda perlu membatalkan atau mengubah jadwal, silakan hubungi kami di <strong>{{ $hospitalPhone }}</strong> atau melalui aplikasi.</p>

    <p>Terima kasih,<br>
    <strong>Tim {{ $hospitalName }}</strong></p>
</x-emails.layout>
