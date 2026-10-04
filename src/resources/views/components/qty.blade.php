@props(['name' => 'quantity', 'value' => 1, 'max' => null])
<div {{ $attributes->merge(['class' => 'ef-qty']) }} data-qty>
    <button type="button" class="mpqbtn" data-qty-step="-1" aria-label="Decrease">-</button>
    <input type="number" name="{{ $name }}" value="{{ $value }}" min="1" @if($max) max="{{ $max }}" @endif class="mpqnum" inputmode="numeric" aria-label="Quantity">
    <button type="button" class="mpqbtn" data-qty-step="1" aria-label="Increase">+</button>
</div>
