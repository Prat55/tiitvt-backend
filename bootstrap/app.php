<?php

use Illuminate\Foundation\Application;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};
use App\Http\Middleware\{
    AdminAuthMiddleware,
    ForceJsonResponse,
    SiteAccessMiddleware,
    Verify2FA
};
use Spatie\Permission\Middleware\{
    PermissionMiddleware,
    RoleMiddleware,
    RoleOrPermissionMiddleware
};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        api: __DIR__ . '/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('api', [
            'throttle:api',
            SubstituteBindings::class,
            ForceJsonResponse::class,
        ]);

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
