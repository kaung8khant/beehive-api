<?php

namespace App\Providers;

use App\Services\MessageService\BoomSmsService;
use App\Services\MessageService\MessagingService;
use App\Services\MessageService\SlackMessagingService;
use App\Services\PaymentService\CbPayService;
use App\Services\PaymentService\CodService;
use App\Services\PaymentService\CreditService;
use App\Services\PaymentService\KbzPayService;
use App\Services\PaymentService\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('production')) {
            $this->app->singleton(MessagingService::class, function ($app) {
                return new BoomSmsService();
            });
        } else {
            $this->app->singleton(MessagingService::class, function ($app) {
                return new SlackMessagingService();
            });
        }

        $this->app->singleton(PaymentService::class, function ($app) {
            $request = $app->make(Request::class);

            if ($request->input('payment_mode') === 'KPay') {
                return new KbzPayService();
            } elseif ($request->input('payment_mode') === 'CBPay') {
                return new CbPayService();
            } elseif ($request->input('payment_mode') === 'Credit') {
                return new CreditService();
            } else {
                return new CodService();
            }
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
