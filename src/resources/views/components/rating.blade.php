@props(['rating' => 0, 'count' => null, 'size' => null])
@php($value = round((float) $rating * 2) / 2)
<span {{ $attributes->merge(['class' => 'mstars ef-stars']) }} title="{{ number_format((float) $rating, 1) }} out of 5" @if($size) style="font-size:{{ $size }}" @endif>
    @for($i = 1; $i <= 5; $i++)
        @if($value >= $i)
            <i class="fas fa-star"></i>
        @elseif($value >= $i - 0.5)
            <i class="fas fa-star-half-alt"></i>
        @else
            <i class="far fa-star"></i>
        @endif
    @endfor
    @if(! is_null($count))
        <span class="ef-stars-count">({{ $count }})</span>
    @endif
</span>
