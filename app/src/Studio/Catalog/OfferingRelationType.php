<?php declare(strict_types=1);

namespace App\Studio\Catalog;

enum OfferingRelationType: string
{
    case Complementary = 'complementary';
    case Extension = 'extension';
    case Maintenance = 'maintenance';
    case FollowUp = 'follow_up';
    case Gateway = 'gateway';
}
