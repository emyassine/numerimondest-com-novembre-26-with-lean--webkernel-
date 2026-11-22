<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * Authors:
 * - Daniel S. Billing
 * - Paul
 * - Jimmie Johansson
 * - Jens Herlevsen
 */
return array_replace_recursive(require __DIR__.'/nb.php', [
    'formats' => [
        'LLL' => 'D. MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D. MMMM YYYY [kl.] HH:mm',
    ],
    'calendar' => [
        'nextWeek' => 'på dddd [kl.] LT',
        'lastWeek' => '[i] dddd[s kl.] LT',
    ],
]);
