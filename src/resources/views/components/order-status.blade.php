@props(['status'])
<span {{ $attributes->merge(['class' => 'badge rounded-pill text-bg-'.$status->color().' ef-status']) }}>
    <i class="{{ $status->icon() }} me-1"></i>{{ $status->label() }}
</span>
