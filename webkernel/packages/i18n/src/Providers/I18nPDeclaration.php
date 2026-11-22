<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\I18n\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class I18nPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'i18n',
        'catalog' => [
            'title' => [
                'fr' => 'Internationalisation',
                'en' => 'Internationalization'
            ],
            'summary' => [
                'fr' => 'Chargement des langues et traductions des applications et modules Webkernel.',
                'en' => 'Locale and translation loading for Webkernel applications and modules.'
            ],
            'category' => [
                'fr' => 'Fondations',
                'en' => 'Foundation'
            ],
            'features' => [
                0 => [
                    'title' => [
                        'fr' => 'Résolution de la langue',
                        'en' => 'Locale resolution'
                    ]
                ],
                1 => [
                    'title' => [
                        'fr' => 'Catalogues de traduction',
                        'en' => 'Translation catalogs'
                    ]
                ]
            ],
            'visibility' => 'public',
            'type' => 'foundation'
        ]
    ];
}
