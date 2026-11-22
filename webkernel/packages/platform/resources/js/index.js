import Mousetrap from '@danharrin/alpine-mousetrap'
import globalSearch from './components/global-search.js'
import sidebar from './components/sidebar.js'
import sidebarBackground from './components/sidebar-background.js'
import sidebarTooltip from './components/sidebar-tooltip.js'
import tenantMenu from './components/tenant-menu.js'
import sidebarStore from './stores/sidebar.js'
import './dark-mode.js'
import './error-notifications.js'
import './reset-restored-resource-create-record-pages.js'
import './scroll-sidebar.js'
import './unsaved-changes-alert.js'

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Mousetrap)

    window.Alpine.data('platformGlobalSearch', globalSearch)
    window.Alpine.data('platformSidebar', sidebar)
    window.Alpine.data('platformSidebarBackground', sidebarBackground)
    window.Alpine.data('platformSidebarTooltip', sidebarTooltip)
    // Keep theme-switcher behavior in registered methods: CSP-safe Alpine cannot
    // evaluate its inline callbacks, and @js strings break quoted HTML attributes.
    window.Alpine.data('platformThemeSwitcher', () => ({
        theme: 'system',

        init() {
            this.theme =
                localStorage.getItem('platform:theme:mode') ??
                this.$el.dataset.defaultTheme
        },

        setTheme(theme) {
            this.theme = theme
            window.dispatchEvent(
                new CustomEvent('theme-changed', { detail: theme }),
            )

            const dropdown = this.$el.closest('.fi-dropdown')
            if (dropdown) window.Alpine.$data(dropdown)?.close()
        },

        isActive(theme) {
            return this.theme === theme
        },

        ariaChecked(theme) {
            return this.isActive(theme) ? 'true' : 'false'
        },
    }))
    window.Alpine.data('platformTenantMenu', tenantMenu)
    window.Alpine.store('sidebar', sidebarStore())
})
