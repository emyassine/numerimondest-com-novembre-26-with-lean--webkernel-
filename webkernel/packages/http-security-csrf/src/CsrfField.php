<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel;

use Webkernel\View\Htmlable;

final readonly class CsrfField implements Htmlable
{
    public function __construct(
        private string $name,
        private string $token,
        private string $form_id,
    ) {
    }

    public function toHtml(): string
    {
        return '<input type="hidden" name="'.htmlspecialchars($this->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" value="'.htmlspecialchars($this->token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">'
            .'<input type="hidden" name="_csrf_id" value="'.htmlspecialchars($this->form_id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">';
    }
}
