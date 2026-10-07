/**
 * Show / clear inline field errors.
 * Each field has a sibling <p class="form__error" data-error-for="field">.
 */

export function clearErrors(form) {
    form.querySelectorAll('[data-error-for]').forEach((el) => {
        el.textContent = '';
    });
    form.querySelectorAll('.is-invalid').forEach((el) => {
        el.classList.remove('is-invalid');
        el.removeAttribute('aria-invalid');
    });
}

/**
 * @param {HTMLFormElement} form
 * @param {Record<string, string>} errors field => message
 */
export function showErrors(form, errors) {
    clearErrors(form);

    Object.entries(errors).forEach(([field, message]) => {
        const input = form.elements.namedItem(field);
        const target = form.querySelector(`[data-error-for="${field}"]`);

        if (target) target.textContent = message;
        if (input instanceof HTMLElement) {
            input.classList.add('is-invalid');
            input.setAttribute('aria-invalid', 'true');
        }
    });

    // Move focus to the first invalid field.
    const firstField = Object.keys(errors)[0];
    const first = firstField ? form.elements.namedItem(firstField) : null;
    if (first instanceof HTMLElement) first.focus();
}
