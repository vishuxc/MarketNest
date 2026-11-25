for (const panel of document.querySelectorAll('[data-browse-filters]')) {
    const desktop = window.matchMedia('(min-width: 54rem)');
    const updatePanel = () => {
        panel.open = desktop.matches || panel.hasAttribute('data-has-errors');
    };
    updatePanel();
    desktop.addEventListener('change', updatePanel);

    const form = panel.querySelector('[data-browse-form]');
    const { country, state, city } = form.elements;

    function restrictLocations(select, parentValues) {
        if (!select) return;

        const previous = select.value;
        for (const option of select.options) {
            const mismatch = option.value && Object.entries(parentValues)
                .some(([parent, value]) => value && option.dataset[parent] !== value);
            option.hidden = Boolean(mismatch);
            option.disabled = Boolean(mismatch);
        }
        if (select.selectedOptions[0]?.disabled) select.value = '';
        if (previous !== select.value) select.dispatchEvent(new Event('change'));
    }

    function restrictCities() {
        restrictLocations(city, { country: country.value, state: state.value });
    }

    function restrictStates() {
        restrictLocations(state, { country: country.value });
        restrictCities();
    }

    for (const child of form.querySelectorAll('[data-parent]')) {
        const parent = form.elements[child.dataset.parent];
        if (!parent) continue;

        const status = child.closest('.listing-field').querySelector('[data-browse-status]');
        const placeholder = child.options[0].text;
        let pending;

        parent.addEventListener('change', async () => {
            pending?.abort();
            child.replaceChildren(new Option(placeholder, ''));
            child.disabled = true;
            status.textContent = '';

            if (!parent.value) return;

            const request = new AbortController();
            pending = request;
            status.textContent = 'Loading options…';

            try {
                const url = child.dataset.options.replace('__parent__', encodeURIComponent(parent.value));
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                if (!response.ok) throw new Error('Options unavailable');

                const options = await response.json();
                if (request.signal.aborted) return;

                for (const option of options) {
                    child.add(new Option(option.name, option.id));
                }

                child.disabled = options.length === 0;
                status.textContent = options.length ? '' : 'No options available.';
            } catch {
                if (!request.signal.aborted) {
                    status.textContent = 'Could not load options. Apply filters to reload them.';
                }
            }
        });
    }
    country.addEventListener('change', restrictStates);
    state.addEventListener('change', restrictCities);
    restrictStates();
}
