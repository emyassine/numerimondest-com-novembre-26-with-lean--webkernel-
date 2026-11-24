<?php declare(strict_types=1);

namespace App\Studio\Http;

use App\Studio\Analytics\MarketFitAnalytics;
use App\Studio\Catalog\Taxonomy\BusinessNeed;
use App\Studio\Catalog\Taxonomy\Industry;
use Webkernel\HttpFoundation\JsonResponse;
use Webkernel\Route\Request;

final class MarketFitController
{
    public static function record(Request $request): JsonResponse
    {
        $values = $request->all();
        $industryValue = is_string($values['industry'] ?? null) ? $values['industry'] : '';
        $needValue = is_string($values['business_need'] ?? null) ? $values['business_need'] : '';
        $industry = Industry::tryFrom($industryValue);
        $need = BusinessNeed::tryFrom($needValue);

        if ($industry === null || $need === null) {
            return new JsonResponse(['success' => false], 422);
        }

        return new JsonResponse(['success' => MarketFitAnalytics::record($industry, $need)]);
    }
}
