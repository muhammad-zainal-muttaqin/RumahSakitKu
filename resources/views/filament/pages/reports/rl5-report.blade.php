<x-filament-panels::page>
    {{-- Filters --}}
    <x-filament-panels::form wire:submit="updateFilters">
        {{ $this->form }}
    </x-filament-panels::form>

    @php
        $summary = $this->getSummaryData();
        $ageDistribution = $this->getAgeDistribution();
    @endphp

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl bg-danger-50 p-4 text-center dark:bg-danger-900/20">
            <p class="text-3xl font-bold text-danger-600 dark:text-danger-400">
                {{ number_format($summary['total_deaths'] ?? 0) }}
            </p>
            <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">Total Kematian</p>
        </div>

        @if(isset($summary['under_48h']))
            <div class="rounded-xl bg-warning-50 p-4 text-center dark:bg-warning-900/20">
                <p class="text-3xl font-bold text-warning-600 dark:text-warning-400">
                    {{ number_format($summary['under_48h']) }}
                </p>
                <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">&lt; 48 Jam</p>
            </div>

            <div class="rounded-xl bg-orange-50 p-4 text-center dark:bg-orange-900/20">
                <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                    {{ number_format($summary['over_48h']) }}
                </p>
                <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">&gt; 48 Jam</p>
            </div>

            <div class="rounded-xl bg-gray-100 p-4 text-center dark:bg-gray-800">
                <p class="text-3xl font-bold text-gray-600 dark:text-gray-400">
                    {{ number_format($summary['gdr'], 2) }}%
                </p>
                <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">GDR</p>
            </div>
        @endif
    </div>

    @if(count($ageDistribution) > 0)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-pie style="width: 1.25rem; height: 1.25rem;" />
                    Distribusi Umur Kematian
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-3 text-left font-medium text-gray-600 dark:text-gray-400">Kelompok Umur</th>
                            <th class="py-3 text-center font-medium text-gray-600 dark:text-gray-400">Laki-laki</th>
                            <th class="py-3 text-center font-medium text-gray-600 dark:text-gray-400">Perempuan</th>
                            <th class="py-3 text-center font-medium text-gray-600 dark:text-gray-400">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ageDistribution as $age)
                            <tr class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                                <td class="py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $age['age_group'] }}
                                </td>
                                <td class="py-3 text-center text-primary-600 dark:text-primary-400">
                                    {{ number_format($age['male']) }}
                                </td>
                                <td class="py-3 text-center text-danger-600 dark:text-danger-400">
                                    {{ number_format($age['female']) }}
                                </td>
                                <td class="py-3 text-center font-bold text-gray-900 dark:text-white">
                                    {{ number_format($age['count']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- Detailed Table --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-table-cells style="width: 1.25rem; height: 1.25rem;" />
                Detail Kematian
            </div>
        </x-slot>

        {{ $this->table }}
    </x-filament::section>

    {{-- NDR Note --}}
    @if(isset($summary['ndr']))
        <div class="mt-4 rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-800">
            <p class="font-medium text-gray-900 dark:text-white">Keterangan:</p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-gray-600 dark:text-gray-400">
                <li><strong>GDR (Gross Death Rate):</strong> {{ number_format($summary['gdr'], 2) }}% - Angka kematian kasar dari total pasien keluar</li>
                <li><strong>NDR (Net Death Rate):</strong> {{ number_format($summary['ndr'], 2) }}% - Angka kematian bersih (pasien meninggal >48 jam)</li>
                <li>Pasien meninggal &lt;48 jam: {{ $summary['under_48h'] ?? 0 }} orang</li>
                <li>Pasien meninggal &gt;48 jam: {{ $summary['over_48h'] ?? 0 }} orang</li>
            </ul>
        </div>
    @endif
</x-filament-panels::page>
