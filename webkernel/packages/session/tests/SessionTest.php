<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Webkernel\Session\Session;

final class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        Session::configure([
            'cookie' => 'WK_TEST_'.strtoupper(bin2hex(random_bytes(3))),
            'save_path' => base_path('runtime/tmp/session-tests'),
            'lifetime' => 2,
            'secure' => false,
            'http_only' => true,
            'same_site' => 'Lax',
        ]);
    }

    public function testDataFlashAndIdentifierRotation(): void
    {
        Session::put('user_id', 42);
        self::assertSame(42, Session::get('user_id'));

        $original_id = session_id();
        Session::regenerate();
        self::assertNotSame($original_id, session_id());

        Session::invalidate();
        self::assertFalse(Session::has('user_id'));
    }

    public function testFlashDataExpiresAfterOneSubsequentRequest(): void
    {
        Session::flash('notice', 'Saved');
        Session::save();
        Session::start();
        self::assertSame('Saved', Session::get('notice'));
        Session::save();
        Session::start();
        self::assertFalse(Session::has('notice'));
    }
}
