@extends('efront::account.layout')

@section('title', 'Profile')
@section('account_title', 'Profile & Password')

@section('account')
    <div class="fcard mb-4">
        <h5 class="ef-summary-title">Profile Photo</h5>
        <div class="ef-avatar-edit">
            <x-efront::avatar :customer="$customer" size="xl" data-avatar-preview />
            <div>
                <form method="POST" action="{{ route('efront.account.avatar.update') }}" enctype="multipart/form-data" data-avatar-form>
                    @csrf
                    <label class="btn-red ef-btn-file">
                        <i class="fas fa-camera"></i>{{ $customer->avatar ? 'Change photo' : 'Upload photo' }}
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-avatar-input hidden>
                    </label>
                </form>
                @if($customer->avatar)
                    <form method="POST" action="{{ route('efront.account.avatar.remove') }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ef-link-btn text-danger mt-2">Remove photo</button>
                    </form>
                @endif
                <p class="small text-muted mb-0 mt-2">JPG, PNG or WebP, up to {{ round(config('efront.avatar_max_kb', 2048) / 1024, 1) }} MB.</p>
                @error('avatar')<div class="ef-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="fcard mb-4">
        <h5 class="ef-summary-title">Personal Information</h5>
        <form method="POST" action="{{ route('efront.account.profile.update') }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-12">
                    <label class="flbl" for="name">Full name *</label>
                    <input type="text" id="name" name="name" class="fctrl" value="{{ old('name', $customer->name) }}" required>
                    @error('name')<div class="ef-error">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="flbl" for="phone">
                        Mobile number *
                        @if($customer->phone_verified_at)<span class="ef-verified"><i class="fas fa-check-circle"></i> Verified</span>@endif
                    </label>
                    <input type="tel" id="phone" name="phone" class="fctrl" value="{{ old('phone', $customer->phone) }}" required>
                    @error('phone')<div class="ef-error">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="flbl" for="email">
                        Email
                        @if($customer->email_verified_at)<span class="ef-verified"><i class="fas fa-check-circle"></i> Verified</span>@endif
                    </label>
                    <input type="email" id="email" name="email" class="fctrl" value="{{ old('email', $customer->email) }}">
                    @error('email')<div class="ef-error">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                    <button class="btn-red"><i class="fas fa-save"></i>Save Changes</button>
                    <a href="{{ route('efront.account.addresses') }}" class="ef-link-more"><i class="fas fa-map-marker-alt me-1"></i>Manage addresses</a>
                </div>
            </div>
        </form>
    </div>

    <div class="fcard">
        <h5 class="ef-summary-title">Change Password</h5>
        <form method="POST" action="{{ route('efront.account.password.change') }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                @foreach([['current_password', 'Current password', 'current-password'], ['password', 'New password', 'new-password'], ['password_confirmation', 'Confirm new password', 'new-password']] as [$field, $label, $autocomplete])
                    <div class="col-md-4">
                        <label class="flbl" for="pw_{{ $field }}">{{ $label }}</label>
                        <div class="ef-password">
                            <input type="password" id="pw_{{ $field }}" name="{{ $field }}" class="fctrl" autocomplete="{{ $autocomplete }}" required>
                            <button type="button" data-toggle-password aria-label="Show password"><i class="far fa-eye"></i></button>
                        </div>
                        @error($field)<div class="ef-error">{{ $message }}</div>@enderror
                    </div>
                @endforeach
                <div class="col-12"><button class="btn-red"><i class="fas fa-key"></i>Update Password</button></div>
            </div>
        </form>
    </div>
@endsection
