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
 * - kostas
 * - François B
 * - Tim Fish
 * - Chiel Robben
 * - Claire Coloma
 * - Steven Heinrich
 * - JD Isaacks
 * - Raphael Amorim
 */
return array_replace_recursive(require __DIR__.'/es.php', [
    'diff_before_yesterday' => 'anteayer',
    'formats' => [
        'LT' => 'h:mm A',
        'LTS' => 'h:mm:ss A',
        'LLL' => 'D [de] MMMM [de] YYYY h:mm A',
        'LLLL' => 'dddd, D [de] MMMM [de] YYYY h:mm A',
    ],
]);
