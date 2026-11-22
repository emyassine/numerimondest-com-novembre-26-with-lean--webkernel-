<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/*
 * This is part of the league/commonmark package.
 *
 * (c) Martin Hasoň <martin.hason@gmail.com>
 * (c) Webuni s.r.o. <info@webuni.cz>
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Markdown\Extension\Table;

use \Webkernel\Markdown\Environment\EnvironmentBuilderInterface;
use \Webkernel\Markdown\Extension\ConfigurableExtensionInterface;
use \Webkernel\Markdown\Renderer\HtmlDecorator;
use \Webkernel\Markdown\Configuration\ConfigurationBuilderInterface;
use \Webkernel\Markdown\Configuration\Schema\Expect;

final class TableExtension implements ConfigurableExtensionInterface
{
    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        $attributeArraySchema = Expect::arrayOf(
            Expect::type('string|string[]|bool'), // attribute value(s)
            'string' // attribute name
        )->mergeDefaults(false);

        $builder->addSchema('table', Expect::structure([
            'wrap' => Expect::structure([
                'enabled' => Expect::bool()->default(false),
                'tag' => Expect::string()->default('div'),
                'attributes' => Expect::arrayOf(Expect::string()),
            ]),
            'alignment_attributes' => Expect::structure([
                'left' => (clone $attributeArraySchema)->default(['align' => 'left']),
                'center' => (clone $attributeArraySchema)->default(['align' => 'center']),
                'right' => (clone $attributeArraySchema)->default(['align' => 'right']),
            ]),
            'max_autocompleted_cells' => Expect::int()->min(0)->default(TableParser::DEFAULT_MAX_AUTOCOMPLETED_CELLS),
        ]));
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $tableRenderer = new TableRenderer();
        if ($environment->getConfiguration()->get('table/wrap/enabled')) {
            $tableRenderer = new HtmlDecorator($tableRenderer, $environment->getConfiguration()->get('table/wrap/tag'), $environment->getConfiguration()->get('table/wrap/attributes'));
        }

        $environment
            ->addBlockStartParser(new TableStartParser($environment->getConfiguration()->get('table/max_autocompleted_cells')))

            ->addRenderer(Table::class, $tableRenderer)
            ->addRenderer(TableSection::class, new TableSectionRenderer())
            ->addRenderer(TableRow::class, new TableRowRenderer())
            ->addRenderer(TableCell::class, new TableCellRenderer($environment->getConfiguration()->get('table/alignment_attributes')));
    }
}
