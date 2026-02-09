<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;
use Filament\Schemas\Components\Tabs\Tab;
use App\Settings\HospitalSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;

class Settings extends SettingsPage
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static ?string $title = 'Pengaturan Sistem';

    protected static ?int $navigationSort = 201;

    protected static string | UnitEnum | null $navigationGroup = 'Sistem';

    protected static string $settings = HospitalSettings::class;

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Tabs::make('Pengaturan')
                    ->tabs([
                        // Tab 1: Informasi Rumah Sakit
                        Tab::make('Informasi RS')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                \Filament\Schemas\Components\Section::make('Data Rumah Sakit')
                                    ->description('Informasi umum tentang rumah sakit')
                                    ->icon('heroicon-o-hospital')
                                    ->schema([
                                        TextInput::make('hospital_name')
                                            ->label('Nama Rumah Sakit')
                                            ->required()
                                            ->maxLength(100)
                                            ->prefixIcon('heroicon-m-building-office'),

                                        TextInput::make('hospital_code')
                                            ->label('Kode RS')
                                            ->required()
                                            ->maxLength(20)
                                            ->prefixIcon('heroicon-m-hashtag')
                                            ->helperText('Kode unik rumah sakit'),

                                        Select::make('hospital_class')
                                            ->label('Kelas RS')
                                            ->required()
                                            ->options([
                                                'A' => 'Kelas A',
                                                'B' => 'Kelas B',
                                                'C' => 'Kelas C',
                                                'D' => 'Kelas D',
                                                'D PRATAMA' => 'Kelas D Pratama',
                                            ])
                                            ->native(false),

                                        Textarea::make('hospital_address')
                                            ->label('Alamat')
                                            ->required()
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        TextInput::make('hospital_phone')
                                            ->label('Telepon')
                                            ->tel()
                                            ->prefixIcon('heroicon-m-phone'),

                                        TextInput::make('hospital_email')
                                            ->label('Email')
                                            ->email()
                                            ->prefixIcon('heroicon-m-envelope'),

                                        TextInput::make('hospital_director')
                                            ->label('Nama Direktur')
                                            ->maxLength(100)
                                            ->prefixIcon('heroicon-m-user'),

                                        TextInput::make('hospital_administrator')
                                            ->label('Nama Administrator')
                                            ->maxLength(100)
                                            ->prefixIcon('heroicon-m-user'),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 2: Pengaturan Pendaftaran
                        Tab::make('Pendaftaran')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                \Filament\Schemas\Components\Section::make('Format Nomor Rekam Medis')
                                    ->icon('heroicon-o-identification')
                                    ->schema([
                                        TextInput::make('mr_number_format')
                                            ->label('Format No. RM')
                                            ->required()
                                            ->default('YYYYMM-XXXX')
                                            ->prefixIcon('heroicon-m-document-text')
                                            ->helperText('YYYY=Tahun, MM=Bulan, XXXX=Nomor urut'),

                                        TextInput::make('mr_starting_number')
                                            ->label('Nomor Mulai')
                                            ->required()
                                            ->numeric()
                                            ->default(1)
                                            ->prefixIcon('heroicon-m-hashtag'),
                                    ])
                                    ->columns(2),

                                \Filament\Schemas\Components\Section::make('Format Nomor Kunjungan')
                                    ->icon('heroicon-o-ticket')
                                    ->schema([
                                        TextInput::make('visit_number_format')
                                            ->label('Format No. Kunjungan')
                                            ->required()
                                            ->default('V-YYYYMMDD-XXXX')
                                            ->prefixIcon('heroicon-m-document-text')
                                            ->helperText('V=Visit, YYYY=Tahun, MM=Bulan, DD=Tanggal, XXXX=Nomor urut'),

                                        TextInput::make('visit_starting_number')
                                            ->label('Nomor Mulai')
                                            ->required()
                                            ->numeric()
                                            ->default(1)
                                            ->prefixIcon('heroicon-m-hashtag'),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 3: Pengaturan BPJS
                        Tab::make('BPJS')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                \Filament\Schemas\Components\Section::make('Status Bridging')
                                    ->icon('heroicon-o-signal')
                                    ->schema([
                                        Toggle::make('bpjs_enabled')
                                            ->label('Aktifkan Bridging BPJS')
                                            ->helperText('Nyalakan untuk mengaktifkan integrasi dengan BPJS')
                                            ->live()
                                            ->inline(false),
                                    ]),

                                \Filament\Schemas\Components\Section::make('Data PPK')
                                    ->icon('heroicon-o-building-library')
                                    ->schema([
                                        TextInput::make('bpjs_ppk_code')
                                            ->label('Kode PPK')
                                            ->maxLength(20)
                                            ->prefixIcon('heroicon-m-hashtag')
                                            ->disabled(fn ($get) => !$get('bpjs_enabled')),

                                        TextInput::make('bpjs_ppk_name')
                                            ->label('Nama PPK')
                                            ->maxLength(100)
                                            ->prefixIcon('heroicon-m-building-office')
                                            ->disabled(fn ($get) => !$get('bpjs_enabled')),
                                    ])
                                    ->columns(2)
                                    ->disabled(fn ($get) => !$get('bpjs_enabled')),

                                \Filament\Schemas\Components\Section::make('Endpoint URLs')
                                    ->icon('heroicon-o-globe-alt')
                                    ->schema([
                                        TextInput::make('bpjs_endpoint_dev')
                                            ->label('Endpoint Development')
                                            ->url()
                                            ->prefixIcon('heroicon-m-link')
                                            ->disabled(fn ($get) => !$get('bpjs_enabled')),

                                        TextInput::make('bpjs_endpoint_prod')
                                            ->label('Endpoint Production')
                                            ->url()
                                            ->prefixIcon('heroicon-m-link')
                                            ->disabled(fn ($get) => !$get('bpjs_enabled')),

                                        Toggle::make('bpjs_prod_mode')
                                            ->label('Mode Production')
                                            ->helperText('Nyalakan untuk menggunakan endpoint production')
                                            ->inline(false)
                                            ->disabled(fn ($get) => !$get('bpjs_enabled')),
                                    ])
                                    ->columns(2)
                                    ->disabled(fn ($get) => !$get('bpjs_enabled')),

                                \Filament\Schemas\Components\Section::make('Kredensial API')
                                    ->icon('heroicon-o-key')
                                    ->schema([
                                        TextInput::make('bpjs_cons_id')
                                            ->label('Cons ID')
                                            ->maxLength(100)
                                            ->prefixIcon('heroicon-m-identification')
                                            ->disabled(fn ($get) => !$get('bpjs_enabled')),

                                        TextInput::make('bpjs_secret_key')
                                            ->label('Secret Key')
                                            ->password()
                                            ->revealable()
                                            ->prefixIcon('heroicon-m-lock-closed')
                                            ->disabled(fn ($get) => !$get('bpjs_enabled')),

                                        TextInput::make('bpjs_user_key')
                                            ->label('User Key')
                                            ->password()
                                            ->revealable()
                                            ->prefixIcon('heroicon-m-key')
                                            ->disabled(fn ($get) => !$get('bpjs_enabled')),
                                    ])
                                    ->columns(3)
                                    ->disabled(fn ($get) => !$get('bpjs_enabled')),
                            ]),

                        // Tab 4: Pengaturan Satu Sehat
                        Tab::make('Satu Sehat')
                            ->icon('heroicon-o-heart')
                            ->schema([
                                \Filament\Schemas\Components\Section::make('Status Integrasi')
                                    ->icon('heroicon-o-signal')
                                    ->schema([
                                        Toggle::make('satu_sehat_enabled')
                                            ->label('Aktifkan Integrasi Satu Sehat')
                                            ->helperText('Nyalakan untuk mengaktifkan integrasi dengan Satu Sehat')
                                            ->live()
                                            ->inline(false),
                                    ]),

                                \Filament\Schemas\Components\Section::make('Organisasi')
                                    ->icon('heroicon-o-building-office')
                                    ->schema([
                                        TextInput::make('satu_sehat_org_id')
                                            ->label('Organization ID')
                                            ->maxLength(100)
                                            ->prefixIcon('heroicon-m-identification')
                                            ->disabled(fn ($get) => !$get('satu_sehat_enabled')),
                                    ])
                                    ->disabled(fn ($get) => !$get('satu_sehat_enabled')),

                                \Filament\Schemas\Components\Section::make('Environment')
                                    ->icon('heroicon-o-server')
                                    ->schema([
                                        Select::make('satu_sehat_environment')
                                            ->label('Environment')
                                            ->options([
                                                'sandbox' => 'Sandbox (Testing)',
                                                'production' => 'Production (Live)',
                                            ])
                                            ->default('sandbox')
                                            ->native(false)
                                            ->disabled(fn ($get) => !$get('satu_sehat_enabled')),
                                    ])
                                    ->disabled(fn ($get) => !$get('satu_sehat_enabled')),

                                \Filament\Schemas\Components\Section::make('Endpoint URLs')
                                    ->icon('heroicon-o-globe-alt')
                                    ->schema([
                                        TextInput::make('satu_sehat_auth_url')
                                            ->label('Auth URL')
                                            ->url()
                                            ->prefixIcon('heroicon-m-link')
                                            ->disabled(fn ($get) => !$get('satu_sehat_enabled')),

                                        TextInput::make('satu_sehat_base_url')
                                            ->label('Base URL')
                                            ->url()
                                            ->prefixIcon('heroicon-m-link')
                                            ->disabled(fn ($get) => !$get('satu_sehat_enabled')),
                                    ])
                                    ->columns(2)
                                    ->disabled(fn ($get) => !$get('satu_sehat_enabled')),

                                \Filament\Schemas\Components\Section::make('Kredensial OAuth2')
                                    ->icon('heroicon-o-key')
                                    ->schema([
                                        TextInput::make('satu_sehat_client_id')
                                            ->label('Client ID')
                                            ->maxLength(100)
                                            ->prefixIcon('heroicon-m-identification')
                                            ->disabled(fn ($get) => !$get('satu_sehat_enabled')),

                                        TextInput::make('satu_sehat_client_secret')
                                            ->label('Client Secret')
                                            ->password()
                                            ->revealable()
                                            ->prefixIcon('heroicon-m-lock-closed')
                                            ->disabled(fn ($get) => !$get('satu_sehat_enabled')),
                                    ])
                                    ->columns(2)
                                    ->disabled(fn ($get) => !$get('satu_sehat_enabled')),
                            ]),

                        // Tab 5: Pengaturan Notifikasi
                        Tab::make('Notifikasi')
                            ->icon('heroicon-o-bell-alert')
                            ->schema([
                                \Filament\Schemas\Components\Section::make('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->schema([
                                        Toggle::make('email_enabled')
                                            ->label('Aktifkan Email')
                                            ->live()
                                            ->inline(false),

                                        TextInput::make('email_host')
                                            ->label('SMTP Host')
                                            ->prefixIcon('heroicon-m-server')
                                            ->disabled(fn ($get) => !$get('email_enabled')),

                                        TextInput::make('email_port')
                                            ->label('SMTP Port')
                                            ->numeric()
                                            ->default(587)
                                            ->prefixIcon('heroicon-m-hashtag')
                                            ->disabled(fn ($get) => !$get('email_enabled')),

                                        TextInput::make('email_username')
                                            ->label('SMTP Username')
                                            ->prefixIcon('heroicon-m-user')
                                            ->disabled(fn ($get) => !$get('email_enabled')),

                                        TextInput::make('email_password')
                                            ->label('SMTP Password')
                                            ->password()
                                            ->revealable()
                                            ->prefixIcon('heroicon-m-lock-closed')
                                            ->disabled(fn ($get) => !$get('email_enabled')),

                                        Select::make('email_encryption')
                                            ->label('Enkripsi')
                                            ->options([
                                                'tls' => 'TLS',
                                                'ssl' => 'SSL',
                                                'none' => 'None',
                                            ])
                                            ->default('tls')
                                            ->native(false)
                                            ->disabled(fn ($get) => !$get('email_enabled')),

                                        TextInput::make('email_from_address')
                                            ->label('From Address')
                                            ->email()
                                            ->prefixIcon('heroicon-m-envelope')
                                            ->disabled(fn ($get) => !$get('email_enabled')),

                                        TextInput::make('email_from_name')
                                            ->label('From Name')
                                            ->prefixIcon('heroicon-m-user')
                                            ->disabled(fn ($get) => !$get('email_enabled')),
                                    ])
                                    ->columns(2)
                                    ->disabled(fn ($get) => !$get('email_enabled')),

                                \Filament\Schemas\Components\Section::make('SMS Gateway')
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->schema([
                                        Toggle::make('sms_enabled')
                                            ->label('Aktifkan SMS')
                                            ->live()
                                            ->inline(false),

                                        Select::make('sms_gateway_provider')
                                            ->label('Provider')
                                            ->options([
                                                'twilio' => 'Twilio',
                                                'nexmo' => 'Nexmo/Vonage',
                                                'messagebird' => 'MessageBird',
                                                'custom' => 'Custom',
                                            ])
                                            ->native(false)
                                            ->disabled(fn ($get) => !$get('sms_enabled')),

                                        TextInput::make('sms_api_key')
                                            ->label('API Key')
                                            ->prefixIcon('heroicon-m-key')
                                            ->disabled(fn ($get) => !$get('sms_enabled')),

                                        TextInput::make('sms_api_secret')
                                            ->label('API Secret')
                                            ->password()
                                            ->revealable()
                                            ->prefixIcon('heroicon-m-lock-closed')
                                            ->disabled(fn ($get) => !$get('sms_enabled')),

                                        TextInput::make('sms_sender_id')
                                            ->label('Sender ID')
                                            ->maxLength(11)
                                            ->prefixIcon('heroicon-m-chat-bubble-left')
                                            ->disabled(fn ($get) => !$get('sms_enabled')),
                                    ])
                                    ->columns(2)
                                    ->disabled(fn ($get) => !$get('sms_enabled')),

                                \Filament\Schemas\Components\Section::make('WhatsApp')
                                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                                    ->schema([
                                        Toggle::make('whatsapp_enabled')
                                            ->label('Aktifkan WhatsApp')
                                            ->live()
                                            ->inline(false),

                                        Select::make('whatsapp_provider')
                                            ->label('Provider')
                                            ->options([
                                                'twilio' => 'Twilio',
                                                'messagebird' => 'MessageBird',
                                                'wablas' => 'Wablas',
                                                'fonnte' => 'Fonnte',
                                            ])
                                            ->native(false)
                                            ->disabled(fn ($get) => !$get('whatsapp_enabled')),

                                        TextInput::make('whatsapp_api_key')
                                            ->label('API Key')
                                            ->prefixIcon('heroicon-m-key')
                                            ->disabled(fn ($get) => !$get('whatsapp_enabled')),

                                        TextInput::make('whatsapp_sender')
                                            ->label('Nomor Sender')
                                            ->tel()
                                            ->prefixIcon('heroicon-m-phone')
                                            ->disabled(fn ($get) => !$get('whatsapp_enabled')),
                                    ])
                                    ->columns(2)
                                    ->disabled(fn ($get) => !$get('whatsapp_enabled')),
                            ]),

                        // Tab 6: Pengaturan Umum
                        Tab::make('Umum')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                \Filament\Schemas\Components\Section::make('Regional')
                                    ->icon('heroicon-o-globe-alt')
                                    ->schema([
                                        Select::make('timezone')
                                            ->label('Zona Waktu')
                                            ->options([
                                                'Asia/Jakarta' => 'WIB (Asia/Jakarta)',
                                                'Asia/Makassar' => 'WITA (Asia/Makassar)',
                                                'Asia/Jayapura' => 'WIT (Asia/Jayapura)',
                                            ])
                                            ->default('Asia/Jakarta')
                                            ->native(false)
                                            ->required(),

                                        Select::make('currency')
                                            ->label('Mata Uang')
                                            ->options([
                                                'IDR' => 'Rupiah (IDR)',
                                                'USD' => 'US Dollar (USD)',
                                            ])
                                            ->default('IDR')
                                            ->native(false)
                                            ->required(),

                                        Select::make('date_format')
                                            ->label('Format Tanggal')
                                            ->options([
                                                'd/m/Y' => 'DD/MM/YYYY (31/12/2024)',
                                                'Y-m-d' => 'YYYY-MM-DD (2024-12-31)',
                                                'd-m-Y' => 'DD-MM-YYYY (31-12-2024)',
                                                'd M Y' => 'DD MMM YYYY (31 Des 2024)',
                                            ])
                                            ->default('d/m/Y')
                                            ->native(false)
                                            ->required(),

                                        Select::make('language')
                                            ->label('Bahasa')
                                            ->options([
                                                'id' => 'Indonesia',
                                                'en' => 'English',
                                            ])
                                            ->default('id')
                                            ->native(false)
                                            ->required(),
                                    ])
                                    ->columns(2),

                                \Filament\Schemas\Components\Section::make('Sistem')
                                    ->icon('heroicon-o-cpu-chip')
                                    ->schema([
                                        TextInput::make('session_lifetime')
                                            ->label('Session Lifetime (menit)')
                                            ->numeric()
                                            ->default(120)
                                            ->minValue(5)
                                            ->maxValue(1440)
                                            ->prefixIcon('heroicon-m-clock'),

                                        TextInput::make('items_per_page')
                                            ->label('Item per Halaman')
                                            ->numeric()
                                            ->default(25)
                                            ->minValue(5)
                                            ->maxValue(100)
                                            ->prefixIcon('heroicon-m-list-bullet'),

                                        Toggle::make('debug_mode')
                                            ->label('Debug Mode')
                                            ->helperText('Hanya aktifkan untuk debugging. Jangan aktifkan di production!')
                                            ->inline(false),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->contained(false)
                    ->persistTabInQueryString(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label('Reset ke Default')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Pengaturan?')
                ->modalDescription('Semua pengaturan akan dikembalikan ke nilai default. Tindakan ini tidak dapat dibatalkan.')
                ->action(function () {
                    app(static::$settings)->reset();
                    
                    Notification::make()
                        ->title('Pengaturan direset')
                        ->body('Semua pengaturan telah dikembalikan ke nilai default.')
                        ->success()
                        ->send();
                    
                    $this->redirect(static::getUrl());
                }),
        ];
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Pengaturan disimpan')
            ->body('Pengaturan sistem berhasil diperbarui.')
            ->success()
            ->send();
    }
}
