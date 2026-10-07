/**
 * Client-side validation. Mirrors the server rules (App\Http\Requests\*)
 * to give instant feedback - the server always validates again.
 */

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const DATE_PATTERN = /^(\d{4})-(\d{2})-(\d{2})$/;

/** True for a real calendar date in YYYY-MM-DD format (rejects 2025-02-30). */
export function isValidDate(value) {
    const match = DATE_PATTERN.exec(value);
    if (!match) return false;

    const [, year, month, day] = match.map(Number);
    const date = new Date(year, month - 1, day);

    return date.getFullYear() === year
        && date.getMonth() === month - 1
        && date.getDate() === day;
}

/**
 * @param {{ email: string, password: string }} data
 * @returns {Record<string, string>} field => message
 */
export function validateLogin({ email, password }) {
    const errors = {};

    if (!email) errors.email = 'Email is required.';
    else if (!EMAIL_PATTERN.test(email)) errors.email = 'Please enter a valid email address.';

    if (!password) errors.password = 'Password is required.';

    return errors;
}

/**
 * @param {{ title: string, description: string, priority: string, status: string, due_date: string }} data
 * @param {{ priorities: string[], statuses: string[] }} allowed
 * @returns {Record<string, string>} field => message
 */
export function validateTask(data, { priorities, statuses }) {
    const errors = {};

    if (!data.title) errors.title = 'Title is required.';
    else if (data.title.length > 255) errors.title = 'Title may not be longer than 255 characters.';

    if (data.description.length > 2000) {
        errors.description = 'Description may not be longer than 2000 characters.';
    }

    if (!priorities.includes(data.priority)) errors.priority = 'Please choose a valid priority.';
    if (!statuses.includes(data.status)) errors.status = 'Please choose a valid status.';

    if (data.due_date && !isValidDate(data.due_date)) {
        errors.due_date = 'The due date must be a valid date (YYYY-MM-DD).';
    }

    return errors;
}
