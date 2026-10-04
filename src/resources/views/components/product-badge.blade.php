@props(['product'])
@php
    $final = $product->finalPrice();
    $regular = (float) $product->price;
    $off = $regular > 0 && $final < $regular ? (int) round(($regular - $final) / $regular * 100) : 0;
@endphp
@if($product->stock <= 0)
    <div class="mbdg ef-bdg-out">Out of stock</div>
@elseif($product->activeCampaign())
    <div class="mbdg"><i class="fas fa-bolt"></i> {{ $off ? "-{$off}%" : 'Deal' }}</div>
@elseif($off)
    <div class="mbdg hot">-{{ $off }}%</div>
@elseif($product->created_at && $product->created_at->gt(now()->subDays((int) config('efront.new_badge_days', 14))))
    <div class="mbdg new"><i class="fas fa-star"></i> New</div>
@elseif($product->is_featured)
    <div class="mbdg hot"><i class="fas fa-fire"></i> Hot</div>
@endif
