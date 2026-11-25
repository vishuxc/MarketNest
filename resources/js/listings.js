for (const form of document.querySelectorAll('[data-listing-form]')) {
    const children = Array.from(form.querySelectorAll('[data-parent]'));
    const submit = form.querySelector('[data-listing-submit]');
    const requests = new Map();

    function syncSubmit() {
        submit.disabled = children.some((select) => select.disabled);
    }

    function reset(select) {
        requests.get(select)?.abort();
        requests.delete(select);
        select.replaceChildren(new Option(select.dataset.placeholder, ''));
        select.disabled = true;
        select.closest('.listing-field').querySelector('[data-option-status]').textContent = '';
        select.closest('.listing-field').querySelector('[data-option-retry]').hidden = true;
        syncSubmit();
    }

    function descendants(select) {
        return children.filter((child) => child.dataset.parent === select.id);
    }

    function clearDescendants(select) {
        for (const child of descendants(select)) {
            reset(child);
            clearDescendants(child);
        }
    }

    async function load(select, restore = false) {
        const parent = form.elements[select.dataset.parent];
        reset(select);
        clearDescendants(select);

        if (!parent.value) return;

        const controller = new AbortController();
        requests.set(select, controller);
        const field = select.closest('.listing-field');
        const status = field.querySelector('[data-option-status]');
        const retry = field.querySelector('[data-option-retry]');
        status.textContent = 'Loading options…';

        try {
            const url = select.dataset.options.replace('__parent__', encodeURIComponent(parent.value));
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Options unavailable');
            const options = await response.json();
            if (controller.signal.aborted) return;

            for (const option of options) {
                select.add(new Option(option.name, option.id));
            }
            select.disabled = options.length === 0;
            status.textContent = options.length ? '' : 'No options available for this selection.';

            if (restore && options.some((option) => String(option.id) === select.dataset.selected)) {
                select.value = select.dataset.selected;
                for (const child of descendants(select)) {
                    load(child, true);
                }
            }
        } catch {
            if (!controller.signal.aborted) {
                status.textContent = 'Could not load options.';
                retry.hidden = false;
            }
        } finally {
            if (requests.get(select) === controller) {
                requests.delete(select);
                syncSubmit();
            }
        }
    }

    for (const child of children) {
        const parent = form.elements[child.dataset.parent];
        parent.addEventListener('change', () => load(child));
        child.closest('.listing-field').querySelector('[data-option-retry]').addEventListener('click', () => load(child, true));

        if (!parent.dataset.parent) load(child, true);
    }

    const input = form.elements['images[]'];
    const previews = form.querySelector('[data-new-photos]');
    const imageStatus = form.querySelector('[data-image-status]');
    const existing = Array.from(form.querySelectorAll('[name="remove_images[]"]'));
    let urls = [];

    function renderPreviews() {
        for (const url of urls) URL.revokeObjectURL(url);
        urls = [];
        previews.replaceChildren();

        const files = Array.from(input.files);
        const count = existing.filter((checkbox) => !checkbox.checked).length + files.length;
        const oversized = files.some((file) => file.size > 5 * 1024 * 1024);
        const message = count > 5 ? 'Keep at most five photos total.' : oversized ? 'Each photo must be 5 MB or smaller.' : '';
        input.setCustomValidity(message);
        imageStatus.textContent = message;

        for (const [index, file] of files.entries()) {
            const preview = document.createElement('div');
            preview.className = 'listing-preview';
            const image = document.createElement('img');
            image.className = 'listing-image';
            image.alt = file.name;
            image.src = URL.createObjectURL(file);
            urls.push(image.src);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'market-nav-link';
            remove.textContent = 'Remove';
            remove.setAttribute('aria-label', 'Remove ' + file.name);
            remove.addEventListener('click', () => {
                const retained = new DataTransfer();
                files.forEach((item, position) => {
                    if (position !== index) retained.items.add(item);
                });
                input.files = retained.files;
                renderPreviews();
                input.focus();
            });

            preview.append(image, remove);
            previews.append(preview);
        }
    }

    input.addEventListener('change', renderPreviews);
    for (const checkbox of existing) checkbox.addEventListener('change', renderPreviews);
    window.addEventListener('pageshow', renderPreviews);
}

for (const form of document.querySelectorAll('[data-delete-listing]')) {
    form.addEventListener('submit', (event) => {
        if (!window.confirm('Delete this listing and its photos?')) event.preventDefault();
    });
}

function useImageFallback(image) {
    if (!image.dataset.imageFallback) return;

    if (image.getAttribute('src') !== image.dataset.imageFallback) {
        image.src = image.dataset.imageFallback;
    }
    image.alt = 'No image available';
}

for (const image of document.querySelectorAll('[data-image-fallback]')) {
    image.addEventListener('error', () => useImageFallback(image));
    if (image.complete && image.naturalWidth === 0) useImageFallback(image);
}

for (const gallery of document.querySelectorAll('[data-photo-gallery]')) {
    const primary = gallery.querySelector('[data-primary-photo]');

    for (const thumbnail of gallery.querySelectorAll('[data-thumbnail]')) {
        thumbnail.addEventListener('click', (event) => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

            event.preventDefault();
            gallery.querySelector('[aria-current]')?.removeAttribute('aria-current');
            thumbnail.setAttribute('aria-current', 'true');
            const image = thumbnail.querySelector('img');
            primary.src = image.src;
            primary.alt = image.alt;
        });
    }
}
