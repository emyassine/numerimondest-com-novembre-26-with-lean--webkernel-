<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Writer\XLSX;

final readonly class Properties
{
    public function __construct(
        public ?string $title = 'Untitled Spreadsheet',
        public ?string $subject = null,
        public ?string $application = 'Webkernel\Exporters\Xlsx',
        public ?string $creator = 'Webkernel\Exporters\Xlsx',
        public ?string $lastModifiedBy = 'Webkernel\Exporters\Xlsx',
        public ?string $keywords = null,
        public ?string $description = null,
        public ?string $category = null,
        public ?string $language = null,
        /** @var array<string, string> $customProperties */
        public array $customProperties = [],
    ) {}
}
