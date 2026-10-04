@extends('efront::layouts.app')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->content), 160))

@section('content')
    <x-efront::page-header :title="$page->title" :breadcrumbs="[['label' => $page->title, 'url' => null]]" />

    <section class="ef-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="fcard ef-richtext">
                        {!! \ME\Efront\Support\Html::clean($page->content) !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
