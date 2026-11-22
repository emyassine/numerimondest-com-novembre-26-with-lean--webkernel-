<?php

namespace Webkernel\Platform\AvatarProviders;

use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Support\Colors\Color;
use Webkernel\Platform\Support\Facades\PlatformColor;
use \Webkernel\Accounts\Contracts\Authenticatable;
use Webkernel\ORM\Model;

class UiAvatarsProvider implements Contracts\AvatarProvider
{
    public function get(Model | Authenticatable $record): string
    {
        $name = str(Platform::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->map(function (string $segment): string {
                // Skip leading punctuation (e.g. a "[SYSTEM] Admin" service-account
                // naming convention) so it doesn't become part of the initials.
                $letters = preg_replace('/^[^\p{L}\p{N}]+/u', '', $segment);

                return filled($letters) ? mb_substr($letters, 0, 1) : '';
            })
            ->join(' ');

        $background = Color::convertToHex(PlatformColor::getColor('gray')[950] ?? Color::Gray[950]);

        if (preg_match('/\A#[0-9a-fA-F]{6}\z/', $background) !== 1) {
            $background = '#111827';
        }

        $name = mb_substr(preg_replace('/[^\p{L}\p{N} ]/u', '', $name) ?: '', 0, 8);
        $escapedName = htmlspecialchars($name !== '' ? $name : '?', ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" role="img">'
            . '<rect width="100" height="100" rx="50" fill="' . $background . '" />'
            . '<text x="50" y="51" dominant-baseline="middle" text-anchor="middle" '
            . 'font-family="system-ui,sans-serif" font-size="38" font-weight="600" fill="#fff">'
            . $escapedName
            . '</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
