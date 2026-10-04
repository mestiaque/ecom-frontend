@props(['customer', 'size' => null])
{{-- Customer photo, or the first letter of the name when there is none. --}}
@if($customer->avatar_url)
    <img src="{{ $customer->avatar_url }}" alt="{{ $customer->name }}" {{ $attributes->merge(['class' => 'ef-avatar ef-avatar-img'.($size ? ' ef-avatar-'.$size : '')]) }}>
@else
    <span {{ $attributes->merge(['class' => 'ef-avatar'.($size ? ' ef-avatar-'.$size : '')]) }}>{{ $customer->initial }}</span>
@endif
