<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Polyclinic Selector --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Pilih Poliklinik</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($this->polyclinics as $polyclinic)
                    <button
                        wire:click="selectPolyclinic({{ $polyclinic->id }})"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 {{ $selectedPolyclinicId === $polyclinic->id ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                    >
                        {{ $polyclinic->name }}
                    </button>
                @endforeach
            </div>
        </div>

        @if($selectedPolyclinicId)
            @php
                $currentQueue = $this->getCurrentQueue();
                $waitingQueues = $this->getWaitingQueues();
                $skippedQueues = $this->getSkippedQueues();
                $selectedPolyclinic = $this->getSelectedPolyclinic();
            @endphp

            {{-- Current Active Queue --}}
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-primary-100 text-sm font-medium mb-1">Antrian Aktif - {{ $selectedPolyclinic->name }}</p>
                        @if($currentQueue)
                            <h2 class="text-4xl font-bold tracking-tight">{{ $currentQueue->display_number }}</h2>
                            <p class="text-primary-100 mt-1">{{ $currentQueue->patient?->name ?? '-' }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <x-heroicon-o-clock class="w-4 h-4 text-primary-200" />
                                <span class="text-sm text-primary-100">{{ $currentQueue->called_at?->format('H:i:s') ?? '-' }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $currentQueue->status === 'in_progress' ? 'bg-blue-400 text-blue-900' : 'bg-yellow-400 text-yellow-900' }}">
                                    {{ $currentQueue->status_label }}
                                </span>
                            </div>
                        @else
                            <h2 class="text-2xl font-medium text-primary-100">Tidak ada antrian aktif</h2>
                            <p class="text-primary-200 text-sm mt-1">Belum ada pasien yang dipanggil</p>
                        @endif
                    </div>
                    <div class="hidden sm:block">
                        <x-heroicon-o-speaker-wave class="w-24 h-24 text-primary-300 opacity-50" />
                    </div>
                </div>

                @if($currentQueue)
                    <div class="flex gap-2 mt-4">
                        @if($currentQueue->status === 'called')
                            <x-filament::button
                                wire:click="markInProgress({{ $currentQueue->id }})"
                                color="info"
                                icon="heroicon-o-user"
                            >
                                Mulai Layani
                            </x-filament::button>
                        @endif
                        <x-filament::button
                            wire:click="completeQueue({{ $currentQueue->id }})"
                            color="success"
                            icon="heroicon-o-check-circle"
                        >
                            Selesai
                        </x-filament::button>
                    </div>
                @endif
            </div>

            {{-- Waiting Queue List --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Waiting --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <x-heroicon-o-clock class="w-5 h-5 text-warning-500" />
                                Menunggu ({{ $waitingQueues->count() }})
                            </h3>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-800 max-h-96 overflow-y-auto">
                        @forelse($waitingQueues as $queue)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                            <span class="text-lg font-bold text-gray-700 dark:text-gray-300">{{ $queue->display_number }}</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $queue->patient?->name ?? '-' }}</p>
                                            <p class="text-sm text-gray-500">{{ $queue->patient?->medical_record_number ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-1">
                                        <x-filament::button
                                            wire:click="callQueue({{ $queue->id }})"
                                            size="sm"
                                            color="warning"
                                            icon="heroicon-o-speaker-wave"
                                        >
                                            Panggil
                                        </x-filament::button>
                                        <x-filament::button
                                            wire:click="skipQueue({{ $queue->id }})"
                                            size="sm"
                                            color="gray"
                                            icon="heroicon-o-forward"
                                        />
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500">
                                <x-heroicon-o-check-circle class="w-12 h-12 mx-auto mb-2 opacity-50" />
                                <p>Tidak ada antrian menunggu</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Skipped --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <x-heroicon-o-forward class="w-5 h-5 text-orange-500" />
                                Dilewati ({{ $skippedQueues->count() }})
                            </h3>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-800 max-h-96 overflow-y-auto">
                        @forelse($skippedQueues as $queue)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg bg-orange-100 dark:bg-orange-900/20 flex items-center justify-center">
                                            <span class="text-lg font-bold text-orange-700 dark:text-orange-300">{{ $queue->display_number }}</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $queue->patient?->name ?? '-' }}</p>
                                            <p class="text-sm text-gray-500">{{ $queue->patient?->medical_record_number ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-1">
                                        <x-filament::button
                                            wire:click="callQueue({{ $queue->id }})"
                                            size="sm"
                                            color="warning"
                                            icon="heroicon-o-speaker-wave"
                                        >
                                            Panggil
                                        </x-filament::button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500">
                                <x-heroicon-o-check-circle class="w-12 h-12 mx-auto mb-2 opacity-50" />
                                <p>Tidak ada antrian yang dilewati</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-12 text-center">
                <x-heroicon-o-building-office-2 class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Pilih Poliklinik</h3>
                <p class="text-gray-500">Silakan pilih poliklinik untuk mengelola antrian</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
