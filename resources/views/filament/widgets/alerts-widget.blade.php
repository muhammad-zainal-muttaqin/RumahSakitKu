<x-filament-widgets::widget>
    <style>
        .rs-alerts-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .rs-alerts-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .rs-alerts-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .rs-alert-card {
            border-radius: 0.875rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: #ffffff;
            padding: 1rem;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .rs-alert-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }

        .rs-alert-card:nth-child(1) { border-left-color: #f97316; }
        .rs-alert-card:nth-child(2) { border-left-color: #ef4444; }
        .rs-alert-card:nth-child(3) { border-left-color: #eab308; }
        .rs-alert-card:nth-child(4) { border-left-color: #a855f7; }

        html.dark .rs-alert-card {
            border-color: rgba(71, 85, 105, 0.5);
            background: #111827;
        }

        html.dark .rs-alert-card:nth-child(1) { border-left-color: #f97316; }
        html.dark .rs-alert-card:nth-child(2) { border-left-color: #ef4444; }
        html.dark .rs-alert-card:nth-child(3) { border-left-color: #eab308; }
        html.dark .rs-alert-card:nth-child(4) { border-left-color: #a855f7; }

        html.dark .rs-alert-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.15);
        }

        .rs-alert-head {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .rs-alert-icon-wrap {
            display: flex;
            height: 2.5rem;
            width: 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            flex: 0 0 auto;
        }

        .rs-alert-icon {
            display: block;
            width: 1.25rem;
            height: 1.25rem;
            flex: 0 0 auto;
        }

        .rs-alert-title {
            margin: 0;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #6b7280;
            font-weight: 500;
        }

        html.dark .rs-alert-title {
            color: #9ca3af;
        }

        .rs-alert-count {
            margin: 0.125rem 0 0 0;
            font-size: 1.5rem;
            line-height: 1.75rem;
            font-weight: 600;
            color: #111827;
        }

        html.dark .rs-alert-count {
            color: #f9fafb;
        }

        .rs-alert-list {
            margin-top: 0.75rem;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
            padding-top: 0.75rem;
        }

        html.dark .rs-alert-list {
            border-top-color: rgba(71, 85, 105, 0.5);
        }

        .rs-alert-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .rs-alert-row:last-child {
            margin-bottom: 0;
        }

        .rs-alert-name {
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        html.dark .rs-alert-name {
            color: #d1d5db;
        }

        .rs-alert-link {
            display: inline-block;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            line-height: 1rem;
            color: #0d9488;
            text-decoration: none;
        }

        .rs-alert-link:hover {
            text-decoration: underline;
        }
    </style>

    <div class="rs-alerts-grid">
        <div class="rs-alert-card">
            <div class="rs-alert-head">
                <div class="rs-alert-icon-wrap" style="background: rgba(249, 115, 22, 0.15);">
                    <x-heroicon-o-beaker class="rs-alert-icon" style="color: #ea580c;" />
                </div>
                <div>
                    <p class="rs-alert-title">Stok Obat Menipis</p>
                    <p class="rs-alert-count">{{ $lowStockCount }}</p>
                </div>
            </div>

            @if($lowStockMedicines->isNotEmpty())
                <div class="rs-alert-list">
                    @foreach($lowStockMedicines as $medicine)
                        <div class="rs-alert-row">
                            <span class="rs-alert-name" title="{{ $medicine->name }}">{{ Str::limit($medicine->name, 25) }}</span>
                            <span style="font-weight: 600; color: {{ $medicine->stock == 0 ? '#dc2626' : '#ea580c' }};">
                                {{ number_format($medicine->stock, 0) }} {{ $medicine->unit }}
                            </span>
                        </div>
                    @endforeach
                </div>

                @if($lowStockCount > 5)
                    <a href="{{ route('filament.admin.resources.medicines.index') }}" class="rs-alert-link">
                        +{{ $lowStockCount - 5 }} item lainnya
                    </a>
                @endif
            @else
                <p style="margin-top: 0.75rem; font-size: 0.875rem; color: #6b7280;">Tidak ada obat dengan stok menipis</p>
            @endif
        </div>

        <div class="rs-alert-card">
            <div class="rs-alert-head">
                <div class="rs-alert-icon-wrap" style="background: rgba(220, 38, 38, 0.15);">
                    <x-heroicon-o-clock class="rs-alert-icon" style="color: #dc2626;" />
                </div>
                <div>
                    <p class="rs-alert-title">Pasien LOS &gt; 7 Hari</p>
                    <p class="rs-alert-count">{{ $longStayCount }}</p>
                </div>
            </div>

            @if($longStayPatients->isNotEmpty())
                <div class="rs-alert-list">
                    @foreach($longStayPatients as $patient)
                        <div class="rs-alert-row">
                            <span class="rs-alert-name" title="{{ $patient['patient_name'] }}">{{ Str::limit($patient['patient_name'], 20) }}</span>
                            <span style="font-weight: 600; color: #dc2626;">{{ $patient['los_days'] }} hari</span>
                        </div>
                    @endforeach
                </div>

                @if($longStayCount > 5)
                    <a href="{{ route('filament.admin.resources.inpatients.index') }}" class="rs-alert-link">
                        +{{ $longStayCount - 5 }} pasien lainnya
                    </a>
                @endif
            @else
                <p style="margin-top: 0.75rem; font-size: 0.875rem; color: #6b7280;">Tidak ada pasien dengan LOS &gt; 7 hari</p>
            @endif
        </div>

        <div class="rs-alert-card">
            <div class="rs-alert-head">
                <div class="rs-alert-icon-wrap" style="background: rgba(245, 158, 11, 0.2);">
                    <x-heroicon-o-document-text class="rs-alert-icon" style="color: #ca8a04;" />
                </div>
                <div>
                    <p class="rs-alert-title">SIP/STR Jatuh Tempo</p>
                    <p class="rs-alert-count">{{ $expiringLicenseCount }}</p>
                </div>
            </div>

            @if($expiringLicenses->isNotEmpty())
                <div class="rs-alert-list">
                    @foreach($expiringLicenses as $employee)
                        @php
                            $daysUntilSip = $employee->sip_expiry_date ? now()->diffInDays($employee->sip_expiry_date, false) : null;
                            $daysUntilStr = $employee->str_expiry_date ? now()->diffInDays($employee->str_expiry_date, false) : null;
                            $minDays = min($daysUntilSip ?? 999, $daysUntilStr ?? 999);
                        @endphp
                        <div class="rs-alert-row">
                            <span class="rs-alert-name" title="{{ $employee->name }}">{{ Str::limit($employee->name, 20) }}</span>
                            <span style="font-weight: 600; color: {{ $minDays <= 7 ? '#dc2626' : '#ca8a04' }};">
                                {{ $minDays <= 0 ? 'Expired' : $minDays . ' hari' }}
                            </span>
                        </div>
                    @endforeach
                </div>

                @if($expiringLicenseCount > 5)
                    <a href="{{ route('filament.admin.resources.employees.index') }}" class="rs-alert-link">
                        +{{ $expiringLicenseCount - 5 }} dokter lainnya
                    </a>
                @endif
            @else
                <p style="margin-top: 0.75rem; font-size: 0.875rem; color: #6b7280;">Tidak ada SIP/STR yang akan expired</p>
            @endif
        </div>

        <div class="rs-alert-card">
            <div class="rs-alert-head">
                <div class="rs-alert-icon-wrap" style="background: rgba(147, 51, 234, 0.15);">
                    <x-heroicon-o-banknotes class="rs-alert-icon" style="color: #7e22ce;" />
                </div>
                <div>
                    <p class="rs-alert-title">Invoice Jatuh Tempo</p>
                    <p class="rs-alert-count">{{ $overdueInvoiceCount }}</p>
                </div>
            </div>

            @if($overdueInvoices->isNotEmpty())
                <div class="rs-alert-list">
                    @foreach($overdueInvoices as $invoice)
                        <div class="rs-alert-row">
                            <span class="rs-alert-name" title="{{ $invoice->patient?->name ?? 'Unknown' }}">
                                {{ Str::limit($invoice->patient?->name ?? 'Unknown', 18) }}
                            </span>
                            <span style="font-weight: 600; color: #7e22ce;">Rp {{ number_format($invoice->balance_due, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>

                @if($overdueInvoiceCount > 5)
                    <a href="{{ route('filament.admin.resources.invoices.index') }}" class="rs-alert-link">
                        +{{ $overdueInvoiceCount - 5 }} invoice lainnya
                    </a>
                @endif
            @else
                <p style="margin-top: 0.75rem; font-size: 0.875rem; color: #6b7280;">Tidak ada invoice yang jatuh tempo</p>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
