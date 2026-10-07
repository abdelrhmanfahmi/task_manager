@extends('layouts.app')

@section('title', 'Login')
@section('body-class', 'page-auth')

@section('content')
<main class="auth-card">
    <header class="auth-card__header">
        <span class="brand brand--lg">{{ config('app.name') }}</span>
        <h1 class="auth-card__title">Sign in to your account</h1>
    </header>

    @if (session('status'))
        <div class="alert alert--success" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form id="login-form" class="form" method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="form__group">
            <label class="form__label" for="email">Email</label>
            <input class="form__input @error('email') is-invalid @enderror"
                   type="email" id="email" name="email" value="{{ old('email') }}"
                   autocomplete="email" required autofocus>
            <p class="form__error" data-error-for="email"></p>
        </div>

        <div class="form__group">
            <label class="form__label" for="password">Password</label>
            <div class="password-field">
                <input class="form__input @error('password') is-invalid @enderror"
                       type="password" id="password" name="password"
                       autocomplete="current-password" required>
                <button type="button" class="password-field__toggle"
                        data-password-toggle="password"
                        aria-controls="password" aria-pressed="false" aria-label="Show password">
                    <svg class="password-field__icon" data-icon="show" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="password-field__icon" data-icon="hide" viewBox="0 0 24 24" aria-hidden="true" hidden>
                        <path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c6.4 0 10 7 10 7a18 18 0 0 1-3.2 4.2M6.6 6.6C3.7 8.5 2 12 2 12s3.6 7 10 7a9.8 9.8 0 0 0 5.4-1.6"/>
                        <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                        <path d="M3 3l18 18"/>
                    </svg>
                </button>
            </div>
            <p class="form__error" data-error-for="password"></p>
        </div>

        <button type="submit" class="btn btn--primary btn--block">Login</button>
    </form>
</main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/login.js') }}"></script>
@endpush
