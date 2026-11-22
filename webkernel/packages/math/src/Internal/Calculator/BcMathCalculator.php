<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Math\Internal\Calculator;

use \Webkernel\Math\Internal\Calculator;
use Override;

use function bcadd;
use function bcdiv;
use function bcmod;
use function bcmul;
use function bcpow;
use function bcpowmod;
use function bcsqrt;
use function bcsub;

/**
 * Calculator implementation built around the bcmath library.
 *
 * @internal
 */
final readonly class BcMathCalculator extends Calculator
{
    #[Override]
    public function add(string $a, string $b): string
    {
        return bcadd($a, $b, 0);
    }

    #[Override]
    public function sub(string $a, string $b): string
    {
        return bcsub($a, $b, 0);
    }

    #[Override]
    public function mul(string $a, string $b): string
    {
        return bcmul($a, $b, 0);
    }

    #[Override]
    public function divQ(string $a, string $b): string
    {
        return bcdiv($a, $b, 0);
    }

    #[Override]
    public function divR(string $a, string $b): string
    {
        return bcmod($a, $b, 0);
    }

    #[Override]
    public function divQR(string $a, string $b): array
    {
        $q = bcdiv($a, $b, 0);
        $r = bcmod($a, $b, 0);

        return [$q, $r];
    }

    #[Override]
    public function pow(string $a, int $e): string
    {
        if ($e === 0) {
            return '1';
        }

        // bcpow() allocates memory proportional to the exponent even when the base is trivial,
        // exhausting memory on 32-bit builds at large exponents.
        if ($a === '0' || $a === '1') {
            return $a;
        }

        if ($a === '-1') {
            return $e % 2 === 0 ? '1' : '-1';
        }

        return bcpow($a, (string) $e, 0);
    }

    #[Override]
    public function modPow(string $base, string $exp, string $mod): string
    {
        // normalize to Euclidean representative so modPow() stays consistent with mod()
        $base = $this->mod($base, $mod);

        return bcpowmod($base, $exp, $mod, 0);
    }

    #[Override]
    public function sqrt(string $n): string
    {
        return bcsqrt($n, 0);
    }
}
