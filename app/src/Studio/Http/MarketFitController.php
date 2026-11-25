<?php declare(strict_types=1);

namespace App\Studio\Http;

use App\Studio\Analytics\MarketFitAnalytics;
use App\Studio\Catalog\MarketFitRecommender;
use Numerimondes\Offerings\Catalog\Taxonomy\BusinessNeed;
use Numerimondes\Offerings\Catalog\Taxonomy\Industry;
use Webkernel\HttpFoundation\JsonResponse;
use Webkernel\Route\Request;

final class MarketFitController
{
    public static function record(Request $request, ?string $telemetryDirectory = null): JsonResponse
    {
        $values = $request->all();
        if (is_string($values['event'] ?? null)) {
            return new JsonResponse(['success' => MarketFitAnalytics::recordEvent($values['event'], $telemetryDirectory)]);
        }
        $industryValue = is_string($values['industry'] ?? null) ? $values['industry'] : '';
        $needValue = is_string($values['business_need'] ?? null) ? $values['business_need'] : '';
        $industry = Industry::tryFrom($industryValue);
        $need = BusinessNeed::tryFrom($needValue);
        $journeyOptions = [
            'team_size' => ['solo', '2-10', '11-50', '51+'],
            'annual_revenue' => ['under-1m', '1m-10m', '10m-50m', '50m+'],
            'profit_margin' => ['negative', '0-10', '11-25', '26+'],
            'ownership' => ['solo', 'partners'],
            'discovery' => ['recent', 'months', 'year+'],
        ];
        $journey = [];
        foreach ($journeyOptions as $field => $allowed) {
            $answer = $values[$field] ?? null;
            if ($answer === null || $answer === '') continue;
            if (!is_string($answer) || !in_array($answer, $allowed, true)) return new JsonResponse(['success' => false], 422);
            $journey[$field] = $answer;
        }

        if (($industryValue !== '' && $industry === null) || ($needValue !== '' && $need === null) || ($industry === null && $need === null && $journey === [])) {
            return new JsonResponse(['success' => false], 422);
        }

        return new JsonResponse([
            'success' => MarketFitAnalytics::record($industry, $need, $journey, $telemetryDirectory),
            'recommendations' => MarketFitRecommender::recommend($industryValue, $needValue, $journey),
        ]);
    }
}
