<?php

namespace Webkernel\Platform\Tables;

use Webkernel\Platform\Actions\Concerns\InteractsWithActions;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Schemas\Concerns\InteractsWithSchemas;
use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\Liveview\Component;

abstract class TableComponent extends Component implements Contracts\HasTable, HasActions, HasSchemas
{
    use Concerns\InteractsWithTable;
    use InteractsWithActions;
    use InteractsWithSchemas;
}
