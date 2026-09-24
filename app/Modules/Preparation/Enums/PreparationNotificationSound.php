<?php

namespace App\Modules\Preparation\Enums;

enum PreparationNotificationSound: string
{
    case None = 'none';
    case Bell = 'bell';
    case Chime = 'chime';
}
