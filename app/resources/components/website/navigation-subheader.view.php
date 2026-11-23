<style>
    .studio-page-subheader {
        box-sizing: border-box;
        width: 100%;
        min-height: 40px;
        margin-top: 56px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-inline: 20px;
        border-bottom: 1px solid color-mix(in srgb, currentColor 12%, transparent);
        font: 400 11px/1.2 var(--nm-body);
    }
    .studio-page-subheader__start,
    .studio-page-subheader__end {
        min-width: 0;
        max-width: 40%;
        flex: 0 1 auto;
        display: flex;
        align-items: center;
        gap: 8px;
        overflow: hidden;
    }
    .studio-page-subheader__center {
        min-width: 0;
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        overscroll-behavior-x: contain;
        scrollbar-width: none;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }
    .studio-page-subheader__center::-webkit-scrollbar { display: none; }
    .studio-page-subheader a,
    .studio-page-subheader button { font: inherit; }
    @media (max-width: 700px) {
        .studio-page-subheader { min-height: 40px; margin-top: 56px; gap: 8px; padding-inline: 12px; }
        .studio-page-subheader__start,
        .studio-page-subheader__end { gap: 6px; }
    }
</style>

@if (! \Webkernel\Platform\Support\is_slot_empty($slot))
    <nav class="studio-page-subheader" aria-label="Page navigation">
        {{ $slot }}
    </nav>
@endif
