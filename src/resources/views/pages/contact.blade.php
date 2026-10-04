@extends('efront::layouts.app')

@section('title', 'Contact Us')

@section('content')
    <x-efront::page-header title="Contact Us" :breadcrumbs="[['label' => 'Contact', 'url' => null]]" />

    <section id="contact-section">
        <div class="container">
            <x-efront::section-title label="Get In Touch" title="We'd love to" highlight="hear from you" text="Questions about an order, a product or anything else? Send us a message." />
            <div class="row g-4">
                <div class="col-lg-4" data-aos="fade-right">
                    <div class="ctdark">
                        <h4>Let's Talk</h4>
                        <p class="ctsub">We usually reply within a few hours during support hours.</p>
                        @foreach([
                            ['fas fa-map-marker-alt', 'Address', efront()->setting('store_address')],
                            ['fas fa-phone-alt', 'Phone', efront()->setting('store_phone')],
                            ['fas fa-envelope', 'Email', efront()->setting('store_email')],
                            ['fas fa-clock', 'Support Hours', efront()->setting('store_hotline_hours')],
                        ] as [$icon, $label, $value])
                            @if($value)
                                <div class="ctitem">
                                    <div class="cticon"><i class="{{ $icon }}"></i></div>
                                    <div class="ctinfo"><strong>{{ $label }}</strong><span>{{ $value }}</span></div>
                                </div>
                            @endif
                        @endforeach
                        <div class="ctsocrow">
                            @foreach(efront()->socialLinks() as $icon => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener"><i class="{{ $icon }}"></i></a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-lg-8" data-aos="fade-left">
                    <form class="fcard" method="POST" action="{{ route('efront.contact.send') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="flbl" for="name">Your Name *</label>
                                <input type="text" id="name" name="name" class="fctrl" value="{{ old('name', efront()->customer()?->name) }}" required>
                                @error('name')<div class="ef-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="flbl" for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="fctrl" value="{{ old('email', efront()->customer()?->email) }}" required>
                                @error('email')<div class="ef-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="flbl" for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="fctrl" value="{{ old('phone', efront()->customer()?->phone) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="flbl" for="subject">Subject *</label>
                                <select id="subject" name="subject" class="fctrl">
                                    @foreach(['General Inquiry', 'Order Support', 'Returns & Refunds', 'Product Question', 'Partnership'] as $subject)
                                        <option @selected(old('subject') === $subject)>{{ $subject }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="flbl" for="message">Message *</label>
                                <textarea id="message" name="message" rows="5" class="fctrl" placeholder="Write your message here..." required>{{ old('message') }}</textarea>
                                @error('message')<div class="ef-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12"><button class="btn-red"><i class="fas fa-paper-plane"></i>Send Message</button></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
