<x-filament-panels::page>
    {{-- Filters --}}
    <x-filament-panels::form wire:submit="updateFilters">
        {{ $this->form }}
    </x-filament-panels::form>

    @php
        $stats = $this->getStatistics();
    @endphp

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-4">
        <div class="rounded-xl bg-primary-50 p-4 text-center dark:bg-primary-900/20">
            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                {{ number_format($stats['total_doctors']) }}
            </p>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Dokter</p>
        </div>

        <div class="rounded-xl bg-success-50 p-4 text-center dark:bg-success-900/20">
            <p class="text-2xl font-bold text-success-600 dark:text-success-400">
                {{ number_format($stats['specialists']) }}
            </p>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Dokter Spesialis</p>
        </div>

        <div class="rounded-xl bg-info-50 p-4 text-center dark:bg-info-900/20">
            <p class="text-2xl font-bold text-info-600 dark:text-info-400">
                {{ number_format($stats['general_practitioners']) }}
            </p>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Dokter Umum</p>
        </div>

        <div class="rounded-xl bg-warning-50 p-4 text-center dark:bg-warning-900/20">
            <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                {{ number_format($stats['nurses']) }}
            </p>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Perawat</p>
        </div>

        <div class="rounded-xl bg-danger-50 p-4 text-center dark:bg-danger-900/20">
            <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">
                {{ number_format($stats['midwives']) }}
            </p>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bidan</p>
        </div>

        <div class="rounded-xl bg-purple-50 p-4 text-center dark:bg-purple-900/20">
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ number_format($stats['pharmacists']) }}
            </p>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Farmasi</p>
        </div>

        <div class="rounded-xl bg-gray-100 p-4 text-center dark:bg-gray-800">
            <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">
                {{ number_format($stats['total_employees']) }}
            </p>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Pegawai</p>
        </div>
    </div>

    {{-- Table --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-users class="h-5 w-5" />
                Daftar Tenaga Medis
            </div>
        </x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
