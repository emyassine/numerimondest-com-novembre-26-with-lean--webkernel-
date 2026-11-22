<?php declare(strict_types=1);

namespace App\Studio;

final class PerfexAccountSession
{
    /** @return array{available: bool, client_id: int, staff_id: int} */
    public static function current(): array
    {
        if (! function_exists('get_client_user_id') || ! function_exists('get_staff_user_id')) {
            $perfexPath = config('nm-studio.path_to_perfex', base_path('account/connect'));
            $bridge = rtrim((string) $perfexPath, '/\\').DIRECTORY_SEPARATOR.'perfex_bridge.php';

            if (! is_file($bridge)) {
                return ['available' => false, 'client_id' => 0, 'staff_id' => 0];
            }

            try {
                require_once $bridge;
            } catch (\Throwable) {
                return ['available' => false, 'client_id' => 0, 'staff_id' => 0];
            }
        }

        if (! function_exists('get_client_user_id') || ! function_exists('get_staff_user_id')) {
            return ['available' => false, 'client_id' => 0, 'staff_id' => 0];
        }

        try {
            return [
                'available' => true,
                'client_id' => max(0, (int) get_client_user_id()),
                'staff_id' => max(0, (int) get_staff_user_id()),
            ];
        } catch (\Throwable) {
            return ['available' => false, 'client_id' => 0, 'staff_id' => 0];
        }
    }
}
