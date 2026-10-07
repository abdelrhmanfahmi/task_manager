import { validateLogin } from './modules/validation.js';
import { clearErrors, showErrors } from './modules/form-errors.js';
import { initPasswordToggles, setVisible } from './modules/password-toggle.js';

const form = document.getElementById('login-form');

initPasswordToggles(form);

form.addEventListener('submit', (event) => {
    const data = {
        email: form.elements.email.value.trim(),
        password: form.elements.password.value,
    };

    const errors = validateLogin(data);

    if (Object.keys(errors).length > 0) {
        event.preventDefault();
        showErrors(form, errors);
        return;
    }

    clearErrors(form);

    // Mask the password again before leaving the page.
    const toggle = form.querySelector('[data-password-toggle="password"]');
    if (toggle) setVisible(toggle, form.elements.password, false);
});
