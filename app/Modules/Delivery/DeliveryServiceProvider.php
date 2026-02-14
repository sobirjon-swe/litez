<?php

namespace App\Modules\Delivery;

use App\Modules\Delivery\Contracts\GeocoderInterface;
use App\Modules\Delivery\Contracts\PaymentInterface;
use App\Modules\Delivery\Contracts\RoutingInterface;
use App\Modules\Delivery\Contracts\SmsNotificationInterface;
use App\Modules\Delivery\Services\MockGeocoderService;
use App\Modules\Delivery\Services\MockPaymentService;
use App\Modules\Delivery\Services\MockRoutingService;
use App\Modules\Delivery\Services\MockSmsService;
use Illuminate\Support\ServiceProvider;

class DeliveryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GeocoderInterface::class, MockGeocoderService::class);
        $this->app->bind(RoutingInterface::class, MockRoutingService::class);
        $this->app->bind(PaymentInterface::class, MockPaymentService::class);
        $this->app->bind(SmsNotificationInterface::class, MockSmsService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}
