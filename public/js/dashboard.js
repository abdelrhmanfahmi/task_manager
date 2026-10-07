import { request, HttpError } from './modules/http.js';
import { validateTask } from './modules/validation.js';
import { clearErrors, showErrors } from './modules/form-errors.js';
import { toast } from './modules/toast.js';

// ---------------------------------------------------------------------------
// DOM references
// ---------------------------------------------------------------------------
const root = document.getElementById('dashboard');
const tasksUrl = root.dataset.tasksUrl;
const loginUrl = root.dataset.loginUrl;

const list = document.getElementById('task-list');
const rowTemplate = document.getElementById('task-row-template');
const emptyState = document.getElementById('empty-state');

const searchInput = document.getElementById('search');
const statusFilter = document.getElementById('filter-status');
const priorityFilter = document.getElementById('filter-priority');

const taskModal = document.getElementById('task-modal');
const taskForm = document.getElementById('task-form');
const fields = taskForm.elements;
const taskModalTitle = document.getElementById('task-modal-title');
const taskFormError = document.getElementById('task-form-error');
const taskSubmit = document.getElementById('task-submit');

const confirmModal = document.getElementById('confirm-modal');
const confirmTitle = document.getElementById('confirm-task-title');
const confirmButton = document.getElementById('confirm-delete-btn');

