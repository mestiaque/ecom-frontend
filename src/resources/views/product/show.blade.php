@extends('efront::layouts.app')

@section('title', $product->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->description), 160))

@section('content')
    <x-efront::page-header :title="$product->category->name ?? 'Product'" :breadcrumbs="$breadcrumbs" />

    <section class="ef-product">
        <div class="container">
            <div class="row g-5">
                {{-- Gallery --}}
                <div class="col-lg-6">
                    @php($images = $product->images)
                    <div class="ef-gallery" data-gallery>
                        <a href="{{ $images->first()?->url ?? asset('efront/img/banner-img.jpg') }}" class="ef-gallery-main" data-gallery-main>
                            <img src="{{ $images->first()?->url ?? asset('efront/img/banner-img.jpg') }}" alt="{{ $product->title }}" data-gallery-image>
                            <x-efront::product-badge :product="$product" />
                            <span class="ef-zoom"><i class="fas fa-expand-alt"></i></span>
                        </a>
                        @if($images->count() > 1)
                            <div class="ef-thumbs">
                                @foreach($images as $image)
                                    <button type="button" class="ef-thumb @if($loop->first) active @endif" data-gallery-thumb="{{ $image->url }}">
                                        <img src="{{ $image->thumb_url }}" alt="" loading="lazy">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Info --}}
                <div class="col-lg-6">
                    <div class="ef-product-info">
                        @if($product->category)
                            <a href="{{ route('efront.category', $product->category) }}" class="mcat d-inline-block">{{ $product->category->name }}</a>
                        @endif
                        <h1 class="ef-product-title">{{ $product->title }}</h1>
                        <div class="d-flex align-items-center flex-wrap gap-3 mb-3">
                            <x-efront::rating :rating="$product->rating" />
                            <a href="#reviews" class="ef-link-muted" data-tab-link="reviews">{{ $product->reviews_count }} {{ \Illuminate\Support\Str::plural('review', $product->reviews_count) }}</a>
                            @if($product->sku)
                                <span class="ef-link-muted">SKU: <span data-sku>{{ $product->sku }}</span></span>
                            @endif
                        </div>
                        @if($product->short_description)
                            <p class="ef-product-short">{{ $product->short_description }}</p>
                        @endif

                        @include('efront::product.partials.purchase')

                        <ul class="ef-meta">
                            @if($product->brand)
                                <li><span>Brand</span><a href="{{ route('efront.brand', $product->brand) }}">{{ $product->brand->name }}</a></li>
                            @endif
                            @if($product->warranty)
                                <li><span>Warranty</span>{{ $product->warranty->name }}</li>
                            @endif
                            <li>
                                <span>Delivery</span>
                                @if($product->free_delivery)
                                    <strong class="text-success"><i class="fas fa-truck me-1"></i>Free delivery</strong>
                                @elseif((float) $product->delivery_charge_adjustment > 0)
                                    Zone charge + {{ ecom_money($product->delivery_charge_adjustment) }} per item
                                @elseif((float) $product->delivery_charge_adjustment < 0)
                                    Zone charge − {{ ecom_money(abs((float) $product->delivery_charge_adjustment)) }} per item
                                @else
                                    Cash on delivery available
                                @endif
                                @if(! $product->free_delivery && ! is_null($freeMin = efront()->freeShippingMin()))
                                    <small class="text-muted d-block w-100 ps-0">Free delivery on orders over {{ ecom_money($freeMin) }}</small>
                                @endif
                            </li>
                            <li>
                                <span>Share</span>
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="ef-share"><i class="fab fa-facebook-f"></i></a>
                                <a href="https://wa.me/?text={{ urlencode($product->title.' '.url()->current()) }}" target="_blank" rel="noopener" class="ef-share"><i class="fab fa-whatsapp"></i></a>
                                <button type="button" class="ef-share" data-copy="{{ url()->current() }}" title="Copy link"><i class="fas fa-link"></i></button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="ef-tabs mt-5" id="reviews">
                <ul class="nav ef-tab-nav" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabDescription" type="button" role="tab">Description</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabReviews" type="button" role="tab" data-tab="reviews">Reviews ({{ $reviews->count() }})</button>
                    </li>
                </ul>
                <div class="tab-content ef-tab-body">
                    <div class="tab-pane fade show active" id="tabDescription" role="tabpanel">
                        @if($product->description)
                            <div class="ef-richtext">{!! \ME\Efront\Support\Html::clean($product->description) !!}</div>
                        @else
                            <p class="text-muted mb-0">No description yet.</p>
                        @endif
                    </div>
                    <div class="tab-pane fade" id="tabReviews" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="ef-rating-summary">
                                    <div class="ef-rating-big">{{ number_format((float) $product->rating, 1) }}</div>
                                    <x-efront::rating :rating="$product->rating" />
                                    <div class="text-muted small mt-1">{{ $reviews->count() }} {{ \Illuminate\Support\Str::plural('review', $reviews->count()) }}</div>
                                    <div class="mt-3">
                                        @foreach($ratingBreakdown as $star => $count)
                                            <div class="ef-bar-row">
                                                <span>{{ $star }} <i class="fas fa-star"></i></span>
                                                <div class="ef-bar"><div style="width:{{ $reviews->count() ? round($count / $reviews->count() * 100) : 0 }}%"></div></div>
                                                <span>{{ $count }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                @forelse($reviews as $review)
                                    <div class="ef-review">
                                        <span class="ef-avatar">{{ mb_strtoupper(mb_substr($review->name, 0, 1)) }}</span>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                                <strong>{{ $review->name }}</strong>
                                                <small class="text-muted">{{ $review->created_at->format('d M Y') }}</small>
                                            </div>
                                            <x-efront::rating :rating="$review->rating" />
                                            @if($review->comment)
                                                <p class="mb-0 mt-1">{{ $review->comment }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted">No reviews yet. Be the first to review this product.</p>
                                @endforelse

                                <div class="ef-review-form mt-4">
                                    @if($canReview)
                                        <h5 class="mb-3">Write a review</h5>
                                        <form method="POST" action="{{ route('efront.reviews.store', $product) }}">
                                            @csrf
                                            <label class="flbl">Your rating *</label>
                                            <div class="ef-star-input mb-3">
                                                @for($i = 5; $i >= 1; $i--)
                                                    <input type="radio" name="rating" id="rating{{ $i }}" value="{{ $i }}" @checked((int) old('rating', 5) === $i)>
                                                    <label for="rating{{ $i }}" title="{{ $i }} stars"><i class="fas fa-star"></i></label>
                                                @endfor
                                            </div>
                                            @error('rating')<div class="ef-error mb-2">{{ $message }}</div>@enderror
                                            <label class="flbl" for="comment">Your review *</label>
                                            <textarea name="comment" id="comment" rows="4" class="fctrl" placeholder="What did you like or dislike?" required>{{ old('comment') }}</textarea>
                                            @error('comment')<div class="ef-error">{{ $message }}</div>@enderror
                                            <button class="btn-red mt-3"><i class="fas fa-paper-plane"></i>Submit Review</button>
                                        </form>
                                    @elseif(! efront()->customer())
                                        <p class="mb-0"><a href="{{ route('efront.account.login') }}" class="fw-semibold">Log in</a> to write a review.</p>
                                    @else
                                        <p class="mb-0 text-muted"><i class="fas fa-check-circle text-success me-1"></i>You have already reviewed this product.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($related->isNotEmpty())
        <section class="ef-related pt-0">
            <div class="container">
                <x-efront::section-title label="You May Also Like" title="Related" highlight="Products" />
                <div class="row g-4">
                    @foreach($related as $relatedProduct)
                        <div class="col-6 col-lg-3"><x-efront::product-card :product="$relatedProduct" class="h-100" /></div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
