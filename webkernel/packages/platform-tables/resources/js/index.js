import table, { tableFilters } from './components/table.js'
import columnManager from './components/column-manager.js'

document.addEventListener('alpine:init', () => {
    window.Alpine.data('platformTable', table)
    window.Alpine.data('platformTableFilters', tableFilters)
    window.Alpine.data('platformTableColumnManager', columnManager)
})
