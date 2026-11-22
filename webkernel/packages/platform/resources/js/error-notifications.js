document.addEventListener('livewire:init', () => {
    Livewire.interceptRequest(({ request, onError, onFailure }) => {
        onError(({ response, preventDefault }) => {
            const errorNotifications = window.platformErrorNotifications

            if (!errorNotifications) {
                return
            }

            try {
                const payload = request?.payload
                if (payload && payload.components.length === 1) {
                    for (const component of payload.components) {
                        if (
                            JSON.parse(component.snapshot).data
                                .isPlatformNotificationsComponent
                        ) {
                            return
                        }
                    }
                }
            } catch (error) {
                //
            }

            const status = response?.status ?? ''
            const errorNotification =
                errorNotifications[status] ?? errorNotifications['']

            if (errorNotification.isDisabled === true) {
                return
            }

            preventDefault()

            if (errorNotification.isHidden === true) {
                return
            }

            new PlatformNotification()
                .title(errorNotification.title)
                .body(errorNotification.body)
                .danger()
                .send()
        })

        onFailure(() => {
            const errorNotifications = window.platformErrorNotifications

            if (!errorNotifications) {
                return
            }

            const errorNotification = errorNotifications['']
            new PlatformNotification()
                .title(errorNotification.title)
                .body(errorNotification.body)
                .danger()
                .send()
        })
    })
})
