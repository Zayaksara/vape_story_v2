<?php

namespace App\Enums;

enum MutationType: string
{
    case RESTOCK = 'in';
    case SALE = 'out';
    case RETURN = 'return';
    case ADJUST = 'adjustment';
}
