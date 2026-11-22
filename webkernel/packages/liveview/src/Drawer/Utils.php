<?php

namespace Webkernel\Liveview\Drawer;

use Webkernel\View\View as WebkernelView;
use Webkernel\View\Htmlable;
use Webkernel\View\ViewResult;

use \Webkernel\HttpFoundation\Request;
use Webkernel\Liveview\Exceptions\RootTagMissingFromViewException;

use Webkernel\Liveview\Features\SupportFileUploads\FileUploadConfiguration;
use function Webkernel\Liveview\invade;

class Utils extends BaseUtils
{
    static function insertAttributesIntoHtmlRoot($html, $attributes) {
        $attributesFormattedForHtmlElement = static::stringifyHtmlAttributes($attributes);

        preg_match('/(?:\n\s*|^\s*)<([a-zA-Z0-9\-]+)/', $html, $matches, PREG_OFFSET_CAPTURE);

        throw_unless(
            count($matches),
            new RootTagMissingFromViewException
        );

        $tagName = $matches[1][0];
        $lengthOfTagName = strlen($tagName);
        $positionOfFirstCharacterInTagName = $matches[1][1];

        return substr_replace(
            $html,
            ' '.$attributesFormattedForHtmlElement,
            $positionOfFirstCharacterInTagName + $lengthOfTagName,
            0
        );
    }

    static function stringifyHtmlAttributes($attributes)
    {
        return collect($attributes)
            ->mapWithKeys(function ($value, $key) {
                return [$key => static::escapeStringForHtml($value)];
            })->map(function ($value, $key) {
                return sprintf('%s="%s"', $key, $value);
            })->implode(' ');
    }

    static function escapeStringForHtml($subject)
    {
        if (is_string($subject) || is_numeric($subject)) {
            return htmlspecialchars($subject, ENT_QUOTES|ENT_SUBSTITUTE);
        }

        return htmlspecialchars(json_encode($subject, JSON_THROW_ON_ERROR), ENT_QUOTES|ENT_SUBSTITUTE);
    }

    static function pretendResponseIsFile($file, $contentType = 'application/javascript; charset=utf-8')
    {
        $lastModified = filemtime($file);

        $content = file_get_contents($file);

        if (! is_string($content)) {
            http_response_code(404);

            return '';
        }

        return static::sendCachedContent($content, $contentType, $lastModified);
    }

    static function pretendResponseIsFileFromString($content, $filemtime, $filename = 'generated', $contentType = 'application/javascript; charset=utf-8')
    {
        return static::sendCachedContent($content, $contentType, $filemtime);
    }

    static function pretendPreviewResponseIsPreviewFile($filename)
    {
        $file = FileUploadConfiguration::path($filename);
        $storage = FileUploadConfiguration::storage();
        $mimeType = FileUploadConfiguration::mimeType($filename);
        $lastModified = FileUploadConfiguration::lastModified($file);

        return self::cachedFileResponse($filename, $mimeType, $lastModified,
            fn ($headers) => $storage->download($file, $filename, $headers));
    }

    static private function cachedFileResponse($filename, $contentType, $lastModified, $downloadCallback)
    {
        $expires = strtotime('+1 year');
        $cacheControl = 'public, max-age=31536000';

        if (static::matchesCache($lastModified)) {
            return response('', 304, [
                'Expires' => static::httpDate($expires),
                'Cache-Control' => $cacheControl,
            ]);
        }

        $headers = [
            'Content-Type' => $contentType,
            'Expires' => static::httpDate($expires),
            'Cache-Control' => $cacheControl,
            'Last-Modified' => static::httpDate($lastModified),
        ];

        if (str($filename)->endsWith('.br')) {
            $headers['Content-Encoding'] = 'br';
        }

        return $downloadCallback($headers);
    }

    static private function sendCachedContent(string $content, string $contentType, int $lastModified): string
    {
        $expires = strtotime('+1 year');
        $headers = [
            'Content-Type' => $contentType,
            'Expires' => static::httpDate($expires),
            'Cache-Control' => 'public, max-age=31536000',
            'Last-Modified' => static::httpDate($lastModified),
        ];

        if (static::matchesCache($lastModified)) {
            http_response_code(304);
        }

        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        return http_response_code() === 304 ? '' : $content;
    }

    static function matchesCache($lastModified)
    {
        $ifModifiedSince = app(Request::class)->header('if-modified-since');

        return $ifModifiedSince !== null && @strtotime($ifModifiedSince) === $lastModified;
    }

    static function httpDate($timestamp)
    {
        return sprintf('%s GMT', gmdate('D, d M Y H:i:s', $timestamp));
    }

    // Params are spread positionally into the method but read by key when authorizing,
    // so out-of-order or mixed integer keys would let those two disagree...
    static function hasUnambiguousParameterKeys(array $params)
    {
        return array_is_list($params) || array_filter(array_keys($params), 'is_int') === [];
    }

    static function containsDots($subject)
    {
        return str_contains($subject, '.');
    }

    static function dotSegments($subject)
    {
        return explode('.', $subject);
    }

    static function beforeFirstDot($subject)
    {
        return head(explode('.', $subject));
    }

    static function afterFirstDot($subject) : string
    {
        return str($subject)->after('.');
    }

    static public function hasProperty($target, $property)
    {
        return property_exists($target, static::beforeFirstDot($property));
    }

    /**
     * @return callable Restores the previously shared value for the name.
     */
    static public function shareWithViews(string $name, mixed $value): callable
    {
        $missing = new \stdClass();
        $old = WebkernelView::shared($name, $missing);

        WebkernelView::share($name, $value);

        return $revert = function () use ($name, $old, $missing) {
            if ($old === $missing) {
                WebkernelView::forget($name);
            } else {
                WebkernelView::share($name, $old);
            }
        };
    }

    static function generateView($subject, $data = []): ViewResult
    {
        if ($subject instanceof ViewResult) {
            return $subject->with($data);
        }

        if ($subject instanceof Htmlable) {
            return WebkernelView::html($subject->toHtml(), $data);
        }

        if (! is_string($subject)) {
            throw new \InvalidArgumentException('Livewire components must return a Webkernel view or a view name.');
        }

        if (str_starts_with(ltrim($subject), '<')) {
            throw new \InvalidArgumentException('Inline Livewire markup must implement Webkernel\\View\\Htmlable.');
        }

        return WebkernelView::make($subject, $data);
    }

    static function applyMiddleware(\Webkernel\HttpFoundation\Request $request, $middleware = [])
    {
        $response = (new \Webkernel\Container\Pipeline\Pipeline(app()))
            ->send($request)
            ->through($middleware)
            ->then(function() {
                return new \Webkernel\HttpFoundation\Response();
            });

        if ($response instanceof \Webkernel\HttpFoundation\RedirectResponse) {
            abort($response);
        }

        return $response;
    }

    static function extractAttributeDataFromHtml($html, $attribute)
    {
        $data = (string) str($html)->betweenFirst($attribute.'="', '"');

        return json_decode(
            htmlspecialchars_decode($data, ENT_QUOTES|ENT_SUBSTITUTE),
            associative: true,
        );
    }
}
