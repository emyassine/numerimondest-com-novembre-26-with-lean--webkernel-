<?php

namespace Webkernel\Platform\Panel\Concerns;

use Webkernel\Platform\Enums\ThemeMode;
use Webkernel\Platform\Support\Assets\Theme;
use Webkernel\Platform\Support\Facades\PlatformAsset;
use Webkernel\View\Htmlable;
use \Webkernel\View\Vite;

trait HasTheme
{
    protected string | Htmlable | Theme | null $theme = null;

    /**
     * @var string | array<string>
     */
    protected string | array | null $viteTheme = null;

    protected ?string $viteThemeBuildDirectory = null;

    protected ThemeMode $defaultThemeMode = ThemeMode::System;

    /**
     * @param  string | array<string>  $theme
     */
    public function viteTheme(string | array $theme, ?string $buildDirectory = null): static
    {
        $this->viteTheme = $theme;
        $this->viteThemeBuildDirectory = $buildDirectory;

        return $this;
    }

    /**
     * @return string | array<string> | null
     */
    public function getViteTheme(): string | array | null
    {
        return $this->viteTheme;
    }

    public function theme(string | Htmlable | Theme $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getTheme(): Theme
    {
        if (filled($viteTheme = $this->getViteTheme())) {
            $this->theme = app(Vite::class)($viteTheme, $this->viteThemeBuildDirectory);
        }

        if (blank($this->theme)) {
            return $this->getDefaultTheme();
        }

        if ($this->theme instanceof Theme) {
            return $this->theme;
        }

        if ($this->theme instanceof Htmlable) {
            return Theme::make('app')->html($this->theme);
        }

        $theme = PlatformAsset::getTheme($this->theme);

        return $theme ?? Theme::make('app', path: $this->theme);
    }

    public function getDefaultTheme(): Theme
    {
        return PlatformAsset::getTheme('app');
    }

    public function defaultThemeMode(ThemeMode $mode): static
    {
        $this->defaultThemeMode = $mode;

        return $this;
    }

    public function getDefaultThemeMode(): ThemeMode
    {
        return $this->defaultThemeMode;
    }
}
