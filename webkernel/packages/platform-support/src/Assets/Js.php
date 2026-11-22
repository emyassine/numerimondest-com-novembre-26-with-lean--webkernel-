<?php

namespace Webkernel\Platform\Support\Assets;

use Webkernel\Platform\Support\Facades\PlatformView;
use Webkernel\View\Htmlable;
use Webkernel\View\HtmlString;
use Webkernel\View\View as WebkernelView;

class Js extends Asset
{
    protected bool $isAsync = false;

    protected bool $isDeferred = false;

    protected bool $isCore = false;

    protected bool $isNavigateOnce = true;

    protected bool $isModule = false;

    /**
     * @var array<string, string>
     */
    protected array $extraAttributes = [];

    protected string | Htmlable | null $html = null;

    public function async(bool $condition = true): static
    {
        $this->isAsync = $condition;

        return $this;
    }

    public function defer(bool $condition = true): static
    {
        $this->isDeferred = $condition;

        return $this;
    }

    public function core(bool $condition = true): static
    {
        $this->isCore = $condition;

        return $this;
    }

    public function navigateOnce(bool $condition = true): static
    {
        $this->isNavigateOnce = $condition;

        return $this;
    }

    public function module(bool $condition = true): static
    {
        $this->isModule = $condition;

        return $this;
    }

    public function html(string | Htmlable | null $html): static
    {
        $this->html = $html;

        return $this;
    }

    public function isAsync(): bool
    {
        return $this->isAsync;
    }

    public function isDeferred(): bool
    {
        return $this->isDeferred;
    }

    public function isCore(): bool
    {
        return $this->isCore;
    }

    public function isNavigateOnce(): bool
    {
        return $this->isNavigateOnce;
    }

    public function isModule(): bool
    {
        return $this->isModule;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    public function extraAttributes(array $attributes): static
    {
        // Security: Attribute values are not escaped when rendered. Never
        // pass unsanitized user input as attribute names or values.

        $this->extraAttributes = $attributes;

        return $this;
    }

    public function getHtml(): Htmlable
    {
        $html = $this->html;

        if (str($html)->contains('<script')) {
            return $html instanceof Htmlable ? $html : new HtmlString($html);
        }

        $html ??= $this->getSrc();

        $async = $this->isAsync() ? 'async' : '';
        $defer = $this->isDeferred() ? 'defer' : '';
        $module = $this->isModule() ? 'type="module"' : '';
        $extraAttributesHtml = $this->getExtraAttributesHtml();

        // Rendered inline after `src` so that the output is unchanged, byte for
        // byte, when no nonce is configured.
        $nonce = WebkernelView::shared('csp_nonce');
        $nonceHtml = is_string($nonce) && $nonce !== ''
            ? ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
            : '';

        $hasSpaMode = PlatformView::hasSpaMode();

        $navigateOnce = ($hasSpaMode && $this->isNavigateOnce()) ? 'data-navigate-once' : '';
        $navigateTrack = $hasSpaMode ? 'data-navigate-track' : '';

        return new HtmlString(
            "
            <script
                src=\"{$html}\"{$nonceHtml}
                {$async}
                {$defer}
                {$module}
                {$extraAttributesHtml}
                {$navigateOnce}
                {$navigateTrack}
            ></script>
        ",
        );
    }

    /**
     * @return array<string, string>
     */
    public function getExtraAttributes(): array
    {
        return $this->extraAttributes;
    }

    public function getExtraAttributesHtml(): string
    {
        $attributes = '';

        foreach ($this->getExtraAttributes() as $key => $value) {
            $attributes .= " {$key}=\"{$value}\"";
        }

        return $attributes;
    }

    public function getSrc(): string
    {
        if ($this->isRemote()) {
            return $this->getPath();
        }

        return $this->getUrl() . '?v=' . $this->getVersion();
    }

    public function getRelativePublicPath(): string
    {
        $path = config('platform.assets_path', '');

        return ltrim("{$path}/js/{$this->getPackage()}/{$this->getId()}.js", '/');
    }

    public function getPublicPath(): string
    {
        return public_path($this->getRelativePublicPath());
    }
}