// Allowed enum values come from the server-rendered <select> options,
// so PHP enums stay the single source of truth.
const allowed = {
    priorities: [...fields.priority.options].map((o) => o.value),
    statuses: [...fields.status.options].map((o) => o.value),
};

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------
const state = {
    tasks: [],
    editingId: null, // null = creating a new task
    deletingId: null,
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
const dateFormatter = new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

function formatDate(value) {
    if (!value) return '—';
    const [year, month, day] = value.split('-').map(Number);
    return dateFormatter.format(new Date(year, month - 1, day));
}

function debounce(fn, delay = 200) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

function findTask(id) {
    return state.tasks.find((task) => task.id === id);
}

/** Session expired or CSRF token mismatch: send the user back to login. */
function handleAuthError(error) {
    if (error instanceof HttpError && (error.status === 401 || error.status === 419)) {
        window.location.href = loginUrl;
        return true;
    }
    return false;
}

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------
function updateStats(stats) {
    Object.entries(stats).forEach(([key, value]) => {
        const el = document.querySelector(`[data-stat="${key}"]`);
        if (el) el.textContent = value;
    });
}

/** Search by title + filter by status / priority - handled entirely client-side. */
function getVisibleTasks() {
    const term = searchInput.value.trim().toLowerCase();
    const status = statusFilter.value;
    const priority = priorityFilter.value;

    return state.tasks.filter((task) =>
        (!term || task.title.toLowerCase().includes(term))
        && (!status || task.status === status)
        && (!priority || task.priority === priority)
    );
}

/** Build a table row. Only textContent is used, so user data is never parsed as HTML. */
function createRow(task) {
    const row = rowTemplate.content.firstElementChild.cloneNode(true);
    const field = (name) => row.querySelector(`[data-field="${name}"]`);

    row.dataset.id = task.id;

    field('title').textContent = task.title;
    field('description').textContent = task.description || '—';

    const priority = field('priority');
    priority.textContent = task.priority_label;
    priority.classList.add(`badge--${task.priority}`);

    const status = field('status');
    status.textContent = task.status_label;
    status.classList.add(`badge--${task.status}`);

    const due = field('due_date');
    due.textContent = formatDate(task.due_date);
    if (task.is_overdue) {
        due.classList.add('is-overdue');
        due.title = 'Overdue';
    }

    return row;
}

function render() {
    const visible = getVisibleTasks();

    list.replaceChildren(...visible.map(createRow));

    if (state.tasks.length === 0) {
        emptyState.textContent = 'You have no tasks yet. Click “Add Task” to create your first one.';
        emptyState.hidden = false;
    } else if (visible.length === 0) {
        emptyState.textContent = 'No tasks match your search or filters.';
        emptyState.hidden = false;
    } else {
        emptyState.hidden = true;
    }
}

// ---------------------------------------------------------------------------
// Data loading
// ---------------------------------------------------------------------------
async function loadTasks() {
    try {
        const data = await request(tasksUrl);
        state.tasks = data.tasks;
        updateStats(data.stats);
        render();
    } catch (error) {
        if (handleAuthError(error)) return;
        list.replaceChildren();
        emptyState.textContent = 'Could not load your tasks. Please refresh the page.';
        emptyState.hidden = false;
    }
}

// ---------------------------------------------------------------------------
// Add / Edit modal
// ---------------------------------------------------------------------------
function openTaskModal(task = null) {
    state.editingId = task?.id ?? null;

    taskForm.reset();
    clearErrors(taskForm);
    taskFormError.hidden = true;

    taskModalTitle.textContent = task ? 'Edit Task' : 'Add Task';
    taskSubmit.textContent = task ? 'Update Task' : 'Save Task';

    if (task) {
        fields.title.value = task.title;
        fields.description.value = task.description ?? '';
        fields.priority.value = task.priority;
        fields.status.value = task.status;
        fields.due_date.value = task.due_date ?? '';
    }

    taskModal.showModal();
    fields.title.focus();
}

function readTaskForm() {
    return {
        title: fields.title.value.trim(),
        description: fields.description.value.trim(),
        priority: fields.priority.value,
        status: fields.status.value,
        due_date: fields.due_date.value,
    };
}

async function saveTask(event) {
    event.preventDefault();
    taskFormError.hidden = true;

    const data = readTaskForm();
    const errors = validateTask(data, allowed);

    if (Object.keys(errors).length > 0) {
        showErrors(taskForm, errors);
        return;
    }

    clearErrors(taskForm);
    taskSubmit.disabled = true;

    const isEdit = state.editingId !== null;
    const payload = { ...data, description: data.description || null, due_date: data.due_date || null };

    try {
        const result = isEdit
            ? await request(`${tasksUrl}/${state.editingId}`, { method: 'PUT', body: payload })
            : await request(tasksUrl, { method: 'POST', body: payload });

        if (isEdit) {
            state.tasks = state.tasks.map((task) => (task.id === result.task.id ? result.task : task));
        } else {
            state.tasks.unshift(result.task);
        }

        updateStats(result.stats);
        render();
        taskModal.close();
        toast(result.message);
    } catch (error) {
        if (handleAuthError(error)) return;

        if (error instanceof HttpError && error.status === 422) {
            showErrors(taskForm, error.fieldErrors);
        } else {
            taskFormError.textContent = error.message || 'Something went wrong. Please try again.';
            taskFormError.hidden = false;
        }
    } finally {
        taskSubmit.disabled = false;
    }
}

// ---------------------------------------------------------------------------
// Delete with confirmation
// ---------------------------------------------------------------------------
function openConfirmModal(task) {
    state.deletingId = task.id;
    confirmTitle.textContent = task.title;
    confirmModal.showModal();
}

async function deleteTask() {
    const id = state.deletingId;
    if (id === null) return;

    confirmButton.disabled = true;

    try {
        const result = await request(`${tasksUrl}/${id}`, { method: 'DELETE' });

        state.tasks = state.tasks.filter((task) => task.id !== id);
        updateStats(result.stats);
        render();
        toast(result.message);
    } catch (error) {
        if (handleAuthError(error)) return;
        toast(error.message || 'Could not delete the task.', 'error');
    } finally {
        confirmButton.disabled = false;
        state.deletingId = null;
        confirmModal.close();
    }
}

// ---------------------------------------------------------------------------
// Event listeners
// ---------------------------------------------------------------------------
document.getElementById('add-task-btn').addEventListener('click', () => openTaskModal());
taskForm.addEventListener('submit', saveTask);
confirmButton.addEventListener('click', deleteTask);

// Edit / Delete buttons (event delegation on the table body).
list.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;

    const task = findTask(Number(button.closest('tr').dataset.id));
    if (!task) return;

    if (button.dataset.action === 'edit') openTaskModal(task);
    if (button.dataset.action === 'delete') openConfirmModal(task);
});

// Close buttons and backdrop clicks for both dialogs.
[taskModal, confirmModal].forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-close]')) {
            dialog.close();
        }
    });
});

searchInput.addEventListener('input', debounce(render));
statusFilter.addEventListener('change', render);
priorityFilter.addEventListener('change', render);

loadTasks();
