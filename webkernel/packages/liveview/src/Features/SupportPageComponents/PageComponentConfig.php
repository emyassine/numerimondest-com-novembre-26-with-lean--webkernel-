<?php

namespace Webkernel\Liveview\Features\SupportPageComponents;

use Webkernel\Config\Config;

class PageComponentConfig
{
    public $response;

    function __construct(
        public $type = 'component',
        public $view = '',
        public $slotOrSection = 'slot',
        public $params = [],
    ) {
        $this->view = $view ?: Config::get('liveview.component_layout', Config::get('liveview.layout'));
    }

    function mergeParams($toMerge)
    {
        $this->params = array_merge($toMerge, $this->params);
    }

}
