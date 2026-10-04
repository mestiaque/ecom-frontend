@extends('efront::account.auth.layout')

@section('title', 'Create Account')
@section('auth_icon', 'fas fa-user-plus')
@section('auth_title', 'Create your account')
@section('auth_subtitle', 'It takes less than a minute.')

@section('auth_form')
    <form method="POST" action="{{ route('efront.account.register.store') }}">
        @csrf
        <div class="mb-3">
            <label class="flbl" for="name">Full name *</label>
            <input type="text" id="name" name="name" class="fctrl" value="{{ old('name') }}" autocomplete="name" required autofocus>
            @error('name')<div class="ef-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="flbl" for="phone">Mobile number *</label>
            <input type="tel" id="phone" name="phone" class="fctrl" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" autocomplete="tel" required>
            @error('phone')<div class="ef-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="flbl" for="email">Email @if(config('efront.registration_otp.email'))*@else<span class="text-muted fw-normal">(needed to reset your password)</span>@endif</label>
            <input type="email" id="email" name="email" class="fctrl" value="{{ old('email') }}" autocomplete="email" @required(config('efront.registration_otp.email'))>
            @error('email')<div class="ef-error">{{ $message }}</div>@enderror
        </div>
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <label class="flbl" for="password">Password *</label>
                <div class="ef-password">
                    <input type="password" id="password" name="password" class="fctrl" autocomplete="new-password" required>
                    <button type="button" data-toggle-password aria-label="Show password"><i class="far fa-eye"></i></button>
                </div>
            </div>
            <div class="col-sm-6">
                <label class="flbl" for="password_confirmation">Confirm password *</label>
                <div class="ef-password">
                    <input type="password" id="password_confirmation" name="password_confirmation" class="fctrl" autocomplete="new-password" required>
                    <button type="button" data-toggle-password aria-label="Show password"><i class="far fa-eye"></i></button>
                </div>
            </div>
            @error('password')<div class="col-12"><div class="ef-error">{{ $message }}</div></div>@enderror
        </div>
        @if(config('efront.registration_otp.phone') || config('efront.registration_otp.email'))
            <p class="small text-muted mb-3"><i class="fas fa-shield-alt me-1"></i>We will send a verification code to your {{ collect([config('efront.registration_otp.phone') ? 'phone' : null, config('efront.registration_otp.email') ? 'email' : null])->filter()->implode(' and ') }}.</p>
        @endif
        <button class="btn-red w-100 justify-content-center"><i class="fas fa-user-plus"></i>Continue</button>
        <p class="text-center mt-4 mb-0">Already have an account? <a href="{{ route('efront.account.login') }}" class="fw-semibold">Log in</a></p>
    </form>
@endsection
