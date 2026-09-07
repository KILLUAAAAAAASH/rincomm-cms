<?php
use Illuminate\Http\Request;
use App\Http\Middleware\ActiveAccountMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => RoleMiddleware::class,
        'active' => ActiveAccountMiddleware::class,
    ]);

    $middleware->redirectUsersTo(
        fn (Request $request) => match ($request->user()?->role) {
            'admin', 'staff' => route('dashboard'),
            'technician' => route('technician.dashboard'),
            'customer' => route('customer.dashboard'),
            default => route('home'),
        }
    );
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();