<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\MailServer;
use Spatie\Health\Facades\Health;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use App\Checks\MqttConnectionCheck;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($mail = MailServer::first()) {
            config([
                'mail.mailers.smtp.host'       => $mail->host,
                'mail.mailers.smtp.port'       => $mail->port,
                'mail.mailers.smtp.username'   => $mail->username,
                'mail.mailers.smtp.password'   => $mail->password,
                'mail.mailers.smtp.encryption' => $mail->encryption,
                'mail.default'                 => 'smtp',
                'mail.from.address'            => $mail->username,
                'mail.from.name'               => 'LBPayLinker',
            ]);
        }
        \App\Models\Outlet::observe(\App\Observers\OutletObserver::class);
        Health::checks([
            DatabaseCheck::new(),
            RedisCheck::new(),
            HorizonCheck::new(),
            MqttConnectionCheck::new(), // Add it here!
        ]);
        //if (config('app.env') === 'production' || config('app.env') === 'staging') {
            URL::forceScheme('https');
        //}

    }
}
