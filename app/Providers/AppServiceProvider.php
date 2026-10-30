<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Português do Brasil + fuso de São Paulo (datas "hoje", alertas de vencimento etc.)
        $tz = env('APP_TIMEZONE', 'America/Sao_Paulo');
        config(['app.timezone' => $tz]);
        date_default_timezone_set($tz);

        app()->setLocale('pt_BR');
        Carbon::setLocale('pt_BR');
    }
}
