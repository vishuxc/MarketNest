for (const toggle of document.querySelectorAll('.auth-toggle')) {
    const input = document.getElementById(toggle.getAttribute('aria-controls'));
    const label = toggle.querySelector('[data-toggle-text]');

    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        const showPassword = input.type === 'password';
        input.type = showPassword ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(showPassword));
        label.textContent = showPassword ? 'Hide' : 'Show';
    });
}

for (const form of document.querySelectorAll('[data-auth-form]')) {
    const button = form.querySelector('[data-loading-label]');
    const defaultLabel = button.textContent;

    form.addEventListener('submit', (event) => {
        if (button.disabled) {
            event.preventDefault();
            return;
        }

        button.textContent = button.dataset.loadingLabel;
        button.disabled = true;
        form.setAttribute('aria-busy', 'true');
    });

    window.addEventListener('pageshow', () => {
        button.textContent = defaultLabel;
        button.disabled = false;
        form.removeAttribute('aria-busy');
    });
}
