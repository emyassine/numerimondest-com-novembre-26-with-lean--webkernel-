<?php declare(strict_types=1);

namespace App\Studio\Analytics;

use App\Studio\Catalog\Taxonomy\BusinessNeed;
use App\Studio\Catalog\Taxonomy\Industry;

/** Stores daily aggregate selector counts only; no visitor identifiers are recorded. */
final class MarketFitAnalytics
{
    public static function record(Industry $industry, BusinessNeed $need): bool
    {
        $telemetryDirectory = base_path((string) config('paths.telemetry', 'runtime/telemetry'));
        if (! is_dir($telemetryDirectory) && ! @mkdir($telemetryDirectory, 0770, true) && ! is_dir($telemetryDirectory)) {
            return false;
        }

        $file = $telemetryDirectory.DIRECTORY_SEPARATOR.'service-market-fit.json';
        $handle = @fopen($file, 'c+');
        if ($handle === false || ! flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            return false;
        }

        try {
            rewind($handle);
            $contents = stream_get_contents($handle);
            $data = is_string($contents) && $contents !== '' ? json_decode($contents, true) : [];
            $counts = is_array($data['counts'] ?? null) ? $data['counts'] : [];
            $day = gmdate('Y-m-d');
            $key = $industry->value.'|'.$need->value;
            $counts[$day][$key] = (int) ($counts[$day][$key] ?? 0) + 1;

            $cutoff = gmdate('Y-m-d', strtotime('-400 days'));
            foreach (array_keys($counts) as $countDay) {
                if (! is_string($countDay) || $countDay < $cutoff || ! is_array($counts[$countDay])) {
                    unset($counts[$countDay]);
                }
            }

            $encoded = json_encode(
                ['schema_version' => 1, 'counts' => $counts],
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            );
            rewind($handle);
            if (! ftruncate($handle, 0) || fwrite($handle, $encoded) === false || ! fflush($handle)) {
                return false;
            }
            return true;
        } catch (\JsonException) {
            return false;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
