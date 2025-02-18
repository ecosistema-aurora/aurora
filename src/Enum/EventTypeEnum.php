<?php

declare(strict_types=1);

namespace App\Enum;

use App\Enum\Trait\EnumTrait;

enum EventTypeEnum: string
{
    use EnumTrait;

    case IN_PERSON = 'in person';
    case ONLINE = 'online';
    case HYBRID = 'hybrid';
}
