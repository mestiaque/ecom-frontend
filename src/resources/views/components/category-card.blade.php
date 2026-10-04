@props(['category', 'active' => false])
<a href="{{ route('efront.category', $category) }}" {{ $attributes->merge(['class' => 'catcard d-block'.($active ? ' active' : '')]) }}>
    @if($category->image)
        <img class="catimg" src="{{ ecom_image($category->image) }}" alt="{{ $category->name }}" loading="lazy">
    @else
        <span class="catimg ef-catimg-icon"><i class="fas fa-tags"></i></span>
    @endif
    <div class="catnm">{{ $category->name }}</div>
    @isset($category->products_count)
        <div class="catct">{{ $category->products_count }} {{ \Illuminate\Support\Str::plural('item', $category->products_count) }}</div>
    @endisset
</a>
