<?php declare(strict_types=1);

namespace Webkernel\Platform;

use Webkernel\Platform\Panel;
use Webkernel\ServiceProvider;

/** Panel declaration consumed by Webkernel's route compiler. */
abstract class PanelProvider extends ServiceProvider
{
    abstract public function panel(Panel $panel): Panel;
}
