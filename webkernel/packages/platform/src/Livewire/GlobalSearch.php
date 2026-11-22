<?php

namespace Webkernel\Platform\Livewire;

use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\GlobalSearch\GlobalSearchResults;
use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;
use Webkernel\Liveview\Component;

class GlobalSearch extends Component
{
    public ?string $search = '';

    public function getResults(): ?GlobalSearchResults
    {
        $search = trim($this->search);

        if (blank($search)) {
            return null;
        }

        $results = Platform::getGlobalSearchProvider()->getResults($search);

        if ($results === null) {
            return $results;
        }

        $this->dispatch('open-global-search-results');

        return $results;
    }

    public function render(): View|ViewResult
    {
        return \Webkernel\View\View::resolve('webkernel-platform::livewire.global-search', [
            'results' => $this->getResults(),
        ]);
    }
}
