<?php declare(strict_types=1);

namespace App\Studio\Admin;

use App\Studio\Persistence\OfferingStore;
use App\Studio\Catalog\OfferingType;
use App\Studio\Catalog\OfferingRelationType;
use App\Studio\Catalog\Taxonomy\Industry;
use App\Studio\Catalog\Taxonomy\BusinessNeed;
use Webkernel\Route\Request;
use Webkernel\HttpFoundation\Response;
use Webkernel\HttpFoundation\Exceptions\HttpResponseException;
use Webkernel\Csrf;
use Webkernel\Session\Session;
use Webkernel\HttpErrorPages\ErrorPages;
use Webkernel\View\View;

/** Local administration for the unified catalogue and its SQLite database. */
final class AdminController
{
    public static function index(Request $request, ?OfferingStore $store = null): Response
    {
        self::prepareSession();
        if (!self::authorized()) return self::redirectToLogin();
        $store ??= new OfferingStore();
        $draft = $store->current('draft');
        $published = $store->current('published');
        $source = $draft ?? $published ?? ['revision' => null, 'catalogue' => ['offerings' => []]];
        $json = json_encode($source['catalogue'], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $value = $json;
        $revision = htmlspecialchars((string) ($source['revision'] ?? 'none'), ENT_QUOTES, 'UTF-8');
        $history = $store->history();
        $csrf = self::csrfToken();
        $historyHtml = '';
        foreach ($history as $entry) {
            $historyHtml .= '<li>Revision '.(int) $entry['revision'].' · '.htmlspecialchars($entry['created_at'], ENT_QUOTES, 'UTF-8').'</li>';
        }
        $html = View::make('studio::partials.admin.offerings.editor', [
            'revision' => $revision,
            'catalogueJson' => $value,
            'historyHtml' => $historyHtml,
            'csrfToken' => htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'),
        ])->render();
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store']);
    }

    public static function saveDraft(Request $request, ?OfferingStore $store = null): Response
    {
        self::prepareSession();
        if (!self::authorized()) return new Response('Authentication required.', 401);
        if (!self::validCsrf($request)) return new Response('Invalid security token.', 419);
        $data = json_decode($request->content(), true);
        if (!is_array($data) || !isset($data['offerings']) || !is_array($data['offerings'])) return new Response('Invalid catalogue payload', 422);
        try { self::validate($data['offerings']); }
        catch (\Throwable $error) { return new Response('Invalid catalogue: '.$error->getMessage(), 422); }
        try { ($store ?? new OfferingStore())->saveDraft($data, 'admin'); }
        catch (\Throwable $error) { return new Response('Invalid catalogue: '.$error->getMessage(), 422); }
        return new Response('Draft saved', 204);
    }

    public static function preview(Request $request): Response
    {
        self::prepareSession();
        if (!self::authorized()) return new Response('Authentication required.', 401);
        if (!self::validCsrf($request)) return new Response('Invalid security token.', 419);
        $data = json_decode($request->content(), true);
        if (!is_array($data) || !is_array($data['offerings'] ?? null)) return new Response('Invalid catalogue payload.', 422);
        try { self::validate($data['offerings']); }
        catch (\Throwable $error) { return new Response('Invalid catalogue: '.$error->getMessage(), 422); }
        $preview = [];
        foreach ($data['offerings'] as $offering) {
            $preview[] = [
                'id' => $offering['id'],
                'name' => $offering['name']['en'] ?? reset($offering['name']) ?: $offering['id'],
                'type' => $offering['type'],
                'active' => $offering['active'],
                'visibility' => $offering['visibility'] ?? 'catalog',
                'prices' => array_map(static fn (array $price): array => array_intersect_key($price, array_flip(['id', 'amount_mad', 'minimum_mad', 'maximum_mad', 'percentage', 'billing', 'basis'])), array_filter($offering['prices'], 'is_array')),
                'relations' => count($offering['relations']),
            ];
        }
        return new Response(json_encode(['valid' => true, 'offering_count' => count($preview), 'offerings' => $preview], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json', 'Cache-Control' => 'no-store']);
    }

    public static function publish(Request $request, ?OfferingStore $store = null): Response
    {
        self::prepareSession();
        if (!self::authorized()) return new Response('Authentication required.', 401);
        if (!self::validCsrf($request)) return new Response('Invalid security token.', 419);
        try { $revision = ($store ?? new OfferingStore())->publish('admin'); }
        catch (\Throwable $error) { return new Response($error->getMessage(), 409); }
        return new Response(json_encode(['revision' => $revision], JSON_THROW_ON_ERROR), 200, ['Content-Type' => 'application/json']);
    }

    public static function loginForm(Request $request): Response
    {
        if (!self::secureRequest()) self::forbidden();
        self::prepareSession();
        $credentials = self::credentials();
        if ($credentials === null) {
            if (!self::isLoopbackRequest()) self::forbidden();
            return self::renderLogin(['setup' => true, 'csrfToken' => htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8')]);
        }
        if (self::authorized()) return self::redirect('/admin');
        return self::renderLogin([
            'csrfToken' => htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8'),
            'error' => $request->query('error') === '1' ? 'Sign-in failed. Check your username and password.' : null,
        ]);
    }

    public static function createAdmin(Request $request): Response
    {
        if (!self::isLoopbackRequest()) self::forbidden();
        self::prepareSession();
        if (self::credentials() !== null) return self::redirect('/admin/login');
        $values = $request->all();
        $username = trim((string) ($values['username'] ?? ''));
        $password = (string) ($values['password'] ?? '');
        $confirmation = (string) ($values['password_confirmation'] ?? '');
        $error = null;
        if (!self::validCsrf($request)) {
            $error = 'This form expired. Reload the page and try again.';
        } elseif (!preg_match('/\A[a-zA-Z0-9._@-]{3,80}\z/', $username)) {
            $error = 'Enter a username between 3 and 80 characters.';
        } elseif (strlen($password) < 12) {
            $error = 'Use a password with at least 12 characters.';
        } elseif (!hash_equals($password, $confirmation)) {
            $error = 'The passwords do not match. Please try again.';
        }
        if ($error !== null) {
            return self::renderLogin([
                'setup' => true,
                'error' => $error,
                'username' => $username,
                'csrfToken' => htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8'),
            ]);
        }

        $path = base_path('config/offerings-admin.php');
        $config = "<?php declare(strict_types=1);\n\nreturn ".var_export([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ], true).";\n";
        $temporary = $path.'.'.bin2hex(random_bytes(8)).'.tmp';
        if (file_put_contents($temporary, $config, LOCK_EX) === false || !chmod($temporary, 0600) || !link($temporary, $path)) {
            @unlink($temporary);
            if (self::credentials() !== null) return self::redirect('/admin/login');
            self::unavailable('Admin setup failed', 'The admin account could not be saved. Check that the application config directory is writable.');
        }
        unlink($temporary);

        Session::regenerate(true);
        Session::put('studio.offerings.admin_authenticated_at', time());
        Csrf::rotate();
        return self::redirect('/admin');
    }

    public static function login(Request $request): Response
    {
        if (!self::secureRequest()) self::forbidden();
        self::prepareSession();
        $credentials = self::credentials();
        $values = $request->all();
        if ($credentials === null) return self::redirect('/admin/login');
        if (!self::validCsrf($request) || !is_string($values['username'] ?? null) || !is_string($values['password'] ?? null)
            || !hash_equals($credentials['username'], $values['username']) || !password_verify($values['password'], $credentials['password_hash'])) {
            return self::redirect('/admin/login?error=1');
        }
        Session::regenerate(true);
        Session::put('studio.offerings.admin_authenticated_at', time());
        Csrf::rotate();
        return self::redirect('/admin');
    }

    public static function logout(Request $request): Response
    {
        self::prepareSession();
        if (!self::authorized()) return new Response('Authentication required.', 401);
        if (!self::validCsrf($request)) return new Response('Invalid security token.', 419);
        Session::forget('studio.offerings.admin_authenticated_at');
        Session::regenerate(true);
        return self::redirect('/admin/login');
    }

    public static function databaseManager(Request $request): Response
    {
        self::prepareSession();
        if (!self::authorized()) return self::redirectToLogin();
        $databaseDirectory = dirname(OfferingStore::defaultPath());
        if (!is_dir($databaseDirectory) || !is_readable($databaseDirectory) || !is_writable($databaseDirectory)) {
            self::unavailable('Admin database unavailable', 'The catalogue database directory is not writable. Check its ownership and permissions, then reload this page.');
        }
        if (!defined('DBLITEADMIN_DATABASE_DIR')) define('DBLITEADMIN_DATABASE_DIR', $databaseDirectory);
        $_SESSION['auth'] = true;
        $initialBufferLevel = ob_get_level();
        ob_start();
        try {
            require_once __DIR__.'/DBLiteAdmin/DBLiteAdmin.php';
            $html = (string) ob_get_clean();
        } catch (\Throwable $error) {
            while (ob_get_level() > $initialBufferLevel) ob_end_clean();
            throw $error;
        }
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store']);
    }

    private static function authorized(): bool
    {
        self::prepareSession();
        $authenticatedAt = Session::get('studio.offerings.admin_authenticated_at');
        return self::secureRequest() && is_int($authenticatedAt) && $authenticatedAt > time() - 28800;
    }

    private static function secureRequest(): bool
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off') return true;
        return self::isLoopbackRequest();
    }

    private static function isLoopbackRequest(): bool
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $parsedHost = parse_url('http://'.$host, PHP_URL_HOST);
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        return is_string($parsedHost)
            && in_array(trim($parsedHost, '[]'), ['localhost', '127.0.0.1', '::1'], true)
            && in_array($remote, ['127.0.0.1', '::1'], true);
    }

    private static function forbidden(): never
    {
        throw new HttpResponseException(new Response(ErrorPages::render(403), 403, [
            'Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store',
        ]));
    }

    private static function unavailable(string $title, string $description): never
    {
        $html = View::make((string) config('http-error-pages.view', 'studio::errors.show'), [
            'status' => 503,
            'locale' => 'en',
            'title' => $title,
            'description' => $description,
        ])->render();
        throw new HttpResponseException(new Response($html, 503, [
            'Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store',
        ]));
    }

    private static function redirect(string $location): Response
    {
        return new Response('', 303, ['Location' => $location, 'Cache-Control' => 'no-store']);
    }

    /** @param array<string,mixed> $data */
    private static function renderLogin(array $data): Response
    {
        $html = View::make('studio::partials.admin.login', $data)->render();
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store']);
    }

    private static function prepareSession(): void
    {
        AdminSession::configure();
    }

    /** @return array{username:string,password_hash:string}|null */
    private static function credentials(): ?array
    {
        $path = base_path('config/offerings-admin.php');
        if (!is_file($path)) return null;
        $config = require $path;
        if (!is_array($config) || !is_string($config['username'] ?? null) || !is_string($config['password_hash'] ?? null)
            || !str_starts_with($config['password_hash'], '$') || strlen($config['password_hash']) < 50) return null;
        return ['username' => $config['username'], 'password_hash' => $config['password_hash']];
    }

    private static function csrfToken(): string
    {
        return Csrf::token();
    }

    private static function validCsrf(Request $request): bool
    {
        $received = $request->header('x-csrf-token') ?? ($request->all()['_token'] ?? null);
        return is_string($received) && Csrf::verify($received);
    }

    private static function redirectToLogin(): Response
    {
        return self::redirect('/admin/login');
    }

    /** @param list<array<string,mixed>> $offerings */
    private static function validate(array $offerings): void
    {
        $ids = [];
        foreach ($offerings as $row) {
            if (!is_array($row)) throw new \InvalidArgumentException('Each offering must be an object.');
            foreach (['id','type','name','description','industries','business_needs','prices','relations','active'] as $field) {
                if (!array_key_exists($field, $row)) throw new \InvalidArgumentException('Missing offering field: '.$field);
            }
            if (!is_string($row['id']) || isset($ids[$row['id']])) throw new \InvalidArgumentException('Offering IDs must be unique strings.');
            $ids[$row['id']] = true;
            if (OfferingType::tryFrom((string) $row['type']) === null) throw new \InvalidArgumentException('Unknown offering type.');
            foreach (['name','description'] as $field) {
                if (!is_array($row[$field]) || $row[$field] === []) throw new \InvalidArgumentException($field.' must contain locale translations.');
                foreach ($row[$field] as $locale => $text) if (!is_string($locale) || !is_string($text)) throw new \InvalidArgumentException('Translations must map locales to strings.');
            }
            if (!is_array($row['industries']) || !is_array($row['business_needs']) || !is_array($row['prices']) || !is_array($row['relations']) || !is_bool($row['active'])) throw new \InvalidArgumentException('Classifications, prices, relations, or active flag have invalid types.');
            foreach ($row['industries'] as $industry) if (!is_string($industry) || Industry::tryFrom($industry) === null) throw new \InvalidArgumentException('Unknown industry: '.(string) $industry);
            foreach ($row['business_needs'] as $need) if (!is_string($need) || BusinessNeed::tryFrom($need) === null) throw new \InvalidArgumentException('Unknown business need: '.(string) $need);
            foreach ($row['prices'] as $price) {
                if (!is_array($price) || !is_string($price['id'] ?? null) || $price['id'] === '') throw new \InvalidArgumentException('Each price component needs an identifier.');
                if (!in_array($price['billing'] ?? null, ['one_time', 'monthly', 'quarterly', 'annual', 'percentage'], true)) throw new \InvalidArgumentException('Unknown price billing interval.');
                foreach (['amount_mad', 'minimum_mad', 'maximum_mad'] as $amount) {
                    if (isset($price[$amount]) && (!is_numeric($price[$amount]) || !is_finite((float) $price[$amount]) || (float) $price[$amount] < 0)) throw new \InvalidArgumentException('MAD price values must be finite and non-negative.');
                }
                if (isset($price['percentage']) && (!is_numeric($price['percentage']) || (float) $price['percentage'] <= 0 || (float) $price['percentage'] > 100 || !is_array($price['basis'] ?? null))) throw new \InvalidArgumentException('Percentage components require a valid percentage and calculation basis.');
                if (isset($price['minimum_mad'], $price['maximum_mad']) && $price['minimum_mad'] > $price['maximum_mad']) throw new \InvalidArgumentException('Price minimum exceeds maximum.');
                if (isset($price['unit']) && (!is_string($price['unit']) || !preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $price['unit'])
                    || !is_numeric($price['amount_mad'] ?? null) || isset($price['minimum_mad']) || isset($price['maximum_mad'])
                    || is_numeric($price['percentage'] ?? null))) throw new \InvalidArgumentException('Unit prices require a fixed amount and a valid unit identifier.');
            }
            foreach ($row['relations'] as $relation) {
                if (!is_array($relation) || !is_string($relation['offering_id'] ?? null) || !is_string($relation['type'] ?? null)
                    || OfferingRelationType::tryFrom($relation['type']) === null
                    || !in_array($relation['visibility'] ?? 'detail_only', ['catalog', 'detail_only', 'hidden'], true)
                    || !is_bool($relation['optional'] ?? true)) throw new \InvalidArgumentException('Invalid offering relationship.');
                $targets[$row['id']][] = $relation['offering_id'];
            }
        }
        foreach ($targets ?? [] as $parent => $children) foreach ($children as $child) {
            if (!isset($ids[$child]) || $parent === $child) throw new \InvalidArgumentException('Offering relationship points to an unknown or identical offering.');
        }
    }
}
