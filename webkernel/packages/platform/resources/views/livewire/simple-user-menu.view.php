@php
    use Webkernel\Platform\Enums\UserMenuPosition;
@endphp

<div>
    <div class="fi-simple-user-menu-ctn">
        <x-webkernel-platform::user-menu :position="UserMenuPosition::Topbar" />
    </div>

    <x-webkernel-actions::modals />
</div>
