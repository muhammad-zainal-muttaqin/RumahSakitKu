<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-signal class="w-5 h-5 text-primary-500" />
                <span>Live Display Antrian</span>
                <span class="text-xs text-gray-400 font-normal ml-2">(Auto refresh setiap 5 detik)</span>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Current Active Queues Per Polyclinic --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($this->getCurrentQueues() as $queueData)
                    @php
                        $polyclinic = $queueData['polyclinic'];
                        $current = $queueData['current'];
                        $waitingCount = $queueData['waiting_count'];
                        $estimatedTime = $queueData['estimated_time'];
                    @endphp
                    
                    <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {{-- Header --}}
                        <div class="bg-primary-500 px-4 py-2">
                            <h4 class="text-white font-medium text-sm truncate">{{ $polyclinic->name }}</h4>
                        </div>
                        
                        {{-- Content --}}
                        <div class="p-4">
                            @if($current)
                                <div class="text-center mb-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Sedang Dipanggil</p>
                                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $current->display_number }}</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 truncate">{{ $current->patient?->name ?? '-' }}</p>
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $current->status === 'in_progress' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                            <x-dynamic-component 
                                                :component="$current->status === 'in_progress' ? 'heroicon-o-user' : 'heroicon-o-speaker-wave'" 
                                                class="w-3 h-3 mr-1" 
                                            />
                                            {{ $current->status_label }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <div class="text-center mb-3 py-4">
                                    <x-heroicon-o-pause-circle class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada antrian aktif</p>
                                </div>
                            @endif

                            {{-- Stats --}}
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                        <x-heroicon-o-clock class="w-4 h-4" />
                                        Menunggu
                                    </span>
                                    <span class="font-semibold {{ $waitingCount > 10 ? 'text-danger-500' : ($waitingCount > 5 ? 'text-warning-500' : 'text-success-500') }}">
                                        {{ $waitingCount }}
                                    </span>
                                </div>
                                @if($estimatedTime > 0 && $waitingCount > 0)
                                    <div class="flex items-center justify-between text-sm mt-1">
                                        <span class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <x-heroicon-o-clock class="w-4 h-4" />
                                            Estimasi
                                        </span>
                                        <span class="text-gray-700 dark:text-gray-300">
                                            ~{{ $estimatedTime }} menit
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Recent Calls & Next In Line --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Recent Calls --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-speaker-wave class="w-4 h-4 text-primary-500" />
                            Panggilan Terbaru
                        </h4>
                    </div>
                    <div class="p-2">
                        <div class="space-y-1 max-h-48 overflow-y-auto">
                            @forelse($this->getRecentCalls() as $call)
                                <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 flex items-center justify-center text-xs font-bold">
                                            {{ $call->display_number }}
                                        </span>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $call->patient?->name ?? '-' }}</p>
                                            <p class="text-xs text-gray-500">{{ $call->polyclinic?->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $call->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($call->status_color === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                            {{ $call->status_label }}
                                        </span>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $call->called_at?->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-gray-500">
                                    <p class="text-sm">Belum ada panggilan hari ini</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Next In Line --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-arrow-right-circle class="w-4 h-4 text-success-500" />
                            Antrian Berikutnya
                        </h4>
                    </div>
                    <div class="p-2">
                        <div class="space-y-1 max-h-48 overflow-y-auto">
                            @forelse($this->getNextInLine() as $next)
                                <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 flex items-center justify-center text-xs font-bold">
                                            {{ $next->display_number }}
                                        </span>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $next->patient?->name ?? '-' }}</p>
                                            <p class="text-xs text-gray-500">{{ $next->polyclinic?->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 text-xs text-gray-500">
                                        <x-heroicon-o-clock class="w-3 h-3" />
                                        {{ $next->created_at?->diffForHumans() }}
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-gray-500">
                                    <p class="text-sm">Tidak ada antrian berikutnya</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
