<x-emails.layout>
    <h2>Hasil Laboratorium Tersedia</h2>

    <p>Halo <strong>{{ $patientName }}</strong>,</p>

    @if($hasCriticalResults)
    <div class="danger-box">
        <h3 style="margin-top: 0; color: #721c24;">⚠️ PERHATIAN</h3>
        <p><strong>Terdapat hasil kritis pada pemeriksaan Anda. Segera hubungi dokter atau kunjungi fasilitas kesehatan terdekat.</strong></p>
    </div>
    @elseif($hasAbnormalResults)
    <div class="alert-box">
        <h3 style="margin-top: 0; color: #856404;">📋 Hasil Perlu Perhatian</h3>
        <p>Terdapat hasil abnormal pada pemeriksaan Anda. Silakan konsultasikan dengan dokter.</p>
    </div>
    @else
    <div class="success-box">
        <h3 style="margin-top: 0; color: #155724;">✓ Hasil Tersedia</h3>
        <p>Hasil pemeriksaan laboratorium Anda telah tersedia.</p>
    </div>
    @endif

    <div class="info-box">
        <h3 style="margin-top: 0;">Informasi Pemeriksaan</h3>
        <table>
            <tr>
                <td>Nomor Order</td>
                <td><span class="highlight">{{ $orderNumber }}</span></td>
            </tr>
            <tr>
                <td>Tanggal Pemeriksaan</td>
                <td>{{ $orderDate }}</td>
            </tr>
            @if($doctorName)
            <tr>
                <td>Dokter Pengirim</td>
                <td>{{ $doctorName }}</td>
            </tr>
            @endif
            @if($priority)
            <tr>
                <td>Prioritas</td>
                <td><strong>{{ ucfirst($priority) }}</strong></td>
            </tr>
            @endif
            @if($diagnosis)
            <tr>
                <td>Diagnosa</td>
                <td>{{ $diagnosis }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="info-box">
        <h3 style="margin-top: 0;">Ringkasan Hasil</h3>
        <table>
            <tr>
                <td>Total Pemeriksaan</td>
                <td>{{ $totalResults }} item</td>
            </tr>
            @if($abnormalResults > 0)
            <tr style="color: #856404;">
                <td>Hasil Abnormal</td>
                <td><strong>{{ $abnormalResults }} item</strong></td>
            </tr>
            @endif
            @if($criticalResults > 0)
            <tr style="color: #721c24;">
                <td>Hasil Kritis</td>
                <td><strong>{{ $criticalResults }} item</strong></td>
            </tr>
            @endif
        </table>
    </div>

    @if($clinicalNotes)
    <div class="info-box">
        <h3 style="margin-top: 0;">Catatan Klinis</h3>
        <p>{{ $clinicalNotes }}</p>
    </div>
    @endif

    <div class="alert-box">
        <h3 style="margin-top: 0; color: #856404;">Cara Mengakses Hasil</h3>
        <ol>
            <li>Klik tombol "Lihat Hasil Lengkap" di bawah</li>
            @if($accessCode)
            <li>Masukkan kode akses: <span class="highlight">{{ $accessCode }}</span></li>
            @endif
            <li>Hasil dapat diunduh dalam format PDF</li>
        </ol>
    </div>

    <div class="text-center">
        <a href="{{ $resultsUrl }}" class="button">Lihat Hasil Lengkap</a>
    </div>

    <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
        <h4 style="margin-top: 0;">Catatan Penting:</h4>
        <ul>
            <li>Hasil laboratorium adalah alat bantu diagnosis, bukan diagnosis akhir</li>
            <li>Konsultasikan hasil dengan dokter Anda</li>
            <li>Jangan mengambil keputusan medis berdasarkan hasil sendiri</li>
            <li>Simpan hasil pemeriksaan untuk arsip medis Anda</li>
        </ul>
    </div>

    @if($hasCriticalResults || $hasAbnormalResults)
    <div class="danger-box">
        <h3 style="margin-top: 0; color: #721c24;">Segera Hubungi Dokter</h3>
        <p>Jika Anda mengalami gejala atau memiliki pertanyaan tentang hasil, segera hubungi:</p>
        <ul>
            <li>Dokter pengirim pemeriksaan</li>
            <li>Call Center: <strong>{{ $hospitalPhone }}</strong></li>
            <li>IGD (Emergency): <strong>{{ $hospitalPhone }}</strong></li>
        </ul>
    </div>
    @endif

    <p>Terima kasih,<br>
    <strong>Tim Laboratorium {{ $hospitalName }}</strong></p>
</x-emails.layout>
