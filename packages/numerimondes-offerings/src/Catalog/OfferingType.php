<?php declare(strict_types=1);

namespace Numerimondes\Offerings\Catalog;

enum OfferingType: string
{
    case Service = 'service';
    case Mandate = 'mandate';
    case Playbook = 'playbook';
    case Software = 'software';
}
