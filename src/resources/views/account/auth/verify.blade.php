@extends('efront::account.auth.layout')

@section('title', 'Verify Your Account')
@section('auth_icon', 'fas fa-shield-alt')
@section('auth_title', 'Verify your account')
@section('auth_subtitle', 'We sent a 6-digit code to confirm it is really you.')

@section('auth_form')
    @php
        $maskedPhone = substr($data['phone'], 0, 3).str_repeat('•', max(0, strlen($data['phone']) - 6)).substr($data['phone'], -3);
        [$local, $domain] = array_pad(explode('@', (string) $data['email'], 2), 2, '');
        $maskedEmail = $data['email'] ? mb_substr($local, 0, 2).str_repeat('•', max(1, mb_strlen($local) - 2)).'@'.$domain : null;
    @endphp
    <form method="POST" action="{{ route('efront.account.register.verify.store') }}">
        @csrf
        @error('code')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror

        @if(in_array('phone', $channels, true))
            <div class="mb-3">
                <label class="flbl" for="phone_code"><i class="fas fa-sms me-1 text-primary-ef"></i>SMS code sent to {{ $maskedPhone }}</label>
                <input type="text" id="phone_code" name="phone_code" class="fctrl ef-otp-input @error('phone_code') is-invalid @enderror" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="••••••" required autofocus>
                @error('phone_code')<div class="ef-error">{{ $message }}</div>@enderror
            </div>
        @endif

        @if(in_array('email', $channels, true))
            <div class="mb-3">
                <label class="flbl" for="email_code"><i class="fas fa-envelope me-1 text-primary-ef"></i>Email code sent to {{ $maskedEmail }}</label>
                <input type="text" id="email_code" name="email_code" class="fctrl ef-otp-input @error('email_code') is-invalid @enderror" inputmode="numeric" maxlength="6" placeholder="••••••" required>
                @error('email_code')<div class="ef-error">{{ $message }}</div>@enderror
            </div>
        @endif

        <button class="btn-red w-100 justify-content-center mt-2"><i class="fas fa-check"></i>Verify &amp; Create Account</button>
    </form>

    <form method="POST" action="{{ route('efront.account.register.resend') }}" class="text-center mt-4">
        @csrf
        <span class="text-muted small">Did not get the code?</span>
        <button type="submit" class="ef-link-btn fw-semibold" data-resend-in="{{ $resendIn }}" @disabled($resendIn > 0)>Send again</button>
        <span class="small text-muted" data-resend-timer></span>
    </form>
    <p class="text-center mt-3 mb-0 small"><a href="{{ route('efront.account.register') }}"><i class="fas fa-arrow-left me-1"></i>Change phone or email</a></p>
@endsection
