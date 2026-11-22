<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\QrCode;

use InvalidArgumentException;
use Webkernel\QrCode\Common\EccLevel;
use Webkernel\QrCode\Common\MaskPattern;
use Webkernel\QrCode\Common\Version;

final readonly class Options
{
    public function __construct(
        public int $version = Version::AUTO,
        public int $ecc_level = EccLevel::L,
        public int $version_min = 1,
        public int $version_max = 40,
        public int $mask_pattern = MaskPattern::AUTO,
        public int $quiet_zone = 4,
    ) {
        if ($version_min < 1 || $version_max > 40 || $version_min > $version_max) {
            throw new InvalidArgumentException('QR version bounds must be between 1 and 40.');
        }
        if ($version !== Version::AUTO && ($version < $version_min || $version > $version_max)) {
            throw new InvalidArgumentException('The QR version must be within the configured bounds.');
        }
        if ($quiet_zone < 4 || $quiet_zone > 64) {
            throw new InvalidArgumentException('The QR quiet zone must be between 4 and 64 modules.');
        }
        new EccLevel($ecc_level);
        if ($mask_pattern !== MaskPattern::AUTO) {
            new MaskPattern($mask_pattern);
        }
    }
}
