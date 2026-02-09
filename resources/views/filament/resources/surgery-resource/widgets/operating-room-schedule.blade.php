<x-filament-widgets::widget>
    <x-filament::section :heading="$this->getHeading()">
        <div class="space-y-4">
            {{-- Summary Stats --}}
            <div class="flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-1">
                    <x-heroicon-o-building-office class="w-4 h-4" />
                    <span>{{ $activeRoomsCount }} OK Aktif</span>
                </div>
                <div class="flex items-center gap-1">
                    <x-heroicon-o-rectangle-stack class="w-4 h-4" />
                    <span>{{ $totalSurgeriesCount }} Total Operasi</span>
                </div>
            </div>

            {{-- Room Schedule Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach ($schedule as $roomCode => $roomData)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {{-- Room Header --}}
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-building-office class="w-5 h-5 text-primary-500" />
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ $roomData['name'] }}
                                    </span>
                                </div>
                                @if ($roomData['count'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                                        {{ $roomData['count'] }}
                                    </span>
                                @endif
                            </div>
                            
                            {{-- Room Status Indicators --}}
                            @if ($roomData['count'] > 0)
                                <div class="flex gap-2 mt-2 text-xs">
                                    @if ($roomData['in_progress'] > 0)
                                        <span class="inline-flex items-center gap-1 text-warning-600 dark:text-warning-400">
                                            <x-heroicon-m-play class="w-3 h-3" />
                                            {{ $roomData['in_progress'] }} Berlangsung
                                        </span>
                                    @endif
                                    @if ($roomData['completed'] > 0)
                                        <span class="inline-flex items-center gap-1 text-success-600 dark:text-success-400">
                                            <x-heroicon-m-check-circle class="w-3 h-3" />
                                            {{ $roomData['completed'] }} Selesai
                                        </span>
                                    @endif
                                    @if ($roomData['cito'] > 0)
                                        <span class="inline-flex items-center gap-1 text-danger-600 dark:text-danger-400">
                                            <x-heroicon-m-bolt class="w-3 h-3" />
                                            {{ $roomData['cito'] }} CITO
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Surgery List --}}
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($roomData['surgeries'] as $surgery)
                                <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            {{-- Time and Status --}}
                                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                <span class="font-medium">
                                                    {{ $surgery->start_time?->format('H:i') ?? '-' }}
                                                </span>
                                                @if ($surgery->estimated_end_time)
                                                    <span>- {{ $surgery->estimated_end_time->format('H:i') }}</span>
                                                @endif
                                                
                                                {{-- Status Badge --}}
                                                @php
                                                    $statusColors = [
                                                        'scheduled' => 'bg-info-100 text-info-800 dark:bg-info-900 dark:text-info-300',
                                                        'preparation' => 'bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-300',
                                                        'in_progress' => 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300',
                                                        'completed' => 'bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-300',
                                                    ];
                                                    $statusColor = $statusColors[$surgery->status] ?? 'bg-gray-100 text-gray-800';
                                                @endphp
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
                                                    {{ $surgery->status_label }}
                                                </span>
                                            </div>

                                            {{-- Patient Name --}}
                                            <div class="font-medium text-sm text-gray-900 dark:text-white truncate">
                                                {{ $surgery->patient?->name ?? 'Pasien tidak ditemukan' }}
                                            </div>

                                            {{-- Procedure --}}
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ Str::limit($surgery->procedure_name, 30) }}
                                            </div>

                                            {{-- Surgeon --}}
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <span class="text-gray-400">dr.</span> {{ $surgery->surgeon?->name ?? '-' }}
                                            </div>
                                        </div>

                                        {{-- Priority Indicator --}}
                                        @if (in_array($surgery->surgery_type, ['cito', 'emergency']))
                                            <x-heroicon-m-bolt class="w-4 h-4 text-danger-500 flex-shrink-0" title="CITO/Emergency" />
                                        @elseif ($surgery->surgery_type === 'urgent')
                                            <x-heroicon-m-exclamation-triangle class="w-4 h-4 text-warning-500 flex-shrink-0" title="Urgent" />
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-calendar class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
                                    <p>Tidak ada jadwal</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
