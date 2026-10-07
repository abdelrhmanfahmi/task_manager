/**
 * Show / hide a password input.
 * Markup: <button data-password-toggle="inputId"> containing two icons,
 * [data-icon="show"] and [data-icon="hide"].
 */

/**
 * @param {ParentNode} root
 */
export function initPasswordToggles(root = document) {
    root.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!(input instanceof HTMLInputElement)) return;

        button.addEventListener('click', () => {
            setVisible(button, input, input.type === 'password');
            input.focus();
        });
    });
}

/**
 * @param {HTMLElement} button
 * @param {HTMLInputElement} input
 * @param {boolean} visible
 */
export function setVisible(button, input, visible) {
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    button.querySelector('[data-icon="show"]')?.toggleAttribute('hidden', visible);
    button.querySelector('[data-icon="hide"]')?.toggleAttribute('hidden', !visible);
}
