<?php

namespace App\Enums;

enum UserRole: string
{
    case Manager = 'manager';
    case Admin = 'admin';
}
