/**
 * Small fetch() wrapper for the JSON endpoints.
 * Sends the Laravel CSRF token and normalises error handling.
 */

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export class HttpError extends Error {
    constructor(status, data) {
        super(data?.message || `Request failed with status ${status}`);
        this.status = status;
        this.data = data ?? {};
    }

    /** Field errors from a 422 response: { field: "first message" } */
    get fieldErrors() {
        const errors = this.data.errors ?? {};
        return Object.fromEntries(
            Object.entries(errors).map(([field, messages]) => [field, messages[0]])
        );
    }
}

/**
 * @param {string} url
 * @param {{ method?: string, body?: object }} [options]
 */
export async function request(url, { method = 'GET', body } = {}) {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        throw new HttpError(response.status, data);
    }

    return data;
}
