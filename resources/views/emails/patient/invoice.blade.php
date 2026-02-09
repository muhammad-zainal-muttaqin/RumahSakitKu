<x-emails.layout>
    <h2>Tagihan Pembayaran</h2>

    <p>Halo <strong>{{ $patientName }}</strong>,</p>

    <p>Berikut adalah detail tagihan pembayaran Anda di {{ $hospitalName }}:</p>

    <div class="info-box">
        <h3 style="margin-top: 0;">Informasi Tagihan</h3>
        <table>
            <tr>
                <td>Nomor Tagihan</td>
                <td><span class="highlight">{{ $invoiceNumber }}</span></td>
            </tr>
            @if($visitNumber)
            <tr>
                <td>Nomor Kunjungan</td>
                <td>{{ $visitNumber }}</td>
            </tr>
            @endif
            <tr>
                <td>Tanggal Tagihan</td>
                <td>{{ $invoiceDate }}</td>
            </tr>
            @if($dueDate)
            <tr>
                <td>Jatuh Tempo</td>
                <td>{{ $dueDate }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="info-box">
        <h3 style="margin-top: 0;">Rincian Pembayaran</h3>
        <table>
            <tr>
                <td>Subtotal</td>
                <td>{{ $subtotal }}</td>
            </tr>
            @if($discountAmount && $discountAmount !== 'Rp 0')
            <tr>
                <td>Diskon</td>
                <td style="color: #28a745;">-{{ $discountAmount }}</td>
            </tr>
            @endif
            @if($taxAmount && $taxAmount !== 'Rp 0')
            <tr>
                <td>Pajak</td>
                <td>{{ $taxAmount }}</td>
            </tr>
            @endif
            <tr style="font-size: 18px; font-weight: bold;">
                <td>Total Tagihan</td>
                <td><strong>{{ $totalAmount }}</strong></td>
            </tr>
            <tr>
                <td>Telah Dibayar</td>
                <td style="color: #28a745;">{{ $paidAmount }}</td>
            </tr>
            <tr style="font-size: 16px; font-weight: bold; color: {{ $balanceDue === 'Rp 0' ? '#28a745' : '#dc3545' }};">
                <td>Sisa Pembayaran</td>
                <td><strong>{{ $balanceDue }}</strong></td>
            </tr>
        </table>
    </div>

    @if($isPaid)
    <div class="success-box">
        <h3 style="margin-top: 0; color: #155724;">✓ LUNAS</h3>
        <p>Terima kasih, pembayaran Anda telah lunas.</p>
    </div>
    @else
    <div class="alert-box">
        <h3 style="margin-top: 0; color: #856404;">Menunggu Pembayaran</h3>
        <p>Sisa pembayaran <strong>{{ $balanceDue }}</strong> harus dilunasi.</p>
    </div>

    @if($paymentLink)
    <div class="text-center">
        <a href="{{ $paymentLink }}" class="button">Bayar Sekarang</a>
    </div>
    @endif
    @endif

    <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
        <h4 style="margin-top: 0;">Metode Pembayaran:</h4>
        <ul>
            <li>Tunai di Kasir</li>
            <li>Transfer Bank</li>
            <li>Kartu Debit/Kredit</li>
            <li>QRIS</li>
            <li>BPJS (jika berlaku)</li>
        </ul>
    </div>

    <p>Jika Anda memiliki pertanyaan tentang tagihan ini, silakan hubungi kami di <strong>{{ $hospitalPhone }}</strong>.</p>

    <p>Terima kasih,<br>
    <strong>Tim {{ $hospitalName }}</strong></p>
</x-emails.layout>
