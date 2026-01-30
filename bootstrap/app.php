<?php

use App\Http\Middleware\AdminAuthMiddleware;
use App\Http\Middleware\SiteAccessMiddleware;
use App\Http\Middleware\Verify2FA;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        api: __DIR__ . '/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apply site access middleware globally
        $middleware->web(append: [
            SiteAccessMiddleware::class,
        ]);

        $middleware->alias([
            'role' =>  RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'admin.auth' => AdminAuthMiddleware::class,
            'site.access' => SiteAccessMiddleware::class,
            'verify.2fa' => Verify2FA::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
