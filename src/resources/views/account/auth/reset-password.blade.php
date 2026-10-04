@extends('efront::account.auth.layout')

@section('title', 'Reset Password')
@section('auth_icon', 'fas fa-lock')
@section('auth_title', 'Set a new password')
@section('auth_subtitle', 'Choose a password with at least 8 characters.')

@section('auth_form')
    <form method="POST" action="{{ route('efront.account.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
            <label class="flbl" for="email">Email</label>
            <input type="email" id="email" name="email" class="fctrl" value="{{ old('email', $email) }}" autocomplete="email" required>
            @error('email')<div class="ef-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="flbl" for="password">New password</label>
            <div class="ef-password">
                <input type="password" id="password" name="password" class="fctrl" autocomplete="new-password" required autofocus>
                <button type="button" data-toggle-password aria-label="Show password"><i class="far fa-eye"></i></button>
            </div>
            @error('password')<div class="ef-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="flbl" for="password_confirmation">Confirm new password</label>
            <div class="ef-password">
                    <input type="password" id="password_confirmation" name="password_confirmation" class="fctrl" autocomplete="new-password" required>
                    <button type="button" data-toggle-password aria-label="Show password"><i class="far fa-eye"></i></button>
                </div>
        </div>
        <button class="btn-red w-100 justify-content-center"><i class="fas fa-check"></i>Reset Password</button>
    </form>
@endsection
