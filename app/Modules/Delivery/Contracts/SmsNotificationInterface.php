<?php

namespace App\Modules\Delivery\Contracts;

interface SmsNotificationInterface
{
    public function send(string $phone, string $message): bool;
}
