<?php declare(strict_types=1);

namespace App\Studio;

final class PerfexLeadGateway
{
    /** @param array<string, string> $lead */
    public static function create(array $lead): bool
    {
        $url = getenv('PERFEX_CONNECT_LEAD_HOOK_URL') ?: config('nm-studio.perfex.lead_hook_url', '');
        $token = getenv('PERFEX_CONNECT_LEAD_HOOK_TOKEN') ?: config('nm-studio.perfex.lead_hook_token', '');

        if (! is_string($url) || ! is_string($token) || $url === '' || $token === '' || ! function_exists('curl_init')) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        if ($scheme !== 'https' || ! is_string($host) || $host === '') {
            return false;
        }

        try {
            $body = json_encode($lead, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException) {
            return false;
        }

        $handle = curl_init($url);
        if ($handle === false) {
            return false;
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-Pcx-Token: '.$token,
            ],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $failed = curl_errno($handle) !== 0;
        curl_close($handle);

        return ! $failed && $status >= 200 && $status < 300;
    }
}
