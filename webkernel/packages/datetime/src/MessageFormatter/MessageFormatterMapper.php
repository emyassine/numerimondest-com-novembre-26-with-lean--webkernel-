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

namespace Webkernel\DateTime\MessageFormatter;

use ReflectionMethod;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Formatter\MessageFormatterInterface;

final class MessageFormatterMapper implements MessageFormatterInterface
{
    public function format(string $message, string $locale, array $parameters = []): string
    {
        return $this->formatter->format($message, $this->transformLocale($locale), $parameters);
    }

    /**
     * Wrapped formatter.
     *
     * @var MessageFormatterInterface
     */
    protected $formatter;

    public function __construct(?MessageFormatterInterface $formatter = null)
    {
        $this->formatter = $formatter ?? new MessageFormatter();
    }

    protected function transformLocale(?string $locale): ?string
    {
        return $locale ? preg_replace('/[_@][A-Za-z][a-z]{2,}/', '', $locale) : $locale;
    }
}
