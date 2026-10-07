@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<header class="topbar">
    <div class="container topbar__inner">
        <span class="brand">{{ config('app.name') }}</span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn--ghost">Logout</button>
        </form>
    </div>
</header>

<main class="container" id="dashboard" data-tasks-url="{{ route('tasks.index') }}" data-login-url="{{ route('login') }}">
    <section class="welcome">
        <h1 class="welcome__title">Welcome, {{ auth()->user()->name }}</h1>
        <p class="welcome__subtitle">Here is an overview of your tasks.</p>
    </section>

    {{-- Statistics --}}
    <section class="stats" aria-label="Task statistics">
        <article class="stat-card">
            <span class="stat-card__label">Total Tasks</span>
            <span class="stat-card__value" data-stat="total">{{ $stats['total'] }}</span>
        </article>
        <article class="stat-card stat-card--pending">
            <span class="stat-card__label">Pending</span>
            <span class="stat-card__value" data-stat="pending">{{ $stats['pending'] }}</span>
        </article>
        <article class="stat-card stat-card--in_progress">
            <span class="stat-card__label">In Progress</span>
            <span class="stat-card__value" data-stat="in_progress">{{ $stats['in_progress'] }}</span>
        </article>
        <article class="stat-card stat-card--completed">
            <span class="stat-card__label">Completed</span>
            <span class="stat-card__value" data-stat="completed">{{ $stats['completed'] }}</span>
        </article>
    </section>

    {{-- Toolbar: search & filters --}}
    <section class="panel">
        <div class="toolbar">
            <div class="toolbar__filters">
                <label class="visually-hidden" for="search">Search tasks by title</label>
                <input class="form__input toolbar__search" type="search" id="search" placeholder="Search by title…" autocomplete="off">

                <label class="visually-hidden" for="filter-status">Filter by status</label>
                <select class="form__input" id="filter-status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>

                <label class="visually-hidden" for="filter-priority">Filter by priority</label>
                <select class="form__input" id="filter-priority">
                    <option value="">All priorities</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" class="btn btn--primary" id="add-task-btn">+ Add Task</button>
        </div>

        {{-- Task list --}}
        <div class="table-wrapper">
            <table class="task-table">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Description</th>
                        <th scope="col">Priority</th>
                        <th scope="col">Status</th>
                        <th scope="col">Due Date</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody id="task-list">
                    <tr class="task-table__message"><td colspan="6">Loading tasks…</td></tr>
                </tbody>
            </table>
        </div>

        <p class="empty-state" id="empty-state" hidden></p>
    </section>
</main>

{{-- Row template: cloned by dashboard.js, filled with textContent only (XSS safe) --}}
<template id="task-row-template">
    <tr class="task-row">
        <td data-label="Title" class="task-row__title" data-field="title"></td>
        <td data-label="Description" class="task-row__description" data-field="description"></td>
        <td data-label="Priority"><span class="badge" data-field="priority"></span></td>
        <td data-label="Status"><span class="badge" data-field="status"></span></td>
        <td data-label="Due Date" data-field="due_date"></td>
        <td class="task-row__actions">
            <button type="button" class="btn btn--sm btn--secondary" data-action="edit">Edit</button>
            <button type="button" class="btn btn--sm btn--danger" data-action="delete">Delete</button>
        </td>
    </tr>
</template>

{{-- Add / Edit task modal --}}
<dialog class="modal" id="task-modal" aria-labelledby="task-modal-title">
    <form class="form" id="task-form" novalidate>
        <header class="modal__header">
            <h2 class="modal__title" id="task-modal-title">Add Task</h2>
            <button type="button" class="modal__close" data-close aria-label="Close">&times;</button>
        </header>

        <div class="modal__body">
            <div class="alert alert--error" id="task-form-error" role="alert" hidden></div>

            <div class="form__group">
                <label class="form__label" for="task-title">Title <span class="required">*</span></label>
                <input class="form__input" type="text" id="task-title" name="title" maxlength="255" required>
                <p class="form__error" data-error-for="title"></p>
            </div>

            <div class="form__group">
                <label class="form__label" for="task-description">Description</label>
                <textarea class="form__input" id="task-description" name="description" rows="3" maxlength="2000"></textarea>
                <p class="form__error" data-error-for="description"></p>
            </div>

            <div class="form__row">
                <div class="form__group">
                    <label class="form__label" for="task-priority">Priority <span class="required">*</span></label>
                    <select class="form__input" id="task-priority" name="priority" required>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}" @selected($priority->value === 'medium')>{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                    <p class="form__error" data-error-for="priority"></p>
                </div>

                <div class="form__group">
                    <label class="form__label" for="task-status">Status <span class="required">*</span></label>
                    <select class="form__input" id="task-status" name="status" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <p class="form__error" data-error-for="status"></p>
                </div>
            </div>

            <div class="form__group">
                <label class="form__label" for="task-due-date">Due Date</label>
                <input class="form__input" type="date" id="task-due-date" name="due_date">
                <p class="form__error" data-error-for="due_date"></p>
            </div>
        </div>

        <footer class="modal__footer">
            <button type="button" class="btn btn--secondary" data-close>Cancel</button>
            <button type="submit" class="btn btn--primary" id="task-submit">Save Task</button>
        </footer>
    </form>
</dialog>

{{-- Delete confirmation modal --}}
<dialog class="modal modal--sm" id="confirm-modal" aria-labelledby="confirm-modal-title">
    <div class="modal__header">
        <h2 class="modal__title" id="confirm-modal-title">Delete task?</h2>
    </div>
    <div class="modal__body">
        <p>Are you sure you want to delete “<strong id="confirm-task-title"></strong>”? This action cannot be undone.</p>
    </div>
    <footer class="modal__footer">
        <button type="button" class="btn btn--secondary" data-close>Cancel</button>
        <button type="button" class="btn btn--danger" id="confirm-delete-btn">Delete</button>
    </footer>
</dialog>

<div class="toast-container" id="toast-container" aria-live="polite"></div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/dashboard.js') }}"></script>
@endpush
