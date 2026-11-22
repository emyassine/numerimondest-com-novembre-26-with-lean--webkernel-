<?php

namespace Webkernel\Platform\Support\Commands\Concerns;


use function console;
use Webkernel\Platform\Exceptions\NoDefaultPanelSetException;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Panel;
use Webkernel\Platform\Support\Commands\Exceptions\FailureCommandOutput;
use Webkernel\Utilities\Arr;

trait HasPanel
{
    protected ?Panel $panel;

    protected function configurePanel(string $question, ?string $initialQuestion = null): void
    {
        if (! class_exists(Panel::class)) {
            $this->panel = null;

            return;
        }

        $panelName = ($this->hasArgument('panel') ? $this->argument('panel') : null) ?? $this->option('panel'); /** @phpstan-ignore-line */
        $this->panel = filled($panelName) ? Platform::getPanel($panelName, isStrict: false) : null;

        if ($this->panel) {
            return;
        }

        if (filled($initialQuestion) && (! console()->confirm(label: $initialQuestion))) {
            $this->panel = null;

            return;
        }

        $panels = Platform::getPanels();

        if (empty($panels)) {
            if (filled($initialQuestion)) {
                $this->panel = null;

                return;
            }

            $this->components->error('Platform has not been installed yet: ./platform platform:install --panels');

            throw new FailureCommandOutput;
        }

        if (count($panels) > 1) {
            try {
                $defaultPanelId = Platform::getDefaultPanel()->getId();
            } catch (NoDefaultPanelSetException) {
                $defaultPanelId = null;
            }

            /** @var Panel $panel */
            $panel = $panels[console()->select(
                label: $question,
                options: array_map(
                    fn (Panel $panel): string => $panel->getId(),
                    $panels,
                ),
                default: $defaultPanelId,
            )];
        } else {
            /** @var Panel $panel */
            $panel = Arr::first($panels);
        }

        $this->panel = $panel;
    }
}
