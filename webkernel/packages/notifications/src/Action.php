<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Notifications;

class Action
{
    /**
     * The action text.
     *
     * @var string
     */
    public $text;

    /**
     * The action URL.
     *
     * @var string
     */
    public $url;

    /**
     * Create a new action instance.
     *
     * @param  string  $text
     * @param  string  $url
     */
    public function __construct($text, $url)
    {
        $this->url = $url;
        $this->text = $text;
    }
}
