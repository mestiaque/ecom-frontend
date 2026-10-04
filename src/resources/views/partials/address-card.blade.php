{{-- One saved address (address book, checkout picker). --}}
<span class="ef-address-head">
    <strong>{{ $address->name }}</strong>
    @if($address->label)<span class="ef-address-label">{{ $address->label }}</span>@endif
    @if($address->is_default)<span class="ef-address-default">Default</span>@endif
</span>
<span class="ef-address-line"><i class="fas fa-phone-alt"></i>{{ $address->phone }}</span>
<span class="ef-address-line"><i class="fas fa-map-marker-alt"></i>{{ $address->full }}</span>
