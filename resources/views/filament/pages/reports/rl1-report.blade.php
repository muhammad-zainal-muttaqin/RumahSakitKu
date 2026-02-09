<x-filament-panels::page>
    {{-- Filters --}}
    <x-filament-panels::form wire:submit="updateFilters">
        {{ $this->form }}
    </x-filament-panels::form>

    @php
        $data = $this->getHospitalData();
    @endphp

    <div class="grid grid-cols-1 gap-6">
        {{-- RL 1.1 - Data Dasar RS --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-building-library class="h-5 w-5" />
                    {{ $data['rl1_1']['title'] }}
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nama Rumah Sakit</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $data['rl1_1']['data']['nama_rs'] }}
                    </p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kode RS</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $data['rl1_1']['data']['kode_rs'] }}
                    </p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jenis RS</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $data['rl1_1']['data']['jenis_rs'] }}
                    </p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kelas RS</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $data['rl1_1']['data']['kelas_rs'] }}
                    </p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Tempat Tidur</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ number_format($data['rl1_1']['data']['total_beds']) }}
                    </p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Direktur RS</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $data['rl1_1']['data']['director_name'] }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        {{-- RL 1.2 - Indikator Pelayanan --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-bar class="h-5 w-5" />
                    {{ $data['rl1_2']['title'] }}
                </div>
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                {{-- BOR --}}
                <div class="rounded-xl bg-primary-50 p-4 text-center dark:bg-primary-900/20">
                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        {{ $data['rl1_2']['data']['bor'] }}%
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">BOR</p>
                    <p class="text-xs text-gray-500">Bed Occupancy Rate</p>
                </div>

                {{-- LOS --}}
                <div class="rounded-xl bg-success-50 p-4 text-center dark:bg-success-900/20">
                    <p class="text-3xl font-bold text-success-600 dark:text-success-400">
                        {{ $data['rl1_2']['data']['los'] }}
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">LOS</p>
                    <p class="text-xs text-gray-500">Length of Stay (hari)</p>
                </div>

                {{-- TOI --}}
                <div class="rounded-xl bg-warning-50 p-4 text-center dark:bg-warning-900/20">
                    <p class="text-3xl font-bold text-warning-600 dark:text-warning-400">
                        {{ $data['rl1_2']['data']['toi'] }}
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">TOI</p>
                    <p class="text-xs text-gray-500">Turn Over Interval</p>
                </div>

                {{-- BTO --}}
                <div class="rounded-xl bg-info-50 p-4 text-center dark:bg-info-900/20">
                    <p class="text-3xl font-bold text-info-600 dark:text-info-400">
                        {{ $data['rl1_2']['data']['bto'] }}
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">BTO</p>
                    <p class="text-xs text-gray-500">Bed Turn Over</p>
                </div>

                {{-- GDR --}}
                <div class="rounded-xl bg-danger-50 p-4 text-center dark:bg-danger-900/20">
                    <p class="text-3xl font-bold text-danger-600 dark:text-danger-400">
                        {{ $data['rl1_2']['data']['gdr'] }}%
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">GDR</p>
                    <p class="text-xs text-gray-500">Gross Death Rate</p>
                </div>

                {{-- NDR --}}
                <div class="rounded-xl bg-gray-100 p-4 text-center dark:bg-gray-800">
                    <p class="text-3xl font-bold text-gray-600 dark:text-gray-400">
                        {{ $data['rl1_2']['data']['ndr'] }}%
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">NDR</p>
                    <p class="text-xs text-gray-500">Net Death Rate</p>
                </div>
            </div>

            {{-- Legend --}}
            <div class="mt-4 rounded-lg bg-gray-50 p-3 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                <p class="font-medium">Keterangan Indikator:</p>
                <ul class="mt-1 list-inside list-disc space-y-1">
                    <li><strong>BOR (Bed Occupancy Rate):</strong> Persentase penggunaan tempat tidur. Target: 75-85%</li>
                    <li><strong>LOS (Length of Stay):</strong> Rata-rata lama pasien dirawat. Target: Sesuai jenis penyakit</li>
                    <li><strong>TOI (Turn Over Interval):</strong> Rata-rata waktu tempat tidur kosong. Target: 1-3 hari</li>
                    <li><strong>BTO (Bed Turn Over):</strong> Frekuensi pemakaian tempat tidur. Target: 40-50 kali/tahun</li>
                    <li><strong>GDR (Gross Death Rate):</strong> Angka kematian kasar. Target: &lt; 20 per mille</li>
                    <li><strong>NDR (Net Death Rate):</strong> Angka kematian bersih. Target: &lt; 10 per mille</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
