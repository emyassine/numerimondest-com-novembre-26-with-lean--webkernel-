<?php declare(strict_types=1);

namespace App\Studio\Analytics;

use App\Studio\Catalog\Taxonomy\BusinessNeed;
use App\Studio\Catalog\Taxonomy\Industry;

/** Stores daily aggregate selector counts only; no visitor identifiers are recorded. */
final class MarketFitAnalytics
{
    private const EVENTS = ['shortlist_add', 'shortlist_remove', 'detail_open', 'extension_add', 'currency_change', 'welcome_complete', 'enquiry_submit'];

    /** Records an allow-listed event as a daily count, without visitor or offering identifiers. */
    public static function recordEvent(string $event, ?string $directory = null): bool
    {
        if (!in_array($event, self::EVENTS, true)) return false;
        $directory ??= base_path((string) config('paths.telemetry', 'runtime/telemetry'));
        if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) return false;
        $file = $directory.DIRECTORY_SEPARATOR.'service-events.json';
        $handle = @fopen($file, 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) { if (is_resource($handle)) fclose($handle); return false; }
        try {
            rewind($handle);
            $contents = stream_get_contents($handle);
            $data = is_string($contents) && $contents !== '' ? json_decode($contents, true) : [];
            $counts = is_array($data['counts'] ?? null) ? $data['counts'] : [];
            $day = gmdate('Y-m-d');
            $counts[$day][$event] = (int)($counts[$day][$event] ?? 0) + 1;
            $cutoff = gmdate('Y-m-d', strtotime('-400 days'));
            foreach (array_keys($counts) as $countDay) if (!is_string($countDay) || $countDay < $cutoff || !is_array($counts[$countDay])) unset($counts[$countDay]);
            $encoded = json_encode(['schema_version'=>1,'counts'=>$counts], JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            rewind($handle);
            return ftruncate($handle, 0) && fwrite($handle, $encoded) !== false && fflush($handle);
        } catch (\JsonException) { return false; }
        finally { flock($handle, LOCK_UN); fclose($handle); }
    }

    /** @param array<string,string> $journey */
    public static function record(?Industry $industry, ?BusinessNeed $need, array $journey = [], ?string $directory = null): bool
    {
        if ($industry === null && $need === null && $journey === []) return false;
        $telemetryDirectory = $directory ?? base_path((string) config('paths.telemetry', 'runtime/telemetry'));
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
            if ($industry !== null) $counts[$day]['industries'][$industry->value] = (int) ($counts[$day]['industries'][$industry->value] ?? 0) + 1;
            if ($need !== null) $counts[$day]['business_needs'][$need->value] = (int) ($counts[$day]['business_needs'][$need->value] ?? 0) + 1;
            if ($industry !== null && $need !== null) {
                $key = $industry->value.'|'.$need->value;
                $counts[$day]['combinations'][$key] = (int) ($counts[$day]['combinations'][$key] ?? 0) + 1;
            }
            foreach ($journey as $question => $answer) {
                $counts[$day]['journey'][$question][$answer] = (int) ($counts[$day]['journey'][$question][$answer] ?? 0) + 1;
            }

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
