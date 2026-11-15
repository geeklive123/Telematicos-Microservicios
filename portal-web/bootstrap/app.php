<?php

use App\Http\Middleware\RequireWebAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['web.auth' => RequireWebAuth::class]);
    })->withExceptions(fn () => null)->create();
