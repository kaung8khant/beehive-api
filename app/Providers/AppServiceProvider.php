<?php

namespace App\Providers;

use App\Services\MessageService\BoomSmsService;
use App\Services\MessageService\MessagingService;
use App\Services\MessageService\SlackMessagingService;
use App\Services\OneSignalService\NotificationService;
use App\Services\OneSignalService\NotificationServiceInterface;
use App\Services\OneSignalService\OneSignalService;
use App\Services\OneSignalService\OneSignalServiceInterface;
use App\Services\PaymentService\CbPayService;
use App\Services\PaymentService\CodService;
use App\Services\PaymentService\CreditService;
use App\Services\PaymentService\KbzPayService;
use App\Services\PaymentService\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        $this->app->bind(OneSignalServiceInterface::class, OneSignalService::class);
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('string_or_array', function ($attribute, $value, $parameters, $validator) {
            return is_string($value) || is_array($value);
        });
    }
}
