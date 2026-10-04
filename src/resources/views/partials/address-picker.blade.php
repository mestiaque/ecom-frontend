{{--
    Checkout address choice for $type ("shipping" / "billing"): saved addresses of the customer
    as radio cards plus "new address", which opens the typed form (address-fields).
--}}
@php
    $selected = old("{$type}_address_id", $saved->firstWhere('is_default', true)?->id ?? $saved->first()?->id ?? 'new');
@endphp
<div data-address-picker>
    @if($saved->isNotEmpty())
        <div class="ef-address-grid mb-3">
            @foreach($saved as $savedAddress)
                <label class="ef-address-option">
                    <input type="radio" name="{{ $type }}_address_id" value="{{ $savedAddress->id }}" @checked((string) $selected === (string) $savedAddress->id)>
                    <span class="ef-address-body">@include('efront::partials.address-card', ['address' => $savedAddress])</span>
                    <i class="fas fa-check-circle ef-pay-check"></i>
                </label>
            @endforeach
            <label class="ef-address-option ef-address-new">
                <input type="radio" name="{{ $type }}_address_id" value="new" @checked($selected === 'new')>
                <span class="ef-address-body"><i class="fas fa-plus-circle me-2"></i>Use a new address</span>
            </label>
        </div>
        @error("{$type}_address_id")<div class="ef-error mb-2">{{ $message }}</div>@enderror
    @else
        <input type="hidden" name="{{ $type }}_address_id" value="new">
    @endif

    <div data-address-new @if($saved->isNotEmpty() && $selected !== 'new') hidden @endif>
        @include('efront::partials.address-fields', ['prefix' => $type, 'address' => $saved->isEmpty() && $type === 'shipping' && $customer ? new \ME\Efront\Models\Address(['name' => $customer->name, 'phone' => $customer->phone]) : null])
        @if($customer)
            <label class="ef-check mt-3">
                <input type="checkbox" name="save_{{ $type }}" value="1" @checked(old("save_{$type}", true))>
                <span>Save this address to my address book</span>
            </label>
        @endif
    </div>
</div>
