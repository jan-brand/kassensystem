<?php

namespace App\Modules\Preparation\Enums;

enum PreparationTaskStatus: string
{
    case New = 'new';
    case InPreparation = 'in_preparation';
    case Ready = 'ready';
}
