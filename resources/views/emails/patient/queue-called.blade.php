<x-emails.layout>
    <h2>🎉 Antrian Anda Dipanggil!</h2>

    <p>Halo <strong>{{ $patientName }}</strong>,</p>

    <div class="success-box" style="text-align: center;">
        <h1 style="font-size: 48px; margin: 10px 0; color: #155724;">{{ $queueNumber }}</h1>
        <p style="font-size: 18px; margin: 0;">Nomor Antrian Anda</p>
    </div>

    <div class="info-box">
        <h3 style="margin-top: 0;">Informasi Pelayanan</h3>
        <table>
            <tr>
                <td>Poliklinik</td>
                <td><strong>{{ $polyclinicName }}</strong></td>
            </tr>
            <tr>
                <td>Loket</td>
                <td><span class="highlight" style="font-size: 18px;">{{ $counterNumber }}</span></td>
            </tr>
            @if($calledAt)
            <tr>
                <td>Waktu Dipanggil</td>
                <td>{{ $calledAt }}</td>
            </tr>
            @endif
            @if($waitingTime)
            <tr>
                <td>Waktu Menunggu</td>
                <td>{{ $waitingTime }} menit</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="alert-box">
        <h3 style="margin-top: 0; color: #856404;">⚠️ Segera Datang ke Loket</h3>
        <p style="font-size: 16px; margin: 0;">
            Silakan segera menuju <strong>Loket {{ $counterNumber }}</strong> di <strong>{{ $polyclinicName }}</strong>.
        </p>
        @if($estimatedWaitMinutes)
        <p style="margin-top: 10px;">
            Estimasi waktu tunggu: <strong>{{ $estimatedWaitMinutes }} menit</strong>
        </p>
        @endif
    </div>

    <div class="text-center">
        <p style="font-size: 18px;">🚶‍♂️ <strong>Mohon segera datang ke loket yang dituju</strong> 🚶‍♀️</p>
    </div>

    <p>Jika Anda tidak hadir dalam 5 menit, nomor antrian Anda mungkin akan dilewati.</p>

    <p>Terima kasih atas kesabaran Anda,<br>
    <strong>Tim {{ $hospitalName }}</strong></p>
</x-emails.layout>
