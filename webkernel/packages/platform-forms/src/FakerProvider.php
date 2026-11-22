<?php

namespace Webkernel\Platform\Forms;

use Faker\Provider\Base;
use Webkernel\Platform\Forms\Components\MarkdownEditor\MarkdownFaker;
use Webkernel\Platform\Forms\Components\RichEditor\RichContentAttribute;
use Webkernel\Platform\Forms\Components\RichEditor\RichContentFaker;

class FakerProvider extends Base
{
    public function platformMarkdown(): MarkdownFaker
    {
        return MarkdownFaker::make($this->generator);
    }

    public function platformRichContent(?RichContentAttribute $attribute = null): RichContentFaker
    {
        return RichContentFaker::make($this->generator, $attribute);
    }
}
