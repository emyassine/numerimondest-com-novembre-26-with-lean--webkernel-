<?php declare(strict_types=1);

namespace App\Studio;

use Webkernel\HttpFoundation\RedirectResponse;
use Webkernel\HttpFoundation\JsonResponse;
use Webkernel\Route\Request;
final class ShortlistController
{
    public static function toggle(Request $request): RedirectResponse|JsonResponse
    {
        $values = $request->all();
        $slug = is_string($values['service'] ?? null) ? $values['service'] : '';
        $action = ($values['action'] ?? null) === 'remove' ? 'remove' : 'add';
        $catalog = ServiceCatalog::all();
        $slugs = ServiceShortlist::all();

        if (isset($catalog[$slug])) {
            if ($action === 'remove') {
                $slugs = array_values(array_filter($slugs, static fn (string $item): bool => $item !== $slug));
            } elseif (! in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }

        ServiceShortlist::save($slugs);
        ServiceInquiryController::syncSelection($slugs);

        $acceptsJson = str_contains(strtolower($request->header('accept', '') ?? ''), 'application/json')
            || strtolower($request->header('x-requested-with', '') ?? '') === 'xmlhttprequest';
        if ($acceptsJson) {
            return new JsonResponse([
                'success' => true,
                'saved' => in_array($slug, $slugs, true),
                'count' => count($slugs),
                'services' => $slugs,
                'service' => $slug,
                'name' => $catalog[$slug]['name'] ?? '',
            ]);
        }

        $returnTo = is_string($values['return_to'] ?? null) ? $values['return_to'] : '/shortlist';
        $path = parse_url($returnTo, PHP_URL_PATH);
        $safePath = is_string($path)
            && preg_match('/\A\/(?:shortlist|service-inquiry|services(?:\/[a-z0-9-]+)?|projects(?:\/[a-z0-9-]+)?|about|contact)?\/?\z/i', $path) === 1;
        if (! $safePath) {
            $path = '/shortlist';
        }

        return new RedirectResponse($path, 303);
    }
}
