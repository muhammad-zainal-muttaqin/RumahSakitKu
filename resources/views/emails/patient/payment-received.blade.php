<x-emails.layout>
    <h2>Konfirmasi Pembayaran</h2>

    <p>Halo <strong>{{ $patientName }}</strong>,</p>

    <div class="success-box" style="text-align: center;">
        <h3 style="margin-top: 0; color: #155724;">✓ Pembayaran Berhasil</h3>
        <p style="font-size: 24px; margin: 10px 0;"><strong>{{ $amount }}</strong></p>
    </div>

    <div class="info-box">
        <h3 style="margin-top: 0;">Detail Pembayaran</h3>
        <table>
            <tr>
                <td>Nomor Pembayaran</td>
                <td><span class="highlight">{{ $paymentNumber }}</span></td>
            </tr>
            @if($invoiceNumber)
            <tr>
                <td>Nomor Tagihan</td>
                <td>{{ $invoiceNumber }}</td>
            </tr>
            @endif
            <tr>
                <td>Tanggal</td>
                <td>{{ $paymentDate }}</td>
            </tr>
            @if($paymentTime)
            <tr>
                <td>Waktu</td>
                <td>{{ $paymentTime }}</td>
            </tr>
            @endif
            <tr>
                <td>Jumlah</td>
                <td><strong>{{ $amount }}</strong></td>
            </tr>
            <tr>
                <td>Metode Pembayaran</td>
                <td>{{ $paymentMethod }}</td>
            </tr>
            @if($paymentType)
            <tr>
                <td>Tipe Pembayaran</td>
                <td>{{ $paymentType }}</td>
            </tr>
            @endif
            @if($referenceNumber)
            <tr>
                <td>Nomor Referensi</td>
                <td>{{ $referenceNumber }}</td>
            </tr>
            @endif
            @if($bankName)
            <tr>
                <td>Bank</td>
                <td>{{ $bankName }}</td>
            </tr>
            @endif
            @if($receivedBy)
            <tr>
                <td>Diterima Oleh</td>
                <td>{{ $receivedBy }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if($remainingBalance && $remainingBalance !== 'Rp 0')
    <div class="alert-box">
        <h3 style="margin-top: 0; color: #856404;">Sisa Pembayaran</h3>
        <p>Masih terdapat sisa pembayaran sebesar <strong>{{ $remainingBalance }}</strong></p>
    </div>
    @else
    <div class="success-box">
        <h3 style="margin-top: 0; color: #155724;">Tagihan Lunas</h3>
        <p>Terima kasih! Seluruh tagihan Anda telah lunas.</p>
    </div>
    @endif

    @if($receiptUrl)
    <div class="text-center">
        <a href="{{ $receiptUrl }}" class="button">Unduh Kwitansi</a>
    </div>
    @endif

    <p style="margin-top: 30px;">Simpan email ini sebagai bukti pembayaran Anda. Kwitansi resmi dapat diambil di bagian kasir.</p>

    <p>Terima kasih telah menggunakan layanan {{ $hospitalName }}.</p>

    <p>Salam,<br>
    <strong>Tim {{ $hospitalName }}</strong></p>
</x-emails.layout>
