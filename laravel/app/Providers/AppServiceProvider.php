<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Factory;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Firebase Auth con la cuenta de servicio: firma tokens de acceso y verifica los de los clientes
        $this->app->singleton(FirebaseAuth::class, fn () => (new Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->createAuth());
    }

    public function boot(): void
    {
        // 5 intentos de login por minuto para cada combinación de correo e IP
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
