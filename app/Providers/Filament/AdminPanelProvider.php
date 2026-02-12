<?php

namespace App\Providers\Filament;

use BackedEnum;
use UnitEnum;
use App\Filament\Pages\Auth\Login;
use Filament\Widgets\AccountWidget;
use Filament\AvatarProviders\UiAvatarsProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panelPath = app()->environment('testing') ? 'filament-admin' : 'admin';

        return $panel
            ->default()
            ->id('admin')
            ->path($panelPath)
            ->login(Login::class)
            ->colors([
                'primary' => Color::Teal,
                'secondary' => Color::Slate,
                'success' => Color::Emerald,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'info' => Color::Sky,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render("@include('filament.custom-styles')"),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => Blade::render("@include('filament.login-footer')"),
            )
            ->font('Inter')
            ->brandName('RumahSakitKu')
            ->brandLogo(fn () => view('filament.logo'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.ico'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->navigationGroups([
                'Dashboard',
                'Pendaftaran',
                'Rawat Jalan',
                'Rawat Inap',
                'IGD',
                'Farmasi',
                'Laboratorium',
                'Keuangan',
                'Laporan & Analitik',
                'Manajemen',
                'Master Data',
                'Sistem',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->collapsedSidebarWidth('4rem')
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->spa()
            ->plugins([
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->passwordReset()
            ->emailVerification()
            ->defaultAvatarProvider(UiAvatarsProvider::class)
            ->darkMode(true);
    }
}
