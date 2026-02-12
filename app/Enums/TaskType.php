<?php

namespace App\Enums;

enum TaskType: string
{
    case Call = 'call';
    case Meeting = 'meeting';
    case Email = 'email';
    case Task = 'task';
}
