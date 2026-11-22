<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

use PHPUnit\Framework\TestCase;
use Webkernel\Codebase\Http\ResponseHeaders;

final class ResponseHeadersTest extends TestCase
{
    public function testCspNonceIsInsertedAndHstsIsOmittedForHttp(): void
    {
        $nonce = 'c3RyaWN0LW5vbmNlLXRlc3Q=';
        $headers = ResponseHeaders::build([
            'content_security_policy' => "default-src 'self'; script-src 'self' 'nonce-{nonce}'",
            'x_content_type_options' => 'nosniff',
            'strict_transport_security' => 'max-age=31536000',
        ], false, $nonce);

        self::assertSame(
            "default-src 'self'; script-src 'self' 'nonce-{$nonce}'",
            $headers['Content-Security-Policy'],
        );
        self::assertSame('nosniff', $headers['X-Content-Type-Options']);
        self::assertArrayNotHasKey('Strict-Transport-Security', $headers);
    }

    public function testHstsIsIncludedForHttps(): void
    {
        $headers = ResponseHeaders::build([
            'strict_transport_security' => 'max-age=31536000; includeSubDomains',
        ], true, 'c3RyaWN0LW5vbmNlLXRlc3Q=');

        self::assertSame(
            'max-age=31536000; includeSubDomains',
            $headers['Strict-Transport-Security'],
        );
    }

    public function testHeaderNewlinesAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResponseHeaders::build([
            'referrer_policy' => "strict-origin\r\nX-Injected: yes",
        ], false, 'c3RyaWN0LW5vbmNlLXRlc3Q=');
    }
}
