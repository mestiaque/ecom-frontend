@extends('efront::account.layout')

@section('title', $address->exists ? 'Edit Address' : 'New Address')
@section('account_title', $address->exists ? 'Edit Address' : 'New Address')

@section('account')
    <div class="fcard">
        <form method="POST" action="{{ $address->exists ? route('efront.account.addresses.update', $address) : route('efront.account.addresses.store') }}">
            @csrf
            @if($address->exists)
                @method('PUT')
            @endif
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="flbl">Address type *</label>
                    <div class="d-flex gap-2">
                        @foreach(\ME\Efront\Models\Address::TYPES as $type => $label)
                            <label class="ef-radio flex-fill">
                                <input type="radio" name="type" value="{{ $type }}" @checked(old('type', $address->type) === $type)>
                                <span class="ef-radio-body"><strong>{{ $label }}</strong></span>
                            </label>
                        @endforeach
                    </div>
                    @error('type')<div class="ef-error">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="flbl" for="label">Label <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" id="label" name="label" class="fctrl" value="{{ old('label', $address->label) }}" placeholder="Home, Office..." list="addressLabels">
                    <datalist id="addressLabels"><option value="Home"><option value="Office"><option value="Parents"></datalist>
                </div>
            </div>

            @include('efront::partials.address-fields', ['address' => $address])

            <label class="ef-check mt-3">
                <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default))>
                <span>Use as my default address of this type</span>
            </label>

            <div class="d-flex gap-2 mt-4">
                <button class="btn-red"><i class="fas fa-save"></i>Save Address</button>
                <a href="{{ route('efront.account.addresses') }}" class="ef-btn-outline">Cancel</a>
            </div>
        </form>
    </div>
@endsection
