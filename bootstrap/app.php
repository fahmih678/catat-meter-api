<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register API middleware
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\ForceJsonResponse::class,
            \App\Http\Middleware\OptimizeResponse::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'force.json' => \App\Http\Middleware\ForceJsonResponse::class,
            'optimize.response' => \App\Http\Middleware\OptimizeResponse::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'pam.scope' => \App\Http\Middleware\PamScopeMiddleware::class,
            'spatie.role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'spatie.permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'superadmin.only' => \App\Http\Middleware\SuperAdminOnly::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
