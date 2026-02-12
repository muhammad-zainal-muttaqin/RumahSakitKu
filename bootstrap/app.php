<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\AuditMiddleware::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\AuditMiddleware::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            $isFilamentLogoutRequest = $request->routeIs('filament.*.auth.logout')
                || $request->is('admin/logout')
                || $request->is('filament-admin/logout');

            if (! $isFilamentLogoutRequest) {
                return null;
            }

            $defaultLoginPath = $request->is('filament-admin/*') ? '/filament-admin/login' : '/admin/login';
            $loginPath = app('router')->has('filament.admin.auth.login')
                ? route('filament.admin.auth.login', [], false)
                : $defaultLoginPath;

            return redirect($loginPath)->with(
                'warning',
                'Sesi Anda telah berakhir. Silakan login kembali.'
            );
        });
    })->create();
