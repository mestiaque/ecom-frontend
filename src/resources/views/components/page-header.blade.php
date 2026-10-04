@props(['title', 'breadcrumbs' => [], 'image' => null, 'subtitle' => null])
<section class="ef-pagehead" @if($image) style="--ef-head-img:url('{{ $image }}')" @endif>
    <div class="container position-relative">
        <h1 class="ef-pagehead-title">{{ $title }}</h1>
        @if($subtitle)
            <p class="ef-pagehead-sub">{{ $subtitle }}</p>
        @endif
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb ef-breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('efront.home') }}"><i class="fas fa-home"></i></a></li>
                @foreach($breadcrumbs as $crumb)
                    @if($crumb['url'] ?? null)
                        <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
                    @else
                        <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($crumb['label'], 50) }}</li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>
</section>
