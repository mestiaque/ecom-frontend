@extends('efront::account.auth.layout')

@section('title', 'Login')
@section('auth_icon', 'fas fa-sign-in-alt')
@section('auth_title', 'Welcome back')
@section('auth_subtitle', 'Log in to see your orders and wishlist.')

@section('auth_form')
    <form method="POST" action="{{ route('efront.account.login.store') }}">
        @csrf
        <div class="mb-3">
            <label class="flbl" for="login">Mobile number or email</label>
            <input type="text" id="login" name="login" class="fctrl" value="{{ old('login') }}" placeholder="01XXXXXXXXX" autocomplete="username" required autofocus>
            @error('login')<div class="ef-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <label class="flbl" for="password">Password</label>
                <a href="{{ route('efront.account.password.forgot') }}" class="small">Forgot password?</a>
            </div>
            <div class="ef-password">
                <input type="password" id="password" name="password" class="fctrl" autocomplete="current-password" required>
                <button type="button" data-toggle-password aria-label="Show password"><i class="far fa-eye"></i></button>
            </div>
            @error('password')<div class="ef-error">{{ $message }}</div>@enderror
        </div>
        <label class="ef-check mb-4">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Keep me logged in</span>
        </label>
        <button class="btn-red w-100 justify-content-center"><i class="fas fa-sign-in-alt"></i>Log In</button>
        <p class="text-center mt-4 mb-0">New here? <a href="{{ route('efront.account.register') }}" class="fw-semibold">Create an account</a></p>
    </form>
@endsection
