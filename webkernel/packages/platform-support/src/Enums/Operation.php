<?php

namespace Webkernel\Platform\Support\Enums;

enum Operation: string
{
    case Create = 'create';

    case Edit = 'edit';

    case View = 'view';
}
