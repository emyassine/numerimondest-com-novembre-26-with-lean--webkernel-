<?php

namespace Webkernel\Platform\Tables\Enums;

enum RecordActionsPosition
{
    case AfterCells;

    case AfterColumns;

    case AfterContent;

    case BeforeCells;

    case BeforeColumns;
}
