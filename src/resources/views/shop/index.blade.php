@extends('efront::layouts.app')

@section('title', $title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($description ?? ''), 160) ?: $title)

@section('content')
    @php
        $crumbs = [['label' => 'Shop', 'url' => isset($category) || isset($brand) || isset($campaign) || request('q') ? route('efront.shop') : null]];
        if (isset($category) && $category) {
            $crumbs[] = ['label' => $category->name, 'url' => null];
        } elseif (isset($brand) || isset($campaign) || request('q')) {
            $crumbs[] = ['label' => $title, 'url' => null];
        }
        if (count($crumbs) === 1) {
            $crumbs[0]['url'] = null;
        }
    @endphp
    <x-efront::page-header :title="$title" :breadcrumbs="$crumbs" :image="$banner ?? null" :subtitle="isset($campaign) ? 'Offer ends '.$campaign->ends_at->format('d M Y, h:i A') : null" />

    <section class="ef-shop">
        <div class="container">
            @if(isset($campaign) && ! $campaign->isRunning())
                <div class="alert alert-warning">
                    {{ $campaign->starts_at->isFuture() ? 'This offer starts on '.$campaign->starts_at->format('d M Y, h:i A').'.' : 'This offer has ended — regular prices apply.' }}
                </div>
            @endif
            <div class="row g-4">
                <div class="col-lg-3">
                    @include('efront::shop.partials.filters')
                </div>
                <div class="col-lg-9">
                    <div id="shopResults">
                        @include('efront::shop.partials.results')
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
