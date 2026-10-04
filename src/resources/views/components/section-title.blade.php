@props(['label' => null, 'title', 'highlight' => null, 'text' => null, 'align' => 'center'])
<div {{ $attributes->merge(['class' => ($align === 'center' ? 'text-center' : '').' mb-5']) }} data-aos="fade-up">
    @if($label)
        <span class="slbl">{{ $label }}</span>
    @endif
    <h2 class="stitle {{ $align === 'center' ? '' : 'text-start' }}">{{ $title }} @if($highlight)<span>{{ $highlight }}</span>@endif</h2>
    <div class="sline {{ $align === 'center' ? '' : 'lft' }}"></div>
    @if($text)
        <p class="sdesc {{ $align === 'center' ? 'mx-auto' : '' }}" style="max-width:520px;">{{ $text }}</p>
    @endif
    {{ $slot }}
</div>
