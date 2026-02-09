<x-filament-widgets::widget class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    @php
        $totalAlerts = $lowStockCount + $longStayCount + $expiringLicenseCount + $overdueInvoiceCount;
    @endphp

    {{-- Low Stock Medicines Alert --}}
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-500/20">
                <x-heroicon-o-beaker class="h-5 w-5 text-orange-600 dark:text-orange-400" />
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stok Obat Menipis</p>
                <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $lowStockCount }}</p>
            </div>
        </div>
        @if($lowStockMedicines->isNotEmpty())
            <div class="mt-3 space-y-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                @foreach($lowStockMedicines as $medicine)
                    <div class="flex items-center justify-between text-sm">
                        <span class="truncate text-gray-700 dark:text-gray-300" title="{{ $medicine->name }}">
                            {{ Str::limit($medicine->name, 25) }}
                        </span>
                        <span @class([
                            'font-medium',
                            'text-red-600 dark:text-red-400' => $medicine->stock == 0,
                            'text-orange-600 dark:text-orange-400' => $medicine->stock > 0 && $medicine->stock <= $medicine->min_stock,
                        ])>
                            {{ number_format($medicine->stock, 0) }} {{ $medicine->unit }}
                        </span>
                    </div>
                @endforeach
            </div>
            @if($lowStockCount > 5)
                <a href="{{ route('filament.admin.resources.medicines.index') }}" class="mt-2 block text-xs text-primary-600 hover:underline dark:text-primary-400">
                    +{{ $lowStockCount - 5 }} item lainnya
                </a>
            @endif
        @else
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada obat dengan stok menipis</p>
        @endif
    </div>

    {{-- Long Stay Patients Alert --}}
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/20">
                <x-heroicon-o-clock class="h-5 w-5 text-red-600 dark:text-red-400" />
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pasien LOS > 7 Hari</p>
                <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $longStayCount }}</p>
            </div>
        </div>
        @if($longStayPatients->isNotEmpty())
            <div class="mt-3 space-y-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                @foreach($longStayPatients as $patient)
                    <div class="flex items-center justify-between text-sm">
                        <span class="truncate text-gray-700 dark:text-gray-300" title="{{ $patient['patient_name'] }}">
                            {{ Str::limit($patient['patient_name'], 20) }}
                        </span>
                        <span class="font-medium text-red-600 dark:text-red-400">
                            {{ $patient['los_days'] }} hari
                        </span>
                    </div>
                @endforeach
            </div>
            @if($longStayCount > 5)
                <a href="{{ route('filament.admin.resources.inpatients.index') }}" class="mt-2 block text-xs text-primary-600 hover:underline dark:text-primary-400">
                    +{{ $longStayCount - 5 }} pasien lainnya
                </a>
            @endif
        @else
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada pasien dengan LOS > 7 hari</p>
        @endif
    </div>

    {{-- Expiring Licenses Alert --}}
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-500/20">
                <x-heroicon-o-document-text class="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">SIP/STR Jatuh Tempo</p>
                <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $expiringLicenseCount }}</p>
            </div>
        </div>
        @if($expiringLicenses->isNotEmpty())
            <div class="mt-3 space-y-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                @foreach($expiringLicenses as $employee)
                    <div class="flex items-center justify-between text-sm">
                        <span class="truncate text-gray-700 dark:text-gray-300" title="{{ $employee->name }}">
                            {{ Str::limit($employee->name, 20) }}
                        </span>
                        @php
                            $daysUntilSip = $employee->sip_expiry_date ? now()->diffInDays($employee->sip_expiry_date, false) : null;
                            $daysUntilStr = $employee->str_expiry_date ? now()->diffInDays($employee->str_expiry_date, false) : null;
                            $minDays = min($daysUntilSip ?? 999, $daysUntilStr ?? 999);
                        @endphp
                        <span @class([
                            'font-medium',
                            'text-red-600 dark:text-red-400' => $minDays <= 7,
                            'text-yellow-600 dark:text-yellow-400' => $minDays > 7,
                        ])>
                            {{ $minDays <= 0 ? 'Expired' : $minDays . ' hari' }}
                        </span>
                    </div>
                @endforeach
            </div>
            @if($expiringLicenseCount > 5)
                <a href="{{ route('filament.admin.resources.employees.index') }}" class="mt-2 block text-xs text-primary-600 hover:underline dark:text-primary-400">
                    +{{ $expiringLicenseCount - 5 }} dokter lainnya
                </a>
            @endif
        @else
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada SIP/STR yang akan expired</p>
        @endif
    </div>

    {{-- Overdue Invoices Alert --}}
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-500/20">
                <x-heroicon-o-banknotes class="h-5 w-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Invoice Jatuh Tempo</p>
                <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $overdueInvoiceCount }}</p>
            </div>
        </div>
        @if($overdueInvoices->isNotEmpty())
            <div class="mt-3 space-y-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                @foreach($overdueInvoices as $invoice)
                    <div class="flex items-center justify-between text-sm">
                        <span class="truncate text-gray-700 dark:text-gray-300" title="{{ $invoice->patient?->name ?? 'Unknown' }}">
                            {{ Str::limit($invoice->patient?->name ?? 'Unknown', 18) }}
                        </span>
                        <span class="font-medium text-purple-600 dark:text-purple-400">
                            Rp {{ number_format($invoice->balance_due, 0, ',', '.') }}
                        </span>
                    </div>
                @endforeach
            </div>
            @if($overdueInvoiceCount > 5)
                <a href="{{ route('filament.admin.resources.invoices.index') }}" class="mt-2 block text-xs text-primary-600 hover:underline dark:text-primary-400">
                    +{{ $overdueInvoiceCount - 5 }} invoice lainnya
                </a>
            @endif
        @else
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada invoice yang jatuh tempo</p>
        @endif
    </div>
</x-filament-widgets::widget>
