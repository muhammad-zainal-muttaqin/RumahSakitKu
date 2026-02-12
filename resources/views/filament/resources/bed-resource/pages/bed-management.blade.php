<x-filament-panels::page>
    {{-- Filters --}}
    <x-filament::section>
        <div class="flex flex-wrap gap-4 items-end">
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Filter Lantai
                </label>
                <select 
                    wire:model.live="selectedFloor"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Semua Lantai</option>
                    @foreach($this->floors as $floor)
                        <option value="{{ $floor }}">{{ $floor }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Filter Kamar
                </label>
                <select 
                    wire:model.live="selectedRoom"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Semua Kamar</option>
                    @foreach($this->rooms as $room)
                        <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->room_class }})</option>
                    @endforeach
                </select>
            </div>

            <x-filament::button
                wire:click="$set('selectedFloor', null); $set('selectedRoom', null)"
                color="gray"
                size="sm"
            >
                Reset Filter
            </x-filament::button>
        </div>
    </x-filament::section>

    {{-- Statistics --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->bedStats['total'] }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Total Bed</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-success-200 dark:border-success-800 p-4 text-center">
            <div class="text-2xl font-bold text-success-600">{{ $this->bedStats['kosong'] }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Kosong</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-danger-200 dark:border-danger-800 p-4 text-center">
            <div class="text-2xl font-bold text-danger-600">{{ $this->bedStats['terisi'] }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Terisi</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-warning-200 dark:border-warning-800 p-4 text-center">
            <div class="text-2xl font-bold text-warning-600">{{ $this->bedStats['reserved'] }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Dipesan</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-info-200 dark:border-info-800 p-4 text-center">
            <div class="text-2xl font-bold text-info-600">{{ $this->bedStats['cleaning'] }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Cleaning</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ $this->bedStats['maintenance'] }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Maintenance</div>
        </div>
    </div>

    {{-- Bed Map --}}
    <div class="space-y-6">
        @foreach($this->rooms as $room)
            @if(!$selectedRoom || $selectedRoom == $room->id)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span>{{ $room->name }}</span>
                                <x-filament::badge 
                                    :color="$this->getRoomClassColor($room->room_class)"
                                    size="sm"
                                >
                                    {{ $room->room_class }}
                                </x-filament::badge>
                                <span class="text-sm text-gray-500">Lantai {{ $room->floor }}</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                Tersedia: {{ $room->available_beds }}/{{ $room->total_beds }}
                            </div>
                        </div>
                    </x-slot>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach($room->beds as $bed)
                            <div class="relative bg-white dark:bg-gray-800 rounded-lg border-2 
                                {{ $bed->status === 'kosong' ? 'border-success-500' : '' }}
                                {{ $bed->status === 'terisi' ? 'border-danger-500' : '' }}
                                {{ $bed->status === 'reserved' ? 'border-warning-500' : '' }}
                                {{ $bed->status === 'maintenance' ? 'border-gray-500' : '' }}
                                {{ $bed->status === 'cleaning' ? 'border-info-500' : '' }}
                                p-4 text-center transition-all hover:shadow-md"
                            >
                                {{-- Status Badge --}}
                                <div class="absolute -top-2 -right-2">
                                    <x-filament::badge 
                                        :color="$this->getStatusColor($bed->status)"
                                        size="sm"
                                        class="rounded-full"
                                    >
                                        <x-filament::icon
                                            :icon="$this->getStatusIcon($bed->status)"
                                            style="width: 0.75rem; height: 0.75rem;"
                                        />
                                    </x-filament::badge>
                                </div>

                                {{-- Bed Number --}}
                                <div class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                                    Bed {{ $bed->bed_number }}
                                </div>

                                {{-- Bed Name --}}
                                @if($bed->bed_name)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                        {{ $bed->bed_name }}
                                    </div>
                                @endif

                                {{-- Patient Info (if occupied) --}}
                                @if($bed->status === 'terisi' && $bed->currentVisit)
                                    <div class="text-xs text-gray-600 dark:text-gray-300 mb-2 p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                        <div class="font-medium truncate">{{ $bed->currentVisit->patient?->name ?? 'N/A' }}</div>
                                        <div class="text-gray-400">{{ $bed->currentVisit->visit_number }}</div>
                                    </div>
                                @endif

                                {{-- Occupancy Duration --}}
                                @if($bed->status === 'terisi' && $bed->occupied_at)
                                    <div class="text-xs text-gray-500 mb-2">
                                        {{ $bed->occupied_at->diffForHumans(['parts' => 1]) }}
                                    </div>
                                @endif

                                {{-- Actions --}}
                                <div class="flex justify-center gap-1 mt-2">
                                    @if($bed->status === 'kosong')
                                        <x-filament::button
                                            wire:click="occupyBed({{ $bed->id }})"
                                            size="xs"
                                            color="success"
                                            icon="heroicon-m-user-plus"
                                            tooltip="Isi Bed"
                                        />
                                        <x-filament::button
                                            wire:click="setReserved({{ $bed->id }})"
                                            size="xs"
                                            color="warning"
                                            icon="heroicon-m-clock"
                                            tooltip="Pesan"
                                        />
                                        <x-filament::button
                                            wire:click="setMaintenance({{ $bed->id }})"
                                            size="xs"
                                            color="gray"
                                            icon="heroicon-m-wrench"
                                            tooltip="Maintenance"
                                        />
                                    @endif

                                    @if($bed->status === 'terisi')
                                        <x-filament::button
                                            wire:click="vacateBed({{ $bed->id }})"
                                            size="xs"
                                            color="success"
                                            icon="heroicon-m-arrow-right-start-on-rectangle"
                                            tooltip="Kosongkan"
                                        />
                                        <x-filament::button
                                            :href="route('filament.admin.resources.inpatients.view', $bed->current_visit_id)"
                                            tag="a"
                                            size="xs"
                                            color="primary"
                                            icon="heroicon-m-eye"
                                            tooltip="Lihat Pasien"
                                        />
                                    @endif

                                    @if(in_array($bed->status, ['reserved', 'maintenance']))
                                        <x-filament::button
                                            wire:click="setAvailable({{ $bed->id }})"
                                            size="xs"
                                            color="success"
                                            icon="heroicon-m-check"
                                            tooltip="Jadikan Tersedia"
                                        />
                                        <x-filament::button
                                            wire:click="setCleaning({{ $bed->id }})"
                                            size="xs"
                                            color="info"
                                            icon="heroicon-m-sparkles"
                                            tooltip="Cleaning"
                                        />
                                    @endif

                                    @if($bed->status === 'cleaning')
                                        <x-filament::button
                                            wire:click="setAvailable({{ $bed->id }})"
                                            size="xs"
                                            color="success"
                                            icon="heroicon-m-check"
                                            tooltip="Jadikan Tersedia"
                                        />
                                        <x-filament::button
                                            wire:click="setMaintenance({{ $bed->id }})"
                                            size="xs"
                                            color="gray"
                                            icon="heroicon-m-wrench"
                                            tooltip="Maintenance"
                                        />
                                    @endif
                                </div>

                                {{-- Status Label --}}
                                <div class="mt-2 text-xs font-medium
                                    {{ $bed->status === 'kosong' ? 'text-success-600' : '' }}
                                    {{ $bed->status === 'terisi' ? 'text-danger-600' : '' }}
                                    {{ $bed->status === 'reserved' ? 'text-warning-600' : '' }}
                                    {{ $bed->status === 'maintenance' ? 'text-gray-600' : '' }}
                                    {{ $bed->status === 'cleaning' ? 'text-info-600' : '' }}">
                                    {{ $this->getStatusLabel($bed->status) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        @endforeach
    </div>

    {{-- Legend --}}
    <x-filament::section>
        <x-slot name="heading">Keterangan</x-slot>
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded border-2 border-success-500 bg-success-50"></div>
                <span>Kosong (Tersedia)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded border-2 border-danger-500 bg-danger-50"></div>
                <span>Terisi</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded border-2 border-warning-500 bg-warning-50"></div>
                <span>Dipesan</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded border-2 border-info-500 bg-info-50"></div>
                <span>Cleaning</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded border-2 border-gray-500 bg-gray-50"></div>
                <span>Maintenance</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
