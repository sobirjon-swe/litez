<?php

namespace App\Enums;

enum RemindVia: string
{
    case Email = 'email';
    case Sms = 'sms';
}
