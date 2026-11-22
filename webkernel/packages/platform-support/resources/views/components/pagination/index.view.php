@props([
    'currentPageOptionProperty' => 'tableRecordsPerPage',
    'extremeLinks' => false,
    'paginator',
    'pageOptions' => [],
])

@php
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\Support\View\SupportIconAlias;
    use \Webkernel\Database\Contracts\Pagination\CursorPaginator;
    use \Webkernel\Database\Pagination\LengthAwarePaginator;
    use Webkernel\Utilities\Number;

    $isRtl = __('webkernel-platform::layout.direction') === 'rtl';
    $isSimple = ! $paginator instanceof LengthAwarePaginator;
@endphp

<nav
    aria-label="{{ __('webkernel::components/pagination.label') }}"
    {{
        $attributes->class([
            'fi-pagination',
            'fi-simple' => $isSimple,
        ])
    }}
>
    @if (! $paginator->onFirstPage())
        @php
            if ($paginator instanceof CursorPaginator) {
                $wireClickAction = "setPage('{$paginator->previousCursor()->encode()}', '{$paginator->getCursorName()}')";
            } else {
                $wireClickAction = "previousPage('{$paginator->getPageName()}')";
            }
        @endphp

        <x-webkernel-platform::button
            color="gray"
            rel="prev"
            :wire:click="$wireClickAction"
            :wire:key="$this->getId() . '.pagination.previous'"
            class="fi-pagination-previous-btn"
        >
            {{ __('webkernel::components/pagination.actions.previous.label') }}
        </x-webkernel-platform::button>
    @endif

    @if (! $isSimple)
        <span class="fi-pagination-overview">
            {{
                trans_choice(
                    'webkernel::components/pagination.overview',
                    $paginator->total(),
                    [
                        'first' => Number::format($paginator->firstItem() ?? 0),
                        'last' => Number::format($paginator->lastItem() ?? 0),
                        'total' => Number::format($paginator->total()),
                    ],
                )
            }}
        </span>
    @endif

    @if (count($pageOptions) > 1)
        <div class="fi-pagination-records-per-page-select-ctn">
            <label class="fi-pagination-records-per-page-select fi-compact">
                <x-webkernel-platform::input.wrapper>
                    <x-webkernel-platform::input.select
                        :wire:model.live="$currentPageOptionProperty"
                    >
                        @foreach ($pageOptions as $option)
                            <option value="{{ $option }}">
                                {{ $option === 'all' ? __('webkernel::components/pagination.fields.records_per_page.options.all') : $option }}
                            </option>
                        @endforeach
                    </x-webkernel-platform::input.select>
                </x-webkernel-platform::input.wrapper>

                <span class="fi-sr-only">
                    {{ __('webkernel::components/pagination.fields.records_per_page.label') }}
                </span>
            </label>

            <label class="fi-pagination-records-per-page-select">
                <x-webkernel-platform::input.wrapper
                    :prefix="__('webkernel::components/pagination.fields.records_per_page.label')"
                >
                    <x-webkernel-platform::input.select
                        :wire:model.live="$currentPageOptionProperty"
                    >
                        @foreach ($pageOptions as $option)
                            <option value="{{ $option }}">
                                {{ $option === 'all' ? __('webkernel::components/pagination.fields.records_per_page.options.all') : $option }}
                            </option>
                        @endforeach
                    </x-webkernel-platform::input.select>
                </x-webkernel-platform::input.wrapper>
            </label>
        </div>
    @endif

    @if ($paginator->hasMorePages())
        @php
            if ($paginator instanceof CursorPaginator) {
                $wireClickAction = "setPage('{$paginator->nextCursor()->encode()}', '{$paginator->getCursorName()}')";
            } else {
                $wireClickAction = "nextPage('{$paginator->getPageName()}')";
            }
        @endphp

        <x-webkernel-platform::button
            color="gray"
            rel="next"
            :wire:click="$wireClickAction"
            :wire:key="$this->getId() . '.pagination.next'"
            class="fi-pagination-next-btn"
        >
            {{ __('webkernel::components/pagination.actions.next.label') }}
        </x-webkernel-platform::button>
    @endif

    @if ((! $isSimple) && $paginator->hasPages())
        <ol class="fi-pagination-items">
            @if (! $paginator->onFirstPage())
                @if ($extremeLinks)
                    <x-webkernel-platform::pagination.item
                        :aria-label="__('webkernel::components/pagination.actions.first.label')"
                        :icon="$isRtl ? Heroicon::ChevronDoubleRight : Heroicon::ChevronDoubleLeft"
                        :icon-alias="
                            $isRtl
                            ? SupportIconAlias::PAGINATION_FIRST_BUTTON_RTL
                            : SupportIconAlias::PAGINATION_FIRST_BUTTON
                        "
                        rel="first"
                        :wire:click="'gotoPage(1, \'' . $paginator->getPageName() . '\')'"
                        :wire:key="$this->getId() . '.pagination.first'"
                    />
                @endif

                <x-webkernel-platform::pagination.item
                    :aria-label="__('webkernel::components/pagination.actions.previous.label')"
                    :icon="$isRtl ? Heroicon::ChevronRight : Heroicon::ChevronLeft"
                    {{-- @deprecated Use `SupportIconAlias::PAGINATION_PREVIOUS_BUTTON_RTL` instead of `SupportIconAlias::PAGINATION_PREVIOUS_BUTTON` for RTL. --}}
                    :icon-alias="
                        $isRtl
                        ? [
                            SupportIconAlias::PAGINATION_PREVIOUS_BUTTON_RTL,
                            SupportIconAlias::PAGINATION_PREVIOUS_BUTTON,
                        ]
                        : SupportIconAlias::PAGINATION_PREVIOUS_BUTTON
                    "
                    rel="prev"
                    :wire:click="'previousPage(\'' . $paginator->getPageName() . '\')'"
                    :wire:key="$this->getId() . '.pagination.previous'"
                />
            @endif

            @foreach ($paginator->elements() as $element)
                @if (is_string($element))
                    <x-webkernel-platform::pagination.item disabled :label="$element" />
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <x-webkernel-platform::pagination.item
                            :active="$page === $paginator->currentPage()"
                            :aria-label="trans_choice('webkernel::components/pagination.actions.go_to_page.label', $page, ['page' => Number::format($page)])"
                            :label="$page"
                            :wire:click="'gotoPage(' . $page . ', \'' . $paginator->getPageName() . '\')'"
                            :wire:key="$this->getId() . '.pagination.' . $paginator->getPageName() . '.' . $page"
                        />
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <x-webkernel-platform::pagination.item
                    :aria-label="__('webkernel::components/pagination.actions.next.label')"
                    :icon="$isRtl ? Heroicon::ChevronLeft : Heroicon::ChevronRight"
                    {{-- @deprecated Use `SupportIconAlias::PAGINATION_NEXT_BUTTON_RTL` instead of `SupportIconAlias::PAGINATION_NEXT_BUTTON` for RTL. --}}
                    :icon-alias="
                        $isRtl
                        ? [
                            SupportIconAlias::PAGINATION_NEXT_BUTTON_RTL,
                            SupportIconAlias::PAGINATION_NEXT_BUTTON,
                        ]
                        : SupportIconAlias::PAGINATION_NEXT_BUTTON
                    "
                    rel="next"
                    :wire:click="'nextPage(\'' . $paginator->getPageName() . '\')'"
                    :wire:key="$this->getId() . '.pagination.next'"
                />

                @if ($extremeLinks)
                    <x-webkernel-platform::pagination.item
                        :aria-label="__('webkernel::components/pagination.actions.last.label')"
                        :icon="$isRtl ? Heroicon::ChevronDoubleLeft : Heroicon::ChevronDoubleRight"
                        :icon-alias="
                            $isRtl
                            ? SupportIconAlias::PAGINATION_LAST_BUTTON_RTL
                            : SupportIconAlias::PAGINATION_LAST_BUTTON
                        "
                        rel="last"
                        :wire:click="'gotoPage(' . $paginator->lastPage() . ', \'' . $paginator->getPageName() . '\')'"
                        :wire:key="$this->getId() . '.pagination.last'"
                    />
                @endif
            @endif
        </ol>
    @endif
</nav>
