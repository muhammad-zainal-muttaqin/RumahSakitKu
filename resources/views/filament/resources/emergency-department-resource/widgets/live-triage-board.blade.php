<x-filament-widgets::widget class="fi-wi-live-triage-board">
    <div class="space-y-4">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <x-heroicon-o-heart class="inline-block w-5 h-5 mr-2" />
                Papan Triase IGD - Live
            </h3>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Auto-refresh: 5 detik
            </span>
        </div>

        {{-- Triage Board Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
            {{-- Red Category (Emergency) --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 border-danger-500 overflow-hidden">
                <div class="bg-danger-500 text-white px-4 py-2 font-semibold flex items-center justify-between">
                    <span class="flex items-center">
                        <x-heroicon-m-exclamation-circle class="w-5 h-5 mr-2" />
                        MERAH (Emergency)
                    </span>
                    <span class="bg-white text-danger-600 px-2 py-0.5 rounded-full text-sm font-bold">
                        {{ count($redPatients) }}
                    </span>
                </div>
                <div class="p-2 space-y-2 max-h-96 overflow-y-auto">
                    @forelse($redPatients as $patient)
                        <div class="bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg p-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-semibold text-danger-900 dark:text-danger-100 text-sm">
                                        {{ $patient->patient?->name ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-danger-700 dark:text-danger-300">
                                        No. RM: {{ $patient->patient?->medical_record_number ?? '-' }}
                                    </p>
                                </div>
                                <span class="text-xs font-bold text-danger-600 dark:text-danger-400">
                                    {{ $patient->wait_time }}
                                </span>
                            </div>
                            <p class="text-xs text-danger-700 dark:text-danger-300 mt-1 truncate">
                                {{ $patient->medicalRecord?->assessments->first()?->chief_complaint ?? '-' }}
                            </p>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-400 text-sm">
                            Tidak ada pasien
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Yellow Category (Urgent) --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 border-warning-500 overflow-hidden">
                <div class="bg-warning-500 text-white px-4 py-2 font-semibold flex items-center justify-between">
                    <span class="flex items-center">
                        <x-heroicon-m-clock class="w-5 h-5 mr-2" />
                        KUNING (Urgent)
                    </span>
                    <span class="bg-white text-warning-600 px-2 py-0.5 rounded-full text-sm font-bold">
                        {{ count($yellowPatients) }}
                    </span>
                </div>
                <div class="p-2 space-y-2 max-h-96 overflow-y-auto">
                    @forelse($yellowPatients as $patient)
                        <div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg p-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-semibold text-warning-900 dark:text-warning-100 text-sm">
                                        {{ $patient->patient?->name ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-warning-700 dark:text-warning-300">
                                        No. RM: {{ $patient->patient?->medical_record_number ?? '-' }}
                                    </p>
                                </div>
                                <span class="text-xs font-bold text-warning-600 dark:text-warning-400">
                                    {{ $patient->wait_time }}
                                </span>
                            </div>
                            <p class="text-xs text-warning-700 dark:text-warning-300 mt-1 truncate">
                                {{ $patient->medicalRecord?->assessments->first()?->chief_complaint ?? '-' }}
                            </p>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-400 text-sm">
                            Tidak ada pasien
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Green Category (Non-Urgent) --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 border-success-500 overflow-hidden">
                <div class="bg-success-500 text-white px-4 py-2 font-semibold flex items-center justify-between">
                    <span class="flex items-center">
                        <x-heroicon-m-check-circle class="w-5 h-5 mr-2" />
                        HIJAU (Non-Urgent)
                    </span>
                    <span class="bg-white text-success-600 px-2 py-0.5 rounded-full text-sm font-bold">
                        {{ count($greenPatients) }}
                    </span>
                </div>
                <div class="p-2 space-y-2 max-h-96 overflow-y-auto">
                    @forelse($greenPatients as $patient)
                        <div class="bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-lg p-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-semibold text-success-900 dark:text-success-100 text-sm">
                                        {{ $patient->patient?->name ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-success-700 dark:text-success-300">
                                        No. RM: {{ $patient->patient?->medical_record_number ?? '-' }}
                                    </p>
                                </div>
                                <span class="text-xs font-bold text-success-600 dark:text-success-400">
                                    {{ $patient->wait_time }}
                                </span>
                            </div>
                            <p class="text-xs text-success-700 dark:text-success-300 mt-1 truncate">
                                {{ $patient->medicalRecord?->assessments->first()?->chief_complaint ?? '-' }}
                            </p>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-400 text-sm">
                            Tidak ada pasien
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- In Progress --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 border-primary-500 overflow-hidden">
                <div class="bg-primary-500 text-white px-4 py-2 font-semibold flex items-center justify-between">
                    <span class="flex items-center">
                        <x-heroicon-m-play class="w-5 h-5 mr-2" />
                        Sedang Dilayani
                    </span>
                    <span class="bg-white text-primary-600 px-2 py-0.5 rounded-full text-sm font-bold">
                        {{ count($inProgressPatients) }}
                    </span>
                </div>
                <div class="p-2 space-y-2 max-h-96 overflow-y-auto">
                    @forelse($inProgressPatients as $patient)
                        <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg p-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-semibold text-primary-900 dark:text-primary-100 text-sm">
                                        {{ $patient->patient?->name ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-primary-700 dark:text-primary-300">
                                        {{ $patient->doctor?->name ?? 'Belum ada dokter' }}
                                    </p>
                                </div>
                                <span class="text-xs font-bold text-primary-600 dark:text-primary-400">
                                    {{ $patient->treatment_duration }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-xs text-primary-700 dark:text-primary-300 truncate flex-1 mr-2">
                                    {{ $patient->medicalRecord?->assessments->first()?->chief_complaint ?? '-' }}
                                </p>
                                @if($patient->triage_category)
                                    <span @class([
                                        'px-1.5 py-0.5 rounded text-xs font-medium',
                                        'bg-danger-100 text-danger-700' => $patient->triage_category === 'red',
                                        'bg-warning-100 text-warning-700' => $patient->triage_category === 'yellow',
                                        'bg-success-100 text-success-700' => $patient->triage_category === 'green',
                                        'bg-gray-100 text-gray-700' => $patient->triage_category === 'black',
                                    ])>
                                        {{ strtoupper($patient->triage_category) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-400 text-sm">
                            Tidak ada pasien
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
