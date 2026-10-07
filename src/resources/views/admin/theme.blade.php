@extends('me::master')
@section('title', 'Storefront Theme')

@php
    // Readability checks for the browser: "PAGE" = page background (depends on style), "topbar" only when the top bar is on
    $jsContrastChecks = collect(\ME\Efront\Support\Theme::contrastChecks(\ME\Efront\Support\Theme::merge(['header' => ['topbar' => true]])))
        ->reject(fn ($check) => str_contains($check[0], ' — '))
        ->map(fn ($check) => ['label' => $check[0], 'fg' => $check[1], 'bg' => $check[2] === 'glass.bg_1' ? 'PAGE' : $check[2], 'min' => $check[3], 'topbar' => str_starts_with($check[2], 'header.topbar')])
        ->values();
    $sectionNames = \ME\Efront\Support\Theme::SECTIONS;
    $buttonNames = \ME\Efront\Support\Theme::BUTTONS;
    $iconNames = ['search' => 'Search', 'wishlist' => 'Wishlist (header)', 'account' => 'Account', 'cart' => 'Cart', 'menu' => 'Mobile menu'];
    $fieldLabels = ['badge' => 'Badge', 'label' => 'Small label (above title)', 'title' => 'Title', 'highlight' => 'Highlighted word(s)', 'text' => 'Description', 'button' => 'Button text', 'limit' => 'How many to show'];
    $iconSuggestions = ['fas fa-shopping-cart', 'fas fa-cart-plus', 'fas fa-shopping-bag', 'fas fa-shopping-basket', 'fas fa-bolt', 'fas fa-plus', 'fas fa-eye', 'fas fa-lock', 'fas fa-check-circle', 'fas fa-credit-card', 'fas fa-arrow-right', 'fas fa-search', 'far fa-heart', 'fas fa-heart', 'far fa-user', 'fas fa-user', 'fas fa-user-circle', 'fas fa-bars', 'fas fa-store', 'fas fa-truck', 'fas fa-shipping-fast', 'fas fa-hand-holding-usd', 'fas fa-money-bill-wave', 'fas fa-undo', 'fas fa-headset', 'fas fa-phone-alt', 'fas fa-gift', 'fas fa-tag', 'fas fa-tags', 'fas fa-star', 'fas fa-shield-alt', 'fas fa-award', 'fas fa-leaf', 'fas fa-box', 'fas fa-percent', 'fas fa-fire', 'fas fa-rocket', 'fas fa-paper-plane'];
@endphp

