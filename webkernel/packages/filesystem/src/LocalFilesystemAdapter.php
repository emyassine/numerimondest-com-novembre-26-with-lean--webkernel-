<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from Laravel; see LICENSE-LARAVEL.md.

namespace Webkernel\Filesystem;

use Webkernel\Filesystem\FilesystemAdapter;

/** Local disk; temporary URLs are supplied explicitly by the Webkernel routing integration. */
class LocalFilesystemAdapter extends FilesystemAdapter
{
    protected string $disk = 'local';

    public function diskName(string $disk): static
    {
        $this->disk = $disk;
        return $this;
    }
}
