@if (platform()->hasUnsavedChangesAlerts())
    @script
        <script>
            setUpPlatformUnsavedActionChangesAlert({
                resolveLivewireComponentUsing: () => @this,
                $wire,
            })
        </script>
    @endscript
@endif
