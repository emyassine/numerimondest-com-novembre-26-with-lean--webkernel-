<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Email\Result\Reason;

class UnusualElements implements Reason
{
    /**
     * @var string $element
     */
    private $element;

    public function __construct(string $element)
    {
        $this->element = $element;
    }

    public function code() : int
    {
        return 201;
    }

    public function description() : string
    {
        return 'Unusual element found, wourld render invalid in majority of cases. Element found: ' . $this->element;
    }
}
