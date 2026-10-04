@extends('efront::layouts.app')

@section('title', 'FAQ')
@section('meta_description', 'Answers to common questions about orders, payment, delivery and returns at '.efront()->storeName().'.')

@section('content')
    <x-efront::page-header title="Frequently Asked Questions" :breadcrumbs="[['label' => 'FAQ', 'url' => null]]" />

    <section class="ef-page ef-faq">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    @if($groups->isEmpty())
                        <div class="fcard">
                            <x-efront::empty icon="far fa-question-circle" title="No questions yet" text="Have a question? We are happy to help.">
                                <a href="{{ route('efront.contact') }}" class="btn-red mt-2"><i class="fas fa-paper-plane"></i>Contact Us</a>
                            </x-efront::empty>
                        </div>
                    @else
                        <div class="ef-faq-search mb-4">
                            <i class="fas fa-search"></i>
                            <input type="search" class="fctrl" placeholder="Search questions..." data-faq-search aria-label="Search questions">
                        </div>

                        @if($groups->count() > 1)
                            <div class="ef-faq-tabs mb-4">
                                <button type="button" class="filtbtn active" data-faq-filter="all">All</button>
                                @foreach($groups->keys() as $category)
                                    <button type="button" class="filtbtn" data-faq-filter="{{ \Illuminate\Support\Str::slug($category) }}">{{ $category }}</button>
                                @endforeach
                            </div>
                        @endif

                        @foreach($groups as $category => $faqs)
                            <div class="ef-faq-group" data-faq-group="{{ \Illuminate\Support\Str::slug($category) }}">
                                @if($groups->count() > 1)
                                    <h5 class="ef-faq-title">{{ $category }}</h5>
                                @endif
                                <div class="accordion ef-accordion" id="faq-{{ \Illuminate\Support\Str::slug($category) }}">
                                    @foreach($faqs as $faq)
                                        <div class="accordion-item" data-faq-item>
                                            <h3 class="accordion-header">
                                                <button class="accordion-button @unless($loop->parent->first && $loop->first) collapsed @endunless" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $faq->id }}" aria-expanded="{{ $loop->parent->first && $loop->first ? 'true' : 'false' }}">
                                                    {{ $faq->question }}
                                                </button>
                                            </h3>
                                            <div id="faq{{ $faq->id }}" class="accordion-collapse collapse @if($loop->parent->first && $loop->first) show @endif">
                                                <div class="accordion-body ef-richtext">{!! \ME\Efront\Support\Html::clean($faq->answer) !!}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <x-efront::empty icon="fas fa-search" title="No matching questions" text="Try other words, or ask us directly." class="d-none" data-faq-empty />
                    @endif

                    <div class="ef-faq-cta mt-5">
                        <div>
                            <h5 class="mb-1">Still have a question?</h5>
                            <p class="mb-0 text-muted">Our support team replies {{ efront()->setting('store_hotline_hours') ? '('.efront()->setting('store_hotline_hours').')' : 'quickly' }}.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @if($phone = efront()->setting('store_phone'))
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="ef-btn-outline"><i class="fas fa-phone-alt"></i>{{ $phone }}</a>
                            @endif
                            <a href="{{ route('efront.contact') }}" class="btn-red"><i class="fas fa-paper-plane"></i>Contact Us</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
