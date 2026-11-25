<x-studio::page title="Catalogue administration · Numerimondes" description="Private catalogue administration." robots="noindex,nofollow" :page-width="['desktop' => '1200px']">
<main class="mx-auto max-w-6xl px-4 py-10" data-csrf-token="{{ $csrfToken }}">
    <nav class="mb-6 flex justify-between"><a href="/admin">Database administration</a><form method="post" action="/admin/logout"><input type="hidden" name="_csrf" value="{{ $csrfToken }}"><button type="submit" class="underline">Sign out</button></form></nav>
    <x-webkernel-platform::section heading="Offerings catalogue" icon="heroicon-o-rectangle-stack">
        <form method="post" action="/admin/offerings/logout" class="mb-4 text-right"><input type="hidden" name="_csrf" value="{{ $csrfToken }}"><button type="submit" class="text-sm underline">Sign out</button></form>
        <p class="mb-4">Editing revision {{ $revision }}. Saving creates a validated draft; publishing makes that revision available to catalogue consumers.</p>
        <label for="catalogue" class="mb-2 block font-medium">Catalogue JSON</label>
        <textarea id="catalogue" spellcheck="false" class="min-h-[60vh] w-full rounded-md border border-gray-300 p-4 font-mono text-sm">{{ $catalogueJson }}</textarea>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-webkernel-platform::button type="button" id="preview" color="gray">Validate preview</x-webkernel-platform::button>
            <x-webkernel-platform::button type="button" id="save">Save draft</x-webkernel-platform::button>
            <x-webkernel-platform::button type="button" id="publish" color="primary">Publish draft</x-webkernel-platform::button>
        </div>
        <p id="status" role="status" aria-live="polite" class="mt-3 min-h-6"></p>
        <div id="preview-results" class="mt-4" aria-live="polite"></div>
    </x-webkernel-platform::section>

    <x-webkernel-platform::section heading="Published revisions" icon="heroicon-o-clock" class="mt-6">
        <ul class="list-inside list-disc">{!! $historyHtml ?: '<li>No published revisions yet.</li>' !!}</ul>
    </x-webkernel-platform::section>
</main>

<script>
(() => {
    const editor = document.querySelector('#catalogue');
    const status = document.querySelector('#status');
    function parse() {
        const catalogue = JSON.parse(editor.value);
        if (!Array.isArray(catalogue.offerings)) throw new Error('Expected an offerings array.');
        return catalogue;
    }
    document.querySelector('#preview').addEventListener('click', () => {
        let catalogue;
        try { catalogue = parse(); }
        catch (error) { status.textContent = error.message; return; }
        post('/admin/offerings/preview', catalogue).then((data) => {
            status.textContent = `Valid draft: ${data.offering_count} offerings.`;
            const preview = document.querySelector('#preview-results');
            preview.replaceChildren();
            const table = document.createElement('table');
            table.className = 'w-full border-collapse text-left text-sm';
            const head = document.createElement('tr');
            for (const label of ['Offering', 'Type', 'Status', 'Price components', 'Relationships']) {
                const cell = document.createElement('th'); cell.className = 'border-b p-2'; cell.textContent = label; head.append(cell);
            }
            const thead = document.createElement('thead'); thead.append(head); table.append(thead);
            const tbody = document.createElement('tbody');
            for (const offering of data.offerings) {
                const row = document.createElement('tr');
                const values = [offering.name, offering.type, offering.active ? offering.visibility : 'Inactive', offering.prices.map((price) => {
                    if (typeof price.percentage === 'number') return `${price.percentage}% · ${price.billing}`;
                    const amount = price.amount_mad ?? `${price.minimum_mad ?? '—'}–${price.maximum_mad ?? '—'}`;
                    return `${amount} MAD · ${price.billing}`;
                }).join('; ') || 'Unpriced', String(offering.relations)];
                for (const value of values) { const cell = document.createElement('td'); cell.className = 'border-b p-2 align-top'; cell.textContent = value; row.append(cell); }
                tbody.append(row);
            }
            table.append(tbody); preview.append(table);
        })
            .catch((error) => { status.textContent = error.message; });
    });
    async function post(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('main[data-csrf-token]').dataset.csrfToken},
            body: data === null ? '' : JSON.stringify(data),
        });
        if (!response.ok) throw new Error((await response.text()) || `HTTP ${response.status}`);
        if (response.status === 204) return 'Draft saved.';
        return url.endsWith('/preview') ? await response.json() : await response.text();
    }
    document.querySelector('#save').addEventListener('click', async () => {
        try { status.textContent = await post('/admin/offerings/draft', parse()); }
        catch (error) { status.textContent = error.message; }
    });
    document.querySelector('#publish').addEventListener('click', async () => {
        if (!window.confirm('Publish this draft to the live catalogue?')) return;
        try { status.textContent = await post('/admin/offerings/publish', null); }
        catch (error) { status.textContent = error.message; }
    });
})();
</script>
</x-studio::page>
