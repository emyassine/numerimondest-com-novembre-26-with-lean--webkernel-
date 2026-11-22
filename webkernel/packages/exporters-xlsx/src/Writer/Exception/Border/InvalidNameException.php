<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Writer\Exception\Border;

use Webkernel\Exporters\Xlsx\Common\Entity\Style\BorderPart;
use Webkernel\Exporters\Xlsx\Writer\Exception\WriterException;

final class InvalidNameException extends WriterException
{
    public function __construct(string $name)
    {
        $msg = '%s is not a valid name identifier for a border. Valid identifiers are: %s.';

        parent::__construct(\sprintf($msg, $name, implode(',', BorderPart::allowedNames)));
    }
}