@push('buttons')
    <button type="button" class="btn btn-sm btn-primary shadow-sm" id="previewToggle"><i class="fas fa-eye me-1"></i>Live Preview</button>
    <a href="{{ route('efront.home') }}" target="_blank" class="btn btn-sm btn-encodex-list text-white shadow-sm"><i class="fas fa-external-link-alt me-1"></i>View Store</a>
    {{-- Plain buttons, not a dropdown: metheme's title bar (overflow:auto) would clip a dropdown menu --}}
    <a download href="{{ route('efront.admin.theme.export') }}" class="no-loader btn btn-sm btn-outline-secondary shadow-sm" title="Download the published theme as a .json file"><i class="fas fa-download me-1"></i>Export</a>
    @if($hasDraft)
        <a download href="{{ route('efront.admin.theme.export', ['draft' => 1]) }}" class="no-loader btn btn-sm btn-outline-secondary shadow-sm" title="Download the unpublished draft"><i class="fas fa-download me-1"></i>Export Draft</a>
    @endif
    <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal" title="Load a theme .json file as a draft"><i class="fas fa-upload me-1"></i>Import</button>
    <button type="submit" form="efResetForm" class="btn btn-sm btn-outline-danger shadow-sm"><i class="fas fa-undo me-1"></i>Reset</button>
@endpush

@push('css')
<style>.ef-badge-sample{display:inline-block;border-radius:7px;padding:3px 11px;font-size:.72rem;font-weight:700}</style>
<style>
    .ef-theme-tabs .nav-link { font-weight: 600; font-size: .86rem; }
    .ef-color .form-control-color { max-width: 46px; padding: 3px; }
    .ef-section-card .card-header { cursor: default; background: transparent; }
    .ef-section-card.is-off { opacity: .55; }
    .ef-drag { cursor: grab; color: #adb5bd; }
    .ef-preview-btn { white-space: nowrap; display: inline-flex; align-items: center; gap: 8px; border: 0; padding: 10px 22px; font-weight: 600; font-size: .9rem; }
    .ef-preview-round { width: 38px; height: 38px; border-radius: 50%; border: 0; display: inline-flex; align-items: center; justify-content: center; }
    .ef-font-sample { font-size: 1.4rem; line-height: 1.3; }
    /* Live preview panel (right half of the screen) */
    #efPreview { position: fixed; top: 0; right: 0; bottom: 0; width: 50vw; z-index: 1040; display: none; flex-direction: column; background: #e9edf3; border-left: 1px solid rgba(0,0,0,.08); box-shadow: -10px 0 40px rgba(15,23,42,.15); }
    body.ef-previewing #efPreview { display: flex; }
    body.ef-previewing .app-main { margin-right: 50vw; }
    body.ef-previewing #themeForm .row > [class*="col-"] { flex: 0 0 100%; max-width: 100%; }
    #efPreview .ef-preview-bar { display: flex; align-items: center; gap: 6px; padding: 8px 10px; background: #fff; border-bottom: 1px solid #e5e7eb; }
    #efPreview .ef-preview-stage { flex: 1; overflow: auto; display: flex; justify-content: center; padding: 10px; }
    #efPreview iframe { border: 0; background: #fff; border-radius: 10px; box-shadow: 0 6px 24px rgba(0,0,0,.12); width: 100%; height: 100%; transition: width .25s; }
    #efPreview .ef-preview-status { font-size: .75rem; min-width: 110px; }
    @media (max-width: 991px) { #efPreview { width: 100vw; } body.ef-previewing .app-main { margin-right: 0; } }
    /* Sticky Save Draft / Publish bar */
    .ef-actionbar { position: sticky; bottom: 0; z-index: 20; margin-top: 16px; padding: 10px 14px; border-radius: 14px; background: rgba(255,255,255,.88); backdrop-filter: blur(12px); box-shadow: 0 -6px 24px rgba(15,23,42,.08); border: 1px solid rgba(0,0,0,.05); }
    .ef-preset { border-radius: 14px; overflow: hidden; transition: transform .2s, box-shadow .2s; }
    .ef-preset:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(15,23,42,.12); }
    .ef-preset-swatch { height: 86px; position: relative; display: flex; align-items: flex-end; gap: 6px; padding: 10px; }
    .ef-preset-swatch span { width: 26px; height: 26px; border-radius: 50%; border: 2px solid rgba(255,255,255,.9); box-shadow: 0 2px 6px rgba(0,0,0,.15); }
    .ef-preset-btn { position: absolute; right: 10px; top: 10px; border-radius: 50px; padding: 4px 12px; font-size: .75rem; font-weight: 600; border: 0; }
    .ef-preset.is-live { border: 2px solid #198754 !important; }
    .ef-preset.is-draft { border: 2px dashed #fd7e14 !important; }
    .ef-preset-state { position: absolute; left: 10px; top: 10px; border-radius: 50px; padding: 3px 10px; font-size: .7rem; font-weight: 700; color: #fff; }
    .ef-contrast-item.is-ok { display: none; }
</style>
@endpush

@section('content')
<form action="{{ route('efront.admin.theme.update') }}" method="POST" id="themeForm">
    @csrf
    @method('PUT')

    @if($errors->any())
        <div class="alert alert-danger small"><b>Please fix the highlighted fields.</b> {{ $errors->first() }}</div>
    @endif

    @if($hasDraft)
        <div class="alert alert-warning small d-flex flex-wrap align-items-center gap-2 py-2">
            <i class="fas fa-pen-nib"></i>
            <span><b>You are editing an unpublished draft</b> (saved {{ $draftSavedAt?->diffForHumans() }}). Customers still see the published theme.</span>
            <button type="submit" form="efDiscardForm" class="btn btn-sm btn-outline-danger ms-auto py-0">Discard draft</button>
        </div>
    @endif

    {{-- Readability check (updated live while editing) --}}
    <div class="alert alert-danger small py-2 {{ $contrastIssues ? '' : 'd-none' }}" id="contrastBox">
        <i class="fas fa-low-vision me-1"></i><b>Hard to read</b> — these text colours are too close to their background:
        <ul class="mb-0 mt-1" id="contrastList">
            @foreach($contrastIssues as $issue)
                <li>{{ $issue['label'] }} <span class="text-muted">(contrast {{ $issue['ratio'] }}:1, needs {{ $issue['min'] }}:1)</span></li>
            @endforeach
        </ul>
    </div>

    {{-- Last applied preset (live, and in the draft when it differs) --}}
    @if($livePreset || $editPreset)
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3 small">
            <span class="text-muted"><i class="fas fa-swatchbook me-1"></i>Preset:</span>
            @if($livePreset)
                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2">
                    <i class="fas fa-circle me-1" style="font-size:.5rem"></i>Live: {{ $livePreset['name'] }}@if($livePreset['customized']) <span class="fw-normal">· customized</span>@endif
                    @if($livePreset['applied_at'])<span class="fw-normal text-muted"> · applied {{ \Illuminate\Support\Carbon::parse($livePreset['applied_at'])->format('d M Y') }}</span>@endif
                </span>
            @else
                <span class="badge rounded-pill bg-light text-muted border px-3 py-2">Live: custom theme</span>
            @endif
            @if($hasDraft && $editPreset && ($editPreset['key'] !== ($livePreset['key'] ?? null) || $editPreset['customized'] !== ($livePreset['customized'] ?? null)))
                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2">
                    <i class="fas fa-pen me-1"></i>In draft: {{ $editPreset['name'] }}@if($editPreset['customized']) <span class="fw-normal">· customized</span>@endif
                </span>
            @endif
        </div>
    @endif

    <ul class="nav nav-pills ef-theme-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
        @foreach(['general' => ['fas fa-sliders-h', 'Style & Fonts'], 'colors' => ['fas fa-palette', 'Colours'], 'header' => ['fas fa-window-maximize', 'Header & Footer'], 'buttons' => ['fas fa-hand-pointer', 'Buttons & Icons'], 'card' => ['fas fa-th-large', 'Product Card'], 'home' => ['fas fa-home', 'Home Page'], 'presets' => ['fas fa-swatchbook', 'Presets'], 'history' => ['fas fa-history', 'History']] as $tab => [$icon, $name])
            <li class="nav-item"><button type="button" class="nav-link text-nowrap {{ $loop->first ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#tab-{{ $tab }}"><i class="{{ $icon }} me-1"></i>{{ $name }}</button></li>
        @endforeach
    </ul>

    <div class="tab-content">
        {{-- STYLE & FONTS --}}
        <div class="tab-pane fade show active" id="tab-general">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card glass-card h-100"><div class="card-body">
                        <h6 class="fw-bold mb-3">Theme style</h6>
                        @foreach(['glass' => ['Glassmorphism', 'Frosted-glass cards over a soft, glowing colourful background.'], 'classic' => ['Classic', 'Solid white cards on a light background.']] as $value => [$name, $styleHelp])
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="theme[style]" id="style_{{ $value }}" value="{{ $value }}" @checked(old('theme.style', $theme['style']) === $value)>
                                <label class="form-check-label" for="style_{{ $value }}"><b>{{ $name }}</b> <span class="text-muted small d-block">{{ $styleHelp }}</span></label>
                            </div>
                        @endforeach
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Card corner radius (px)</label>
                                <input type="number" min="0" max="40" name="theme[shape][card_radius]" value="{{ old('theme.shape.card_radius', $theme['shape']['card_radius']) }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Button corner radius (px)</label>
                                <input type="number" min="0" max="50" name="theme[shape][button_radius]" value="{{ old('theme.shape.button_radius', $theme['shape']['button_radius']) }}" class="form-control form-control-sm" data-preview-radius>
                                <small class="text-muted">50 = pill, 0 = square</small>
                            </div>
                        </div>
                    </div></div>
                </div>
                <div class="col-lg-6">
                    <div class="card glass-card h-100"><div class="card-body">
                        <h6 class="fw-bold mb-3">Fonts</h6>
                        @foreach(['heading' => 'Headings, product names, prices', 'body' => 'Body text'] as $role => $fontUsage)
                            <label class="form-label small fw-semibold mb-1">{{ ucfirst($role) }} font <span class="text-muted fw-normal">— {{ $fontUsage }}</span></label>
                            <select name="theme[fonts][{{ $role }}]" class="form-select form-select-sm mb-1" data-font-select="{{ $role }}">
                                @foreach(array_keys(\ME\Efront\Support\Theme::FONTS) as $font)
                                    <option value="{{ $font }}" @selected(old("theme.fonts.$role", $theme['fonts'][$role]) === $font)>{{ $font }}{{ in_array($font, ['Hind Siliguri', 'Noto Sans Bengali']) ? ' (বাংলা)' : '' }}{{ in_array($font, ['Lora', 'Playfair Display']) ? ' (serif)' : '' }}</option>
                                @endforeach
                            </select>
                            <div class="ef-font-sample mb-3 {{ $role === 'body' ? 'fs-6' : 'fw-bold' }}" data-font-sample="{{ $role }}">{{ $role === 'heading' ? 'Featured Products ৳1,590' : 'Genuine products, cash on delivery and easy returns.' }}</div>
                        @endforeach
                        <label class="form-label small fw-semibold mb-1">Small section labels</label>
                        <select name="theme[fonts][label_style]" class="form-select form-select-sm">
                            <option value="caps" @selected(old('theme.fonts.label_style', $theme['fonts']['label_style']) === 'caps')>Clean — SMALL UPPERCASE</option>
                            <option value="script" @selected(old('theme.fonts.label_style', $theme['fonts']['label_style']) === 'script')>Handwritten (Dancing Script)</option>
                        </select>
                    </div></div>
                </div>
            </div>
        </div>

        {{-- COLOURS --}}
        <div class="tab-pane fade" id="tab-colors">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card glass-card h-100"><div class="card-body">
                        <h6 class="fw-bold mb-3">Brand & text colours</h6>
                        <div class="row">
                            @foreach(['primary' => 'Primary (links, highlights, badges)', 'secondary' => 'Secondary (accents, active breadcrumb)', 'heading' => 'Headings', 'text' => 'Body text', 'price' => 'Price', 'label' => 'Small section labels', 'category_label' => 'Category name on product cards'] as $key => $label)
                                <div class="col-sm-6">@include('efront::admin.partials.color', ['name' => "colors.$key", 'label' => $label, 'value' => $theme['colors'][$key]])</div>
                            @endforeach
                        </div>
                    </div></div>
                </div>
                <div class="col-lg-6">
                    <div class="card glass-card h-100"><div class="card-body">
                        <h6 class="fw-bold mb-1">Glass background</h6>
                        <p class="small text-muted">Only used with the Glassmorphism style.</p>
                        <div class="row">
                            @foreach(['bg_1' => 'Background — top left', 'bg_2' => 'Background — 2', 'bg_3' => 'Background — 3', 'bg_4' => 'Background — bottom right'] as $key => $label)
                                <div class="col-sm-6">@include('efront::admin.partials.color', ['name' => "glass.$key", 'label' => $label, 'value' => $theme['glass'][$key]])</div>
                            @endforeach
                            @for($i = 1; $i <= 5; $i++)
                                <div class="col-sm-6">@include('efront::admin.partials.color', ['name' => "glass.orb_$i", 'label' => "Glow $i", 'value' => $theme['glass']["orb_$i"]])</div>
                            @endfor
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Glow strength: <span data-range-value>{{ old('theme.glass.glow', $theme['glass']['glow']) }}</span>%</label>
                                <input type="range" min="0" max="100" name="theme[glass][glow]" value="{{ old('theme.glass.glow', $theme['glass']['glow']) }}" class="form-range" oninput="this.previousElementSibling.querySelector('[data-range-value]').textContent = this.value">
                            </div>
                        </div>
                    </div></div>
                </div>
                <div class="col-12">
                    <div class="card glass-card"><div class="card-body">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <h6 class="fw-bold mb-0 me-2">Product badges</h6>
                            {{-- Live sample, painted from the fields below --}}
                            <span class="ef-badge-sample" data-badge-preview="sale">-12%</span>
                            <span class="ef-badge-sample" data-badge-preview="new"><i class="fas fa-star"></i> New</span>
                            <span class="ef-badge-sample" data-badge-preview="hot"><i class="fas fa-fire"></i> Hot</span>
                        </div>
                        <div class="row">
                            @foreach(['sale' => 'Discount (-12%, campaign deal)', 'new' => 'New arrival', 'hot' => 'Hot (featured)'] as $key => $label)
                                <div class="col-md-4">
                                    <div class="row g-2">
                                        <div class="col-6">@include('efront::admin.partials.color', ['name' => "badges.{$key}_bg", 'label' => $label, 'value' => $theme['badges']["{$key}_bg"]])</div>
                                        <div class="col-6">@include('efront::admin.partials.color', ['name' => "badges.{$key}_text", 'label' => 'Text', 'value' => $theme['badges']["{$key}_text"]])</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div></div>
                </div>
            </div>
        </div>

        {{-- HEADER & FOOTER --}}
        <div class="tab-pane fade" id="tab-header">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card glass-card h-100"><div class="card-body">
                        <h6 class="fw-bold mb-3">Header</h6>
                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="theme[header][topbar]" value="0">
                            <input class="form-check-input" type="checkbox" name="theme[header][topbar]" value="1" id="topbar" @checked(old('theme.header.topbar', $theme['header']['topbar']))>
                            <label class="form-check-label" for="topbar">Show the top bar (phone, email, social links)</label>
                        </div>
                        <div class="row">
                            @foreach(['topbar_bg' => 'Top bar background', 'topbar_text' => 'Top bar text', 'navbar_bg' => 'Menu bar background', 'navbar_text' => 'Menu bar links', 'pagehead_bg' => 'Page title banner background', 'pagehead_text' => 'Page title banner text'] as $key => $label)
                                <div class="col-sm-6">@include('efront::admin.partials.color', ['name' => "header.$key", 'label' => $label, 'value' => $theme['header'][$key]])</div>
                            @endforeach
                        </div>
                    </div></div>
                </div>
                <div class="col-lg-6">
                    <div class="card glass-card h-100"><div class="card-body">
                        <h6 class="fw-bold mb-3">Footer</h6>
                        <div class="row">
                            @foreach(['bg' => 'Background', 'text' => 'Text & links', 'heading' => 'Headings'] as $key => $label)
                                <div class="col-sm-6">@include('efront::admin.partials.color', ['name' => "footer.$key", 'label' => $label, 'value' => $theme['footer'][$key]])</div>
                            @endforeach
                        </div>
                    </div></div>
                </div>
            </div>
        </div>

        {{-- BUTTONS & ICONS --}}
        <div class="tab-pane fade" id="tab-buttons">
            <div class="row g-3">
                @foreach($buttonNames as $key => $name)
                    @php($button = $theme['buttons'][$key])
                    <div class="col-md-6 col-xl-4">
                        <div class="card glass-card h-100"><div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                                <h6 class="fw-bold mb-0">{{ $name }}</h6>
                                @if($key === 'quick_add')
                                    <button type="button" class="ef-preview-round" data-button-preview="{{ $key }}"><i></i></button>
                                @else
                                    <button type="button" class="ef-preview-btn" data-button-preview="{{ $key }}"><i></i><span></span></button>
                                @endif
                            </div>
                            <div class="row">
                                <div class="col-6">@include('efront::admin.partials.color', ['name' => "buttons.$key.bg", 'label' => 'Background', 'value' => $button['bg'], 'preview' => $key])</div>
                                <div class="col-6">@include('efront::admin.partials.color', ['name' => "buttons.$key.text", 'label' => 'Text', 'value' => $button['text'], 'preview' => $key])</div>
                            </div>
                            @if(! in_array($key, ['primary', 'quick_add']))
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold mb-1">Label</label>
                                    <input type="text" name="theme[buttons][{{ $key }}][label]" value="{{ old("theme.buttons.$key.label", $button['label']) }}" maxlength="40" class="form-control form-control-sm" data-label-input data-preview="{{ $key }}">
                                </div>
                            @endif
                            @if($key !== 'primary')
                                @include('efront::admin.partials.icon', ['name' => "buttons.$key.icon", 'label' => 'Icon', 'value' => $button['icon'], 'preview' => $key])
                            @endif
                            @if($key === 'primary')<p class="small text-muted mb-0">Shop Now, View All Products, Grab the Deal, Start Shopping — their texts are set per section on the Home Page tab.</p>@endif
                        </div></div>
                    </div>
                @endforeach
                <div class="col-12">
                    <div class="card glass-card"><div class="card-body">
                        <h6 class="fw-bold mb-3">Header icons</h6>
                        <div class="row">
                            @foreach($iconNames as $key => $name)
                                <div class="col-sm-6 col-lg">@include('efront::admin.partials.icon', ['name' => "icons.$key", 'label' => $name, 'value' => $theme['icons'][$key], 'required' => true])</div>
                            @endforeach
                        </div>
                        <p class="small text-muted mb-0">Any Font Awesome 6 icon works — pick one from the list or type its class (see fontawesome.com/icons). Example: <code>fas fa-cart-plus</code>.</p>
                    </div></div>
                </div>
            </div>
        </div>

        {{-- PRODUCT CARD --}}
        <div class="tab-pane fade" id="tab-card">
            <div class="card glass-card"><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Image fit</label>
                        @foreach(['contain' => 'Show the whole photo (recommended — nothing is cut off)', 'cover' => 'Fill the box (photo may be cropped)'] as $value => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="theme[card][image_fit]" id="fit_{{ $value }}" value="{{ $value }}" @checked(old('theme.card.image_fit', $theme['card']['image_fit']) === $value)>
                                <label class="form-check-label small" for="fit_{{ $value }}">{{ $label }}</label>
                            </div>
                        @endforeach
                        <div class="row mt-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Image height (px)</label>
                                <input type="number" min="120" max="420" name="theme[card][image_height]" value="{{ old('theme.card.image_height', $theme['card']['image_height']) }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Space around image (px)</label>
                                <input type="number" min="0" max="40" name="theme[card][image_padding]" value="{{ old('theme.card.image_padding', $theme['card']['image_padding']) }}" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        @include('efront::admin.partials.color', ['name' => 'card.image_bg', 'label' => 'Image background', 'value' => $theme['card']['image_bg']])
                        @foreach(['show_category' => 'Show category name', 'show_description' => 'Show short description', 'show_rating' => 'Show star rating'] as $key => $label)
                            <div class="form-check form-switch">
                                <input type="hidden" name="theme[card][{{ $key }}]" value="0">
                                <input class="form-check-input" type="checkbox" name="theme[card][{{ $key }}]" value="1" id="card_{{ $key }}" @checked(old("theme.card.$key", $theme['card'][$key]))>
                                <label class="form-check-label small" for="card_{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div></div>
        </div>

        {{-- HOME PAGE SECTIONS --}}
        <div class="tab-pane fade" id="tab-home">
            <p class="small text-muted"><i class="fas fa-arrows-alt me-1"></i>Drag sections to change their order. Switch a section off to hide it. Leave a colour unticked to use the theme's colour.</p>
            <div id="sectionList">
                @foreach($theme['sections'] as $key => $section)
                    <div class="card glass-card mb-2 ef-section-card {{ $section['enabled'] ? '' : 'is-off' }}" data-section="{{ $key }}">
                        <input type="hidden" name="section_order[]" value="{{ $key }}">
                        <div class="card-header d-flex align-items-center gap-2 py-2">
                            <i class="fas fa-grip-vertical ef-drag" title="Drag to reorder"></i>
                            <div class="form-check form-switch mb-0">
                                <input type="hidden" name="theme[sections][{{ $key }}][enabled]" value="0">
                                <input class="form-check-input" type="checkbox" name="theme[sections][{{ $key }}][enabled]" value="1" @checked(old("theme.sections.$key.enabled", $section['enabled'])) data-section-toggle>
                            </div>
                            <b class="flex-grow-1">{{ $sectionNames[$key] ?? $key }}</b>
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#sec-{{ $key }}"><i class="fas fa-pen me-1"></i>Edit</button>
                        </div>
                        <div class="collapse" id="sec-{{ $key }}">
                            <div class="card-body pt-2">
                                <div class="row">
                                    @foreach(['badge', 'label', 'title', 'highlight', 'button', 'limit'] as $field)
                                        @if(array_key_exists($field, $section))
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label small fw-semibold mb-1">{{ $fieldLabels[$field] }}</label>
                                                <input type="{{ $field === 'limit' ? 'number' : 'text' }}" @if($field === 'limit') min="1" max="24" @endif name="theme[sections][{{ $key }}][{{ $field }}]" value="{{ old("theme.sections.$key.$field", $section[$field]) }}" class="form-control form-control-sm @error("theme.sections.$key.$field") is-invalid @enderror">
                                            </div>
                                        @endif
                                    @endforeach
                                    @if(array_key_exists('text', $section))
                                        <div class="col-12 mb-3">
                                            <label class="form-label small fw-semibold mb-1">{{ $fieldLabels['text'] }}</label>
                                            <textarea name="theme[sections][{{ $key }}][text]" rows="2" class="form-control form-control-sm">{{ old("theme.sections.$key.text", $section['text']) }}</textarea>
                                        </div>
                                    @endif
                                    @if(array_key_exists('filter', $section))
                                        <div class="col-12 mb-3">
                                            <div class="form-check form-switch">
                                                <input type="hidden" name="theme[sections][{{ $key }}][filter]" value="0">
                                                <input class="form-check-input" type="checkbox" name="theme[sections][{{ $key }}][filter]" value="1" id="filter_{{ $key }}" @checked(old("theme.sections.$key.filter", $section['filter']))>
                                                <label class="form-check-label small" for="filter_{{ $key }}">Show category filter buttons</label>
                                            </div>
                                        </div>
                                    @endif
                                    @foreach($section['items'] ?? [] as $i => $item)
                                        <div class="col-md-6 col-xl-3 mb-2">
                                            <div class="border rounded p-2 h-100">
                                                <div class="small fw-semibold mb-2">Feature {{ $i + 1 }}</div>
                                                @include('efront::admin.partials.icon', ['name' => "sections.$key.items.$i.icon", 'label' => 'Icon', 'value' => $item['icon']])
                                                <input type="text" name="theme[sections][{{ $key }}][items][{{ $i }}][title]" value="{{ old("theme.sections.$key.items.$i.title", $item['title']) }}" maxlength="60" class="form-control form-control-sm mb-2" placeholder="Title (empty = hide)">
                                                <input type="text" name="theme[sections][{{ $key }}][items][{{ $i }}][text]" value="{{ old("theme.sections.$key.items.$i.text", $item['text']) }}" maxlength="120" class="form-control form-control-sm" placeholder="{{ in_array($i, [0, 3]) ? 'Empty = live store info' : 'Text' }}">
                                            </div>
                                        </div>
                                    @endforeach
                                    @foreach(['bg' => 'Background colour', 'heading' => 'Title colour'] as $field => $label)
                                        @php($custom = (bool) old("theme.sections.$key.custom_$field", filled($section[$field])))
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check mb-1">
                                                <input type="hidden" name="theme[sections][{{ $key }}][custom_{{ $field }}]" value="0">
                                                <input class="form-check-input" type="checkbox" name="theme[sections][{{ $key }}][custom_{{ $field }}]" value="1" id="custom_{{ $key }}_{{ $field }}" @checked($custom)>
                                                <label class="form-check-label small fw-semibold" for="custom_{{ $key }}_{{ $field }}">Own {{ strtolower($label) }}</label>
                                            </div>
                                            @include('efront::admin.partials.color', ['name' => "sections.$key.$field", 'label' => '', 'value' => $section[$field] ?? ($field === 'bg' ? '#ffffff' : '#1a1a1a')])
                                        </div>
                                    @endforeach
                                </div>
                                @if($key === 'hero')
                                    <p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i>When you have active "slider" banners (Website → Banners) the slider is shown instead of this text hero.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- PRESETS --}}
        <div class="tab-pane fade" id="tab-presets">
            <p class="small text-muted"><i class="fas fa-info-circle me-1"></i>A preset changes only the style, fonts and colours — your texts, sections, button labels and icons stay. It is applied as a <b>draft</b>: check the live preview, then Publish.</p>
            <div class="row g-3">
                @foreach($presets as $key => $preset)
                    @php($ps = $preset['settings'])
                    @php($swatchBg = ($ps['style'] ?? 'glass') === 'glass' ? 'linear-gradient(135deg,'.($ps['glass']['bg_1'] ?? '#fff').','.($ps['glass']['bg_3'] ?? '#fff').')' : '#ffffff')
                    <div class="col-sm-6 col-xl-3">
                        @php($isLive = ($livePreset['key'] ?? null) === $key)
                        @php($isDraft = $hasDraft && ($editPreset['key'] ?? null) === $key && ! $isLive)
                        <div @class(['card ef-preset h-100 border', 'is-live' => $isLive, 'is-draft' => $isDraft])>
                            <div class="ef-preset-swatch" style="background: {{ $swatchBg }}">
                                @if($isLive)<span class="ef-preset-state bg-success"><i class="fas fa-check me-1"></i>Live</span>@endif
                                @if($isDraft)<span class="ef-preset-state" style="background:#fd7e14"><i class="fas fa-pen me-1"></i>In draft</span>@endif
                                @foreach(array_filter([$ps['colors']['primary'] ?? null, $ps['colors']['secondary'] ?? null, $ps['glass']['orb_2'] ?? null, $ps['footer']['bg'] ?? null]) as $swatch)
                                    <span style="background: {{ $swatch }}"></span>
                                @endforeach
                                <button type="submit" form="efPreset-{{ $key }}" class="ef-preset-btn" style="background: {{ $ps['buttons']['primary']['bg'] ?? '#111' }}; color: {{ $ps['buttons']['primary']['text'] ?? '#fff' }}">Apply</button>
                            </div>
                            <div class="card-body py-2">
                                <div class="fw-bold" style="font-family: '{{ $ps['fonts']['heading'] ?? 'inherit' }}', sans-serif">{{ $preset['name'] }}</div>
                                <div class="small text-muted">{{ $preset['description'] }}</div>
                                <div class="small mt-1"><span class="badge bg-light text-dark border">{{ ucfirst($ps['style'] ?? 'glass') }}</span> <span class="badge bg-light text-dark border">{{ $ps['fonts']['heading'] ?? '' }}</span></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- HISTORY --}}
        <div class="tab-pane fade" id="tab-history">
            <div class="card glass-card"><div class="card-body p-0">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th class="ps-3">Version</th><th>Note</th><th>By</th><th>Published</th><th class="text-end pe-3">Actions</th></tr></thead>
                    <tbody>
                        @forelse($versions as $version)
                            <tr>
                                <td class="ps-3">#{{ $version->id }} @if($loop->first)<span class="badge bg-success ms-1">Live</span>@endif</td>
                                <td>{{ $version->note ?: '—' }}</td>
                                <td class="small">{{ $version->user?->name ?? '—' }}</td>
                                <td class="small" title="{{ $version->created_at }}">{{ $version->created_at->format('d M Y, h:i A') }} <span class="text-muted">· {{ $version->created_at->diffForHumans() }}</span></td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a href="{{ route('efront.admin.theme.version.preview', $version) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0"><i class="fas fa-eye me-1"></i>Preview</a>
                                    @unless($loop->first)
                                        <button type="submit" form="efRestore-{{ $version->id }}" class="btn btn-sm btn-outline-success py-0"><i class="fas fa-undo me-1"></i>Restore</button>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-history fa-2x mb-2 d-block opacity-50"></i>
                                <b>No versions yet.</b> The store is using the {{ \ME\Models\Setting::where('key', \ME\Efront\Support\Theme::SETTING_KEY)->exists() ? 'saved' : 'default' }} theme.<br>
                                A version is saved every time you press <b>Publish</b> (and on Reset / Restore) — the last {{ \ME\Efront\Models\ThemeVersion::KEEP }} are kept, so you can always go back.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div></div>
        </div>
    </div>

    <datalist id="efIconList">
        @foreach($iconSuggestions as $icon)<option value="{{ $icon }}">@endforeach
    </datalist>

    <div class="ef-actionbar d-flex flex-wrap align-items-center gap-2">
        <input type="text" name="note" maxlength="120" class="form-control form-control-sm" style="max-width:320px" placeholder="Version note (optional), e.g. Eid colours">
        <span class="small text-muted ms-auto d-none d-md-inline">Draft = only you see it · Publish = customers see it</span>
        <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen-nib me-1"></i>Save Draft</button>
        <button type="submit" name="action" value="publish" class="btn btn-sm btn-encodex-save"><i class="fas fa-rocket me-1"></i>Publish</button>
    </div>
</form>

{{-- Stand-alone forms used by buttons above (forms cannot be nested) --}}
<form id="efResetForm" action="{{ route('efront.admin.theme.reset') }}" method="POST" class="d-none" onsubmit="return confirm('Reset every theme setting to the default look? (You can undo it from History.)')">@csrf</form>
<form id="efDiscardForm" action="{{ route('efront.admin.theme.draft.discard') }}" method="POST" class="d-none" onsubmit="return confirm('Throw away the unpublished draft?')">@csrf @method('DELETE')</form>
@foreach($presets as $key => $preset)
    <form id="efPreset-{{ $key }}" action="{{ route('efront.admin.theme.preset', $key) }}" method="POST" class="d-none" @if($hasDraft) onsubmit="return confirm('Apply {{ $preset['name'] }} over your current draft?')" @endif>@csrf</form>
@endforeach
@foreach($versions->skip(1) as $version)
    <form id="efRestore-{{ $version->id }}" action="{{ route('efront.admin.theme.version.restore', $version) }}" method="POST" class="d-none" onsubmit="return confirm('Publish version #{{ $version->id }} again?')">@csrf</form>
@endforeach

<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('efront.admin.theme.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-upload me-1"></i> Import Theme</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted">Choose a <code>.json</code> file made with <b>Export</b> (from this or another store). It is checked and loaded as a <b>draft</b> — nothing goes live until you Publish.</p>
                <input type="file" name="file" accept=".json,application/json" class="form-control form-control-sm" required>
            </div>
            <div class="modal-footer"><button class="btn btn-sm btn-encodex-save"><i class="fas fa-upload me-1"></i> Import as Draft</button></div>
        </form>
    </div>
</div>

{{-- LIVE PREVIEW PANEL --}}
<div id="efPreview" aria-hidden="true">
    <div class="ef-preview-bar">
        <select class="form-select form-select-sm" id="previewPage" style="max-width:150px">
            @foreach($previewPages as $name => $url)<option value="{{ $url }}">{{ $name }}</option>@endforeach
        </select>
        <div class="btn-group btn-group-sm" id="previewDevice">
            <button type="button" class="btn btn-outline-secondary active" data-width="100%" title="Desktop"><i class="fas fa-desktop"></i></button>
            <button type="button" class="btn btn-outline-secondary" data-width="768px" title="Tablet"><i class="fas fa-tablet-alt"></i></button>
            <button type="button" class="btn btn-outline-secondary" data-width="390px" title="Mobile"><i class="fas fa-mobile-alt"></i></button>
        </div>
        <span class="ef-preview-status text-muted" id="previewStatus"></span>
        <button type="button" class="btn btn-sm btn-light ms-auto" id="previewRefresh" title="Refresh"><i class="fas fa-sync-alt"></i></button>
        <a href="{{ route('efront.home') }}" target="_blank" class="btn btn-sm btn-light" id="previewOpen" title="Open in new tab"><i class="fas fa-external-link-alt"></i></a>
        <button type="button" class="btn btn-sm btn-light" id="previewClose" title="Close preview"><i class="fas fa-times"></i></button>
    </div>
    <div class="ef-preview-stage"><iframe id="previewFrame" title="Store preview"></iframe></div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?{{ collect(array_keys(\ME\Efront\Support\Theme::FONTS))->map(fn ($f) => 'family='.str_replace(' ', '+', $f).':wght@400;700')->implode('&') }}&display=swap">
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('themeForm');
    const value = name => (form.querySelector('[name="theme[' + name.split('.').join('][') + ']"]:not([type=hidden])') || {}).value;

    // Colour picker <-> hex text box
    form.querySelectorAll('[data-color-for]').forEach(picker => {
        const text = form.querySelector('[data-color-text="' + picker.dataset.colorFor + '"]');
        picker.addEventListener('input', () => { text.value = picker.value; text.dispatchEvent(new Event('input')); });
        text.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value; });
    });

    // Icon previews
    form.querySelectorAll('[data-icon-input]').forEach(input => {
        input.addEventListener('input', () => { input.closest('.input-group').querySelector('[data-icon-preview]').className = input.value; });
    });

    // Live button previews
    function paintButton(key) {
        const button = form.querySelector('[data-button-preview="' + key + '"]');
        if (!button) return;
        const bg = value('buttons.' + key + '.bg'), text = value('buttons.' + key + '.text');
        const radius = form.querySelector('[data-preview-radius]').value;
        button.style.background = bg;
        button.style.color = text;
        button.style.boxShadow = '0 8px 22px -6px ' + bg + '99';
        if (key !== 'quick_add') button.style.borderRadius = radius + 'px';
        const icon = value('buttons.' + key + '.icon');
        button.querySelector('i').className = icon || (key === 'primary' ? 'fas fa-store' : '');
        const label = button.querySelector('span');
        if (label) label.textContent = value('buttons.' + key + '.label') || (key === 'primary' ? 'Shop Now' : '');
    }
    form.querySelectorAll('[data-button-preview]').forEach(b => paintButton(b.dataset.buttonPreview));

    // Live badge previews
    function paintBadges() {
        form.querySelectorAll('[data-badge-preview]').forEach(badge => {
            const key = badge.dataset.badgePreview;
            badge.style.background = value('badges.' + key + '_bg');
            badge.style.color = value('badges.' + key + '_text');
        });
    }
    paintBadges();
    form.addEventListener('input', paintBadges);
    form.addEventListener('change', paintBadges);

    form.addEventListener('input', e => {
        if (e.target.dataset.preview) paintButton(e.target.dataset.preview);
        if (e.target.matches('[data-preview-radius]')) form.querySelectorAll('[data-button-preview]').forEach(b => paintButton(b.dataset.buttonPreview));
    });

    // Font samples
    form.querySelectorAll('[data-font-select]').forEach(select => {
        const sample = form.querySelector('[data-font-sample="' + select.dataset.fontSelect + '"]');
        const apply = () => sample.style.fontFamily = '"' + select.value + '", sans-serif';
        select.addEventListener('change', apply);
        apply();
    });

    // Section on/off look + drag to reorder
    form.querySelectorAll('[data-section-toggle]').forEach(toggle => {
        toggle.addEventListener('change', () => toggle.closest('.ef-section-card').classList.toggle('is-off', !toggle.checked));
    });
    if (window.Sortable) new Sortable(document.getElementById('sectionList'), { handle: '.ef-drag', animation: 150 });

    /* ---------- Readability (WCAG contrast) check, live ---------- */
    const contrastChecks = @json($jsContrastChecks);
    const sectionNames = @json($sectionNames);
    const luminance = hex => {
        const c = [1, 3, 5].map(i => parseInt(hex.substr(i, 2), 16) / 255).map(v => v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4));
        return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
    };
    const ratio = (a, b) => { const [l, d] = [luminance(a), luminance(b)].sort((x, y) => y - x); return (l + 0.05) / (d + 0.05); };
    const isHex = v => /^#[0-9a-f]{6}$/i.test(v || '');
    const checked = name => !!form.querySelector('[name="' + name + '"][type=checkbox]:checked');
    function contrastIssues() {
        const glass = (form.querySelector('[name="theme[style]"]:checked') || {}).value === 'glass';
        const resolve = p => p === 'PAGE' ? (glass ? value('glass.bg_1') : '#ffffff') : (p.startsWith('#') ? p : value(p));
        const checks = contrastChecks.filter(c => !c.topbar || checked('theme[header][topbar]'));
        form.querySelectorAll('[data-section]').forEach(card => {
            const key = card.dataset.section;
            if (checked('theme[sections][' + key + '][enabled]') && checked('theme[sections][' + key + '][custom_bg]')) {
                const heading = checked('theme[sections][' + key + '][custom_heading]') ? 'sections.' + key + '.heading' : 'colors.heading';
                checks.push({ label: sectionNames[key] + ' — title on its background', fg: heading, bg: 'sections.' + key + '.bg', min: 3 });
            }
        });
        return checks.map(c => ({ ...c, fgv: resolve(c.fg), bgv: resolve(c.bg) }))
            .filter(c => isHex(c.fgv) && isHex(c.bgv))
            .map(c => ({ label: c.label, min: c.min, ratio: Math.round(ratio(c.fgv, c.bgv) * 100) / 100 }))
            .filter(c => c.ratio < c.min);
    }
    function showContrast() {
        const issues = contrastIssues();
        const box = document.getElementById('contrastBox');
        box.classList.toggle('d-none', issues.length === 0);
        document.getElementById('contrastList').innerHTML = issues.map(i =>
            '<li>' + i.label.replace(/[<>&]/g, '') + ' <span class="text-muted">(contrast ' + i.ratio + ':1, needs ' + i.min + ':1)</span></li>').join('');
    }
    form.addEventListener('input', showContrast);
    form.addEventListener('change', showContrast);

    /* ---------- Live preview ---------- */
    const panel = document.getElementById('efPreview');
    const frame = document.getElementById('previewFrame');
    const status = document.getElementById('previewStatus');
    const pageSelect = document.getElementById('previewPage');
    let previewTimer = null, previewOpen = false, previewBusy = false, previewAgain = false;

    function setStatus(text, cls) { status.textContent = text; status.className = 'ef-preview-status ' + (cls || 'text-muted'); }

    function reloadFrame() {
        let y = 0;
        try { y = frame.contentWindow.scrollY || 0; } catch (e) {}
        frame.onload = () => { try { frame.contentWindow.scrollTo(0, y); } catch (e) {} setStatus('Up to date', 'text-success'); };
        frame.src = pageSelect.value + (pageSelect.value.includes('?') ? '&' : '?') + '_preview=' + Date.now();
        document.getElementById('previewOpen').href = pageSelect.value;
    }

    async function pushPreview() {
        if (!previewOpen) return;
        if (previewBusy) { previewAgain = true; return; }
        previewBusy = true;
        setStatus('Updating…');
        const data = new FormData(form);
        data.delete('_method');
        data.delete('action');
        try {
            const response = await fetch(@json(route('efront.admin.theme.preview')), {
                method: 'POST', body: data, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': data.get('_token') },
            });
            const json = await response.json();
            if (response.ok) { reloadFrame(); } else { setStatus(json.message || 'Fix the highlighted value', 'text-danger'); }
        } catch (e) {
            setStatus('Preview failed', 'text-danger');
        }
        previewBusy = false;
        if (previewAgain) { previewAgain = false; pushPreview(); }
    }

    function openPreview(open) {
        previewOpen = open;
        document.body.classList.toggle('ef-previewing', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        try { localStorage.setItem('efThemePreview', open ? '1' : '0'); } catch (e) {}
        if (open) pushPreview();
    }

    document.getElementById('previewToggle').addEventListener('click', () => openPreview(!previewOpen));
    document.getElementById('previewClose').addEventListener('click', () => openPreview(false));
    document.getElementById('previewRefresh').addEventListener('click', pushPreview);
    pageSelect.addEventListener('change', reloadFrame);
    document.querySelectorAll('#previewDevice [data-width]').forEach(button => button.addEventListener('click', () => {
        document.querySelectorAll('#previewDevice .active').forEach(b => b.classList.remove('active'));
        button.classList.add('active');
        frame.style.width = button.dataset.width;
    }));
    const schedulePreview = e => {
        if (!previewOpen || (e.target.name || '') === 'note' || !e.target.closest('#themeForm')) return;
        clearTimeout(previewTimer);
        setStatus('Changes pending…');
        previewTimer = setTimeout(pushPreview, 700);
    };
    form.addEventListener('input', schedulePreview);
    form.addEventListener('change', schedulePreview);
    if (window.Sortable) {
        // Re-preview after a section is dragged (Sortable is created above)
        document.getElementById('sectionList').addEventListener('pointerup', () => setTimeout(() => schedulePreview({ target: form.querySelector('[name="section_order[]"]') }), 50));
    }
    try { if (localStorage.getItem('efThemePreview') === '1' && window.innerWidth >= 992) openPreview(true); } catch (e) {}

    // Re-open the tab that had an error after a failed save
    const invalid = form.querySelector('.is-invalid');
    if (invalid) {
        const pane = invalid.closest('.tab-pane');
        const collapse = invalid.closest('.collapse');
        if (collapse) collapse.classList.add('show');
        if (pane) document.querySelector('[data-bs-target="#' + pane.id + '"]').click();
    }
});
</script>
@endpush
