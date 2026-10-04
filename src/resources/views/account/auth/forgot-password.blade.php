@extends('efront::account.auth.layout')

@section('title', 'Forgot Password')
@section('auth_icon', 'fas fa-key')
@section('auth_title', 'Forgot your password?')
@section('auth_subtitle', 'Enter the email on your account and we will send you a reset link.')

@section('auth_form')
    <form method="POST" action="{{ route('efront.account.password.email') }}">
        @csrf
        <div class="mb-4">
            <label class="flbl" for="email">Email</label>
            <input type="email" id="email" name="email" class="fctrl" value="{{ old('email') }}" autocomplete="email" required autofocus>
            @error('email')<div class="ef-error">{{ $message }}</div>@enderror
        </div>
        <button class="btn-red w-100 justify-content-center"><i class="fas fa-paper-plane"></i>Send Reset Link</button>
        <p class="small text-muted text-center mt-3 mb-0">No email on your account? Please <a href="{{ route('efront.contact') }}">contact us</a> to reset it.</p>
        <p class="text-center mt-3 mb-0"><a href="{{ route('efront.account.login') }}"><i class="fas fa-arrow-left me-1"></i>Back to login</a></p>
    </form>
@endsection
