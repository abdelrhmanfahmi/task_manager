const container = document.getElementById('toast-container');

/**
 * Show a short notification.
 * @param {string} message
 * @param {'success' | 'error'} [type]
 */
export function toast(message, type = 'success') {
    if (!container) return;

    const el = document.createElement('div');
    el.className = `toast toast--${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.textContent = message;

    container.append(el);

    setTimeout(() => {
        el.classList.add('toast--hide');
        el.addEventListener('transitionend', () => el.remove(), { once: true });
    }, 3000);
}
