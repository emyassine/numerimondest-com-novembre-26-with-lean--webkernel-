import AlpineFloatingUI from '@awcodes/alpine-floating-ui'
import AlpineFocus from '@alpinejs/focus'
import AlpineLazyLoadAssets from 'alpine-lazy-load-assets'
import AsyncAlpine from 'async-alpine'
import { md5 } from 'js-md5'
import Sortable from './sortable.js'
import Tooltip from '@ryangjchandler/alpine-tooltip'
import './tooltip.js'
import dropdown from './components/dropdown.js'
import formButton from './components/form-button.js'
import modal from './components/modal.js'
import oneTimeCodeInput from './components/one-time-code.js'
import './disabled-button.js'
import copyable from './components/copyable.js'
import './partials.js'
import pluralize from './utilities/pluralize.js'

import 'tippy.js/dist/tippy.css'
import 'tippy.js/themes/light.css'

const registerPlatformSupport = (reinitializeExistingDropdowns = false) => {
    const Alpine = window.Alpine

    if (!Alpine || Alpine.__webkernelPlatformSupportInitialized) {
        return
    }

    Alpine.__webkernelPlatformSupportInitialized = true

    // Temporary compatibility fix. Remove once the minimum supported Livewire
    // version bundles Alpine's fix: https://github.com/alpinejs/alpine/pull/4922
    Alpine.interceptClone((existingElement, incomingElement) => {
        // Preserve cloaks on elements still waiting for initialization.
        if (existingElement.hasAttribute?.('x-cloak') === false) {
            incomingElement.removeAttribute?.('x-cloak')
        }
    })

    Alpine.plugin(AlpineFloatingUI)
    Alpine.plugin(AlpineFocus)
    Alpine.plugin(AlpineLazyLoadAssets)
    Alpine.plugin(AsyncAlpine)
    Alpine.plugin(Sortable)
    Alpine.plugin(Tooltip)
    Alpine.data('platformDropdown', dropdown)
    Alpine.data('platformFormButton', formButton)
    Alpine.data('platformModal', modal)
    Alpine.data('platformOneTimeCodeInput', oneTimeCodeInput)
    Alpine.data('platformCopyable', copyable)

    if (reinitializeExistingDropdowns) {
        document
            .querySelectorAll('.fi-dropdown[x-data*="platformDropdown"]')
            .forEach((element) => {
                Alpine.destroyTree(element)
                Alpine.initTree(element)
            })
    }
}

if (window.Alpine) {
    registerPlatformSupport(true)
} else {
    document.addEventListener('alpine:init', () => registerPlatformSupport(), {
        once: true,
    })
}

window.jsMd5 = md5
window.pluralize = pluralize
