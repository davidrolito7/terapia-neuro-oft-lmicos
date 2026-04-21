<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Enums\ThemeMode;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('')
            ->defaultThemeMode(ThemeMode::Dark)
            ->favicon('/img/logo1.png')
            ->authGuard('admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                <style>
                    .fi-simple-layout {
                        background-image: url('/img/fondo.jpeg') !important;
                        background-size: cover !important;
                        background-position: center !important;
                        position: relative !important;
                        min-height: 100vh;
                    }
                    .fi-simple-layout::before {
                        content: '';
                        position: absolute;
                        inset: 0;
                        background: rgba(0, 0, 0, 0.65);
                        backdrop-filter: blur(2px);
                        -webkit-backdrop-filter: blur(2px);
                        z-index: 0;
                    }
                    .fi-simple-layout > * {
                        position: relative;
                        z-index: 1;
                    }
                    .fi-simple-layout-header {
                        display: none !important;
                    }
                </style>
                HTML)
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                <style>
                    .fi-doctor-label  { color: rgba(100,116,139,0.9); }
                    .fi-doctor-name   { color: #1e293b; }
                    .dark .fi-doctor-label { color: rgba(255,255,255,0.55); }
                    .dark .fi-doctor-name  { color: #ffffff; }
                </style>
                <div style="display:flex;flex-direction:column;align-items:center;gap:14px;margin-bottom:28px;">
                    <img
                        src="/img/perfil.jpeg"
                        alt="Dr. Jorge Gómez Morales"
                        style="width:130px;height:130px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,0.35);box-shadow:0 4px 24px rgba(0,0,0,0.5);"
                    />
                    <div style="text-align:center;line-height:1.3;">
                        <p class="fi-doctor-label" style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;margin:0 0 4px;">Bienvenido</p>
                        <h2 class="fi-doctor-name" style="font-size:19px;font-weight:700;margin:0;">Dr. Jorge Gómez Morales</h2>
                    </div>
                </div>
                HTML)
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
