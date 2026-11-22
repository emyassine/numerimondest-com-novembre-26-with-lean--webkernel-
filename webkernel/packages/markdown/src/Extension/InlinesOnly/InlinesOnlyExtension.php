<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Markdown\Extension\InlinesOnly;

use \Webkernel\Markdown as Core;
use \Webkernel\Markdown\Environment\EnvironmentBuilderInterface;
use \Webkernel\Markdown\Extension\CommonMark;
use \Webkernel\Markdown\Extension\CommonMark\Delimiter\Processor\EmphasisDelimiterProcessor;
use \Webkernel\Markdown\Extension\ConfigurableExtensionInterface;
use \Webkernel\Markdown\Configuration\ConfigurationBuilderInterface;
use \Webkernel\Markdown\Configuration\Schema\Expect;

final class InlinesOnlyExtension implements ConfigurableExtensionInterface
{
    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        $builder->addSchema('commonmark', Expect::structure([
            'use_asterisk' => Expect::bool(true),
            'use_underscore' => Expect::bool(true),
            'enable_strong' => Expect::bool(true),
            'enable_em' => Expect::bool(true),
            'unordered_list_markers' => Expect::listOf('string')->min(1)->default(['*', '+', '-'])->mergeDefaults(false),
        ]));
    }

    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma,Squiz.WhiteSpace.SemicolonSpacing.Incorrect
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $childRenderer = new ChildRenderer();

        $environment
            ->addInlineParser(new Core\Parser\Inline\NewlineParser(),           200)
            ->addInlineParser(new CommonMark\Parser\Inline\BacktickParser(),    150)
            ->addInlineParser(new CommonMark\Parser\Inline\EscapableParser(),    80)
            ->addInlineParser(new CommonMark\Parser\Inline\EntityParser(),       70)
            ->addInlineParser(new CommonMark\Parser\Inline\AutolinkParser(),     50)
            ->addInlineParser(new CommonMark\Parser\Inline\HtmlInlineParser(),   40)
            ->addInlineParser(new CommonMark\Parser\Inline\CloseBracketParser(), 30)
            ->addInlineParser(new CommonMark\Parser\Inline\OpenBracketParser(),  20)
            ->addInlineParser(new CommonMark\Parser\Inline\BangParser(),         10)

            ->addRenderer(Core\Node\Block\Document::class,  $childRenderer, 0)
            ->addRenderer(Core\Node\Block\Paragraph::class, $childRenderer, 0)

            ->addRenderer(CommonMark\Node\Inline\Code::class,       new CommonMark\Renderer\Inline\CodeRenderer(),       0)
            ->addRenderer(CommonMark\Node\Inline\Emphasis::class,   new CommonMark\Renderer\Inline\EmphasisRenderer(),   0)
            ->addRenderer(CommonMark\Node\Inline\HtmlInline::class, new CommonMark\Renderer\Inline\HtmlInlineRenderer(), 0)
            ->addRenderer(CommonMark\Node\Inline\Image::class,      new CommonMark\Renderer\Inline\ImageRenderer(),      0)
            ->addRenderer(CommonMark\Node\Inline\Link::class,       new CommonMark\Renderer\Inline\LinkRenderer(),       0)
            ->addRenderer(Core\Node\Inline\Newline::class,          new Core\Renderer\Inline\NewlineRenderer(),          0)
            ->addRenderer(CommonMark\Node\Inline\Strong::class,     new CommonMark\Renderer\Inline\StrongRenderer(),     0)
            ->addRenderer(Core\Node\Inline\Text::class,             new Core\Renderer\Inline\TextRenderer(),             0)
        ;

        if ($environment->getConfiguration()->get('commonmark/use_asterisk')) {
            $environment->addDelimiterProcessor(new EmphasisDelimiterProcessor('*'));
        }

        if ($environment->getConfiguration()->get('commonmark/use_underscore')) {
            $environment->addDelimiterProcessor(new EmphasisDelimiterProcessor('_'));
        }
    }
}
