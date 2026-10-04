@props(['icon' => 'fas fa-box-open', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'ef-empty']) }}>
    <div class="ef-empty-icon"><i class="{{ $icon }}"></i></div>
    <h5>{{ $title }}</h5>
    @if($text)
        <p>{{ $text }}</p>
    @endif
    {{ $slot }}
</div>
