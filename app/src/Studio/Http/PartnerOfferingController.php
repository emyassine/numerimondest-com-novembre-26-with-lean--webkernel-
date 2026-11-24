<?php declare(strict_types=1);

namespace App\Studio\Http;

use App\Studio\Partners\PartnerAccess;
use App\Studio\Persistence\OfferingStore;
use Webkernel\HttpFoundation\JsonResponse;
use Webkernel\HttpFoundation\Response;
use Webkernel\Route\Request;

final class PartnerOfferingController
{
    public static function index(Request $request): Response
    {
        $partner = PartnerAccess::authenticate($request->header('authorization'), $request->header('origin'));
        if ($partner === null) return new JsonResponse(['error' => 'unauthorized'], 401, ['WWW-Authenticate' => 'Bearer']);

        $published = (new OfferingStore())->current('published');
        if ($published === null) return new JsonResponse(['error' => 'catalogue_unavailable'], 503);
        $catalogue = $published['catalogue'];
        $allowedIds = array_fill_keys(array_filter($partner['offering_ids'] ?? [], 'is_string'), true);
        $offerings = [];
        foreach ($catalogue['offerings'] ?? [] as $offering) {
            if (!is_array($offering) || !isset($offering['id']) || !isset($allowedIds[$offering['id']])) continue;
            if (($offering['active'] ?? true) !== true || ($offering['visibility'] ?? 'catalog') === 'detail-only') continue;
            // Never expose internal margin, commission, or cost fields through the public partner API.
            unset($offering['internal'], $offering['cost'], $offering['commission'], $offering['numerimondes_revenue']);
            $offerings[] = $offering;
        }
        $payload = ['schema_version' => 1, 'catalog_revision' => $published['revision'], 'offerings' => $offerings];
        $etag = '"'.hash('sha256', json_encode([$partner['id'], $payload], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)).'"';
        $headers = ['ETag' => $etag, 'Cache-Control' => 'private, max-age=300', 'Vary' => 'Authorization, Origin'];
        if (trim((string) $request->header('if-none-match')) === $etag) return new Response('', 304, $headers);
        return new JsonResponse($payload, 200, $headers);
    }
}
