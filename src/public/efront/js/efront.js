/**
 * Efront storefront behaviour (Sarab theme). Needs jQuery, Bootstrap, AOS, Swiper, Magnific Popup
 * and window.Efront = { csrf, loginUrl, cartUrl, miniCartUrl, suggestUrl, wishlistUrl } from the layout.
 */
(function ($) {
    'use strict';

    var Efront = window.Efront || {};
    window.Efront = Efront;

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': Efront.csrf, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });

    /* ---------- Helpers ---------- */
    Efront.toast = function (message, type) {
        var $toast = $('<div class="ef-toast"></div>').toggleClass('error', type === 'error')
            .append($('<i>').addClass(type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle'))
            .append($('<span>').text(message));
        $('#efToasts').append($toast);
        setTimeout(function () {
            $toast.addClass('hide');
            setTimeout(function () { $toast.remove(); }, 400);
        }, 3200);
    };

    function errorMessage(xhr) {
        var json = xhr.responseJSON || {};
        if (json.errors) {
            var first = Object.keys(json.errors)[0];
            return json.errors[first][0];
        }
        return json.message || 'Something went wrong. Please try again.';
    }

    function handleError(xhr) {
        if (xhr.status === 401 && xhr.responseJSON && xhr.responseJSON.login) {
            window.location.href = xhr.responseJSON.login;
            return;
        }
        if (xhr.status === 419) {
            Efront.toast('Your session expired. Reloading…', 'error');
            setTimeout(function () { window.location.reload(); }, 1200);
            return;
        }
        Efront.toast(errorMessage(xhr), 'error');
    }

    function setBadge(selector, count) {
        $(selector).text(count).prop('hidden', !count).removeClass('bump');
        setTimeout(function () { $(selector).addClass('bump'); }, 10);
    }

    function refreshCart(res) {
        if (typeof res.count !== 'undefined') { setBadge('[data-cart-count]', res.count); }
        if (res.mini) { $('#miniCartBody').html(res.mini); }
        if (res.cart && $('#cartContent').length) { $('#cartContent').html(res.cart); }
    }

    function money(amount, symbol) {
        return symbol + Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function lockScroll(lock) { document.body.style.overflow = lock ? 'hidden' : ''; }

    /* ---------- Page chrome ---------- */
    if (window.AOS) { AOS.init({ duration: 680, once: true, offset: 55 }); }

    // Every popup (search, quick view, mini cart, dropdowns) starts at the navbar's bottom edge — kept in --ef-nav-bottom
    function syncNavBottom() {
        var nav = document.getElementById('nav');
        if (nav) { document.documentElement.style.setProperty('--ef-nav-bottom', Math.max(0, Math.round(nav.getBoundingClientRect().bottom)) + 'px'); }
    }
    syncNavBottom();
    $(window).on('resize load', syncNavBottom);
    $(document).on('show.bs.offcanvas show.bs.dropdown', syncNavBottom);

    $(window).on('scroll', function () {
        $('#nav').toggleClass('scrolled', window.scrollY > 60);
        $('#btt').toggleClass('show', window.scrollY > 300);
        syncNavBottom();
    });
    $('#btt').on('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    // Mobile menu drawer: lift the navbar above the backdrop and swap the burger for a close icon
    var navDrawer = document.getElementById('navmenu');
    if (navDrawer) {
        navDrawer.addEventListener('show.bs.offcanvas', function () { syncNavBottom(); document.body.classList.add('ef-drawer-open'); });
        navDrawer.addEventListener('hidden.bs.offcanvas', function () { document.body.classList.remove('ef-drawer-open'); });
    }

    // Swipeable tab rows (mobile): bring the active tab into view, drop the right fade at the end
    document.querySelectorAll('[data-tab-scroller]').forEach(function (row) {
        var active = row.querySelector('.active');
        if (active && row.scrollWidth > row.clientWidth) {
            row.scrollLeft = active.offsetLeft - (row.clientWidth - active.offsetWidth) / 2;
        }
        var edge = function () { row.classList.toggle('is-end', row.scrollLeft + row.clientWidth >= row.scrollWidth - 4); };
        row.addEventListener('scroll', edge, { passive: true });
        edge();
    });

    $(document).on('click', '[data-toggle-password]', function () {
        var $input = $(this).siblings('input');
        var show = $input.attr('type') === 'password';
        $input.attr('type', show ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
    });

    $(document).on('click', '[data-copy]', function () {
        var text = $(this).data('copy');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () { Efront.toast('Link copied.'); });
        }
    });

    /* ---------- Search overlay + live suggestions ---------- */
    var $searchOv = $('#searchOv');
    var suggestTimer = null;
    var suggestXhr = null;

    function openSearch() {
        syncNavBottom();
        $searchOv.addClass('open');
        lockScroll(true);
        setTimeout(function () { $('#searchInput').trigger('focus'); }, 220);
    }

    function closeSearch() {
        $searchOv.removeClass('open');
        lockScroll(false);
    }

    $(document).on('click', '[data-search-open]', openSearch);
    $(document).on('click', '[data-search-close]', closeSearch);
    $searchOv.on('click', function (e) { if (e.target === this) { closeSearch(); } });

    $('#searchInput').on('input', function () {
        var term = $.trim(this.value);
        clearTimeout(suggestTimer);
        if (term.length < 2) { $('#searchResults').empty(); return; }
        suggestTimer = setTimeout(function () {
            if (suggestXhr) { suggestXhr.abort(); }
            suggestXhr = $.getJSON(Efront.suggestUrl, { q: term }, function (res) { $('#searchResults').html(res.html); });
        }, 280);
    });

    /* ---------- Quick view ---------- */
    var $quickView = $('#menuPop');

    function closeQuickView() {
        $quickView.removeClass('open');
        lockScroll(false);
    }

    $(document).on('click', '[data-quick-view]', function (e) {
        e.preventDefault();
        $('#qvBody').html('<div class="ef-loading"><i class="fas fa-spinner fa-spin"></i></div>');
        syncNavBottom();
        $quickView.addClass('open');
        lockScroll(true);
        $.get($(this).data('quick-view'), function (html) {
            $('#qvBody').html(html);
            initPurchaseForms($('#qvBody'));
            initCountdowns($('#qvBody'));
        }).fail(function (xhr) {
            closeQuickView();
            handleError(xhr);
        });
    });
    $(document).on('click', '[data-qv-close]', closeQuickView);
    $quickView.on('click', function (e) { if (e.target === this) { closeQuickView(); } });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSearch();
            closeQuickView();
        }
    });

    /* ---------- Quantity input ---------- */
    $(document).on('click', '[data-qty-step]', function () {
        var $input = $(this).closest('[data-qty]').find('input');
        var max = parseInt($input.attr('max'), 10) || 999;
        var value = (parseInt($input.val(), 10) || 1) + parseInt($(this).data('qty-step'), 10);
        $input.val(Math.min(max, Math.max(1, value))).trigger('change');
    });

    /* ---------- Purchase form: variants + add to cart ---------- */
    function initPurchaseForms($scope) {
        $scope.find('[data-purchase-form]').each(function () {
            var $form = $(this);
            if ($form.data('ready')) { return; }
            $form.data('ready', true);

            var variants = JSON.parse($form.find('[data-variants]').text() || '[]');
            var $groups = $form.find('[data-attribute]');
            var symbol = ($form.find('[data-price]').text().match(/^[^\d]*/) || [''])[0];
            var $scopeRoot = $form.closest('.ef-product, #qvBody');
            if (!$scopeRoot.length) { $scopeRoot = $(document.body); }

            function selectedValues() {
                return $groups.map(function () {
                    return $(this).find('.active').data('value');
                }).get().filter(Boolean);
            }

            function matchVariant(values) {
                if (values.length !== $groups.length) { return null; }
                return variants.find(function (variant) {
                    return values.every(function (id) { return variant.values.indexOf(id) !== -1; });
                }) || null;
            }

            function markUnavailable() {
                // A value is unavailable when no in-stock variant has it together with the other choices
                $groups.each(function (groupIndex) {
                    var others = $groups.not(this).map(function () { return $(this).find('.active').data('value'); }).get().filter(Boolean);
                    $(this).find('[data-value]').each(function () {
                        var id = $(this).data('value');
                        var possible = variants.some(function (variant) {
                            return variant.stock > 0 && variant.values.indexOf(id) !== -1 && others.every(function (o) { return variant.values.indexOf(o) !== -1; });
                        });
                        $(this).toggleClass('unavailable', !possible);
                    });
                });
            }

            function update() {
                var variant = matchVariant(selectedValues());
                var $stock = $form.find('[data-stock]');
                var $buttons = $form.find('button[type=submit]');
                var $qty = $form.find('[data-qty] input');

                $form.find('[data-variant-input]').val(variant ? variant.id : '');

                if (!variants.length) { return; }

                if (!variant) {
                    $stock.html('<span class="text-muted"><i class="fas fa-info-circle me-1"></i>Choose an option to see availability</span>');
                    return;
                }

                $form.find('[data-price]').text(money(variant.price, symbol));
                $form.find('[data-regular-price]').text(money(variant.regular, symbol)).prop('hidden', variant.regular <= variant.price);
                if (variant.sku) { $scopeRoot.find('[data-sku]').text(variant.sku); }
                if (variant.image) {
                    $scopeRoot.find('[data-gallery-image]').attr('src', variant.image);
                    $scopeRoot.find('[data-gallery-main]').attr('href', variant.image);
                }

                if (variant.stock > 0) {
                    $stock.html(variant.stock <= 5
                        ? '<span class="text-warning"><i class="fas fa-fire me-1"></i>Only ' + variant.stock + ' left — order soon</span>'
                        : '<span class="text-success"><i class="fas fa-check-circle me-1"></i>In stock</span>');
                    $qty.attr('max', variant.stock);
                    if (parseInt($qty.val(), 10) > variant.stock) { $qty.val(variant.stock); }
                } else {
                    $stock.html('<span class="text-danger"><i class="fas fa-times-circle me-1"></i>This option is out of stock</span>');
                }
                $buttons.prop('disabled', variant.stock <= 0);
            }

            $groups.on('click', '[data-value]', function () {
                var $group = $(this).closest('[data-attribute]');
                $group.find('[data-value]').removeClass('active');
                $(this).addClass('active');
                $group.find('[data-option-selected]').text($(this).data('label')).removeClass('text-muted');
                markUnavailable();
                update();
            });

            // Preselect when there is only one value in a group
            $groups.each(function () {
                var $values = $(this).find('[data-value]');
                if ($values.length === 1) { $values.first().trigger('click'); }
            });
            markUnavailable();

            $form.on('submit', function (e) {
                e.preventDefault();
                var submitter = e.originalEvent && e.originalEvent.submitter;
                var buyNow = submitter && submitter.name === 'buy_now';

                if ($groups.length && !$form.find('[data-variant-input]').val()) {
                    Efront.toast('Please choose ' + $groups.map(function () {
                        return $(this).find('.active').length ? null : $(this).find('.ef-option-label').contents().first().text().replace(':', '').trim();
                    }).get().filter(Boolean).join(' and ') + ' first.', 'error');
                    return;
                }

                var data = $form.serializeArray();
                if (buyNow) { data.push({ name: 'buy_now', value: 1 }); }
                var $button = $(submitter || $form.find('[data-add-to-cart]'));
                var html = $button.html();
                $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.post($form.attr('action'), $.param(data)).done(function (res) {
                    if (res.redirect) { window.location.href = res.redirect; return; }
                    refreshCart(res);
                    Efront.toast(res.message);
                    $button.html('<i class="fas fa-check"></i> Added!');
                    setTimeout(function () {
                        $button.html(html).prop('disabled', false);
                        closeQuickView();
                    }, 900);
                }).fail(function (xhr) {
                    $button.html(html).prop('disabled', false);
                    handleError(xhr);
                });
            });
        });
    }

    /* ---------- Wishlist ---------- */
    $(document).on('click', '[data-wishlist]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $button = $(this);
        var id = $button.data('wishlist');

        $.post(Efront.wishlistUrl + '/' + id).done(function (res) {
            $('[data-wishlist="' + id + '"]').each(function () {
                $(this).toggleClass('active', res.in_wishlist).attr('aria-pressed', res.in_wishlist)
                    .find('i').toggleClass('fas', res.in_wishlist).toggleClass('far', !res.in_wishlist);
            });
            setBadge('[data-wishlist-count]', res.count);
            if (!res.in_wishlist) { $('[data-wishlist-item="' + id + '"]').fadeOut(250); }
            Efront.toast(res.message);
        }).fail(handleError);
    });

    /* ---------- Cart (mini cart + cart page) ---------- */
    $(document).on('click', '[data-cart-remove]', function () {
        $.ajax({ url: $(this).data('cart-remove'), type: 'DELETE' }).done(function (res) {
            refreshCart(res);
            Efront.toast(res.message);
        }).fail(handleError);
    });

    var cartTimer = null;
    $(document).on('change', '[data-cart-update] input', function () {
        var $form = $(this).closest('form');
        clearTimeout(cartTimer);
        cartTimer = setTimeout(function () {
            $.post($form.attr('action'), $form.serialize()).done(refreshCart).fail(function (xhr) {
                handleError(xhr);
                setTimeout(function () { window.location.reload(); }, 1500);
            });
        }, 350);
    });

    $(document).on('submit', '[data-coupon-form]', function (e) {
        e.preventDefault();
        var $form = $(this);
        $.post($form.attr('action'), $form.serialize()).done(function (res) {
            refreshCart(res);
            Efront.toast(res.message);
        }).fail(handleError);
    });

    $(document).on('click', '[data-coupon-remove]', function () {
        $.ajax({ url: $(this).data('coupon-remove'), type: 'DELETE' }).done(function (res) {
            refreshCart(res);
            Efront.toast(res.message);
        }).fail(handleError);
    });

    /* ---------- Shop filters (AJAX) ---------- */
    var $filters = $('[data-ajax-filter]');

    function loadResults(url, push) {
        var $results = $('#shopResults').addClass('loading');
        $.ajax({ url: url, cache: false, headers: { Accept: 'text/html' } }).done(function (html) {
            $results.html(html);
            if (push) { history.pushState({ shop: true }, '', url); }
            var top = $results.offset().top - 110;
            if (window.scrollY > top) { window.scrollTo({ top: top, behavior: 'smooth' }); }
        }).fail(function () {
            window.location.href = url;
        }).always(function () {
            $results.removeClass('loading');
        });
    }

    function filterUrl() {
        var query = $filters.serializeArray().filter(function (field) { return field.value !== ''; });
        var params = $.param(query);
        return $filters.attr('action') + (params ? '?' + params : '');
    }

    if ($filters.length) {
        $filters.on('change', 'input[type=checkbox]', function () { loadResults(filterUrl(), true); });
        $filters.on('submit', function (e) {
            e.preventDefault();
            loadResults(filterUrl(), true);
            var panel = document.getElementById('shopFilterPanel');
            var instance = panel && window.bootstrap ? bootstrap.Offcanvas.getInstance(panel) : null;
            if (instance) { instance.hide(); }
        });
        $(document).on('change', '[data-sort]', function () {
            $filters.find('input[name=sort]').val(this.value);
            loadResults(filterUrl(), true);
        });
        $(document).on('click', '#shopResults .pagination a', function (e) {
            e.preventDefault();
            loadResults(this.href, true);
        });
        window.addEventListener('popstate', function (e) {
            if (e.state && e.state.shop) { loadResults(window.location.href, false); }
        });
        history.replaceState({ shop: true }, '', window.location.href);
    }

    /* ---------- Featured filter (home, Sarab menu filter) ---------- */
    $(document).on('click', '.filtbtn[data-f]', function () {
        var filter = String($(this).data('f'));
        $('.filtbtn[data-f]').removeClass('active');
        $(this).addClass('active');
        $('#mgrid .mwrap').each(function () {
            var show = filter === 'all' || String($(this).data('c')) === filter;
            $(this).toggleClass('gone', !show);
        });
    });

    /* ---------- Product gallery ---------- */
    $(document).on('click', '[data-gallery-thumb]', function () {
        var src = $(this).data('gallery-thumb');
        $(this).addClass('active').siblings().removeClass('active');
        $('[data-gallery-image]').attr('src', src);
        $('[data-gallery-main]').attr('href', src);
    });
    if ($.fn.magnificPopup) {
        $('[data-gallery-main]').magnificPopup({ type: 'image', mainClass: 'mfp-fade', closeOnContentClick: true });
    }
    $(document).on('click', '[data-tab-link]', function () {
        var trigger = document.querySelector('[data-tab="' + $(this).data('tab-link') + '"]');
        if (trigger && window.bootstrap) { bootstrap.Tab.getOrCreateInstance(trigger).show(); }
    });
    if (window.location.hash === '#reviews') { $('[data-tab-link="reviews"]').trigger('click'); }

    /* ---------- Countdown ---------- */
    function initCountdowns($scope) {
        $scope.find('[data-countdown]').each(function () {
            var $box = $(this);
            var end = new Date($box.data('countdown')).getTime();
            function tick() {
                var left = Math.max(0, end - Date.now());
                var parts = {
                    d: Math.floor(left / 86400000),
                    h: Math.floor(left / 3600000) % 24,
                    m: Math.floor(left / 60000) % 60,
                    s: Math.floor(left / 1000) % 60
                };
                $.each(parts, function (key, value) { $box.find('[data-cd="' + key + '"]').text(String(value).padStart(2, '0')); });
                return left > 0;
            }
            tick();
            var timer = setInterval(function () { if (!tick()) { clearInterval(timer); } }, 1000);
        });
    }

    /* ---------- Sliders ---------- */
    if (window.Swiper) {
        if ($('.heroSwiper').length) {
            new Swiper('.heroSwiper', {
                loop: $('.heroSwiper .swiper-slide').length > 1,
                autoplay: { delay: 5000, disableOnInteraction: false },
                pagination: { el: '.heroSwiper .swiper-pagination', clickable: true },
                navigation: { nextEl: '.heroSwiper .swiper-button-next', prevEl: '.heroSwiper .swiper-button-prev' }
            });
        }
        if ($('.productSwiper').length) {
            new Swiper('.productSwiper', {
                slidesPerView: 2, spaceBetween: 16,
                pagination: { el: '.productSwiper .swiper-pagination', clickable: true },
                breakpoints: { 768: { slidesPerView: 3, spaceBetween: 22 }, 1200: { slidesPerView: 4, spaceBetween: 24 } }
            });
        }
        if ($('.brandSwiper').length) {
            new Swiper('.brandSwiper', {
                slidesPerView: 3, spaceBetween: 16, loop: $('.brandSwiper .swiper-slide').length > 6,
                autoplay: { delay: 2500, disableOnInteraction: false },
                breakpoints: { 768: { slidesPerView: 5 }, 1200: { slidesPerView: 6 } }
            });
        }
        if ($('.tesSwiper').length) {
            new Swiper('.tesSwiper', {
                slidesPerView: 1, spaceBetween: 22, loop: $('.tesSwiper .swiper-slide').length > 3,
                autoplay: { delay: 4000, disableOnInteraction: false },
                pagination: { el: '.tesSwiper .swiper-pagination', clickable: true },
                breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }
            });
        }
    }

    /* ---------- Checkout totals + payment info ---------- */
    var $checkout = $('[data-checkout]');
    if ($checkout.length) {
        var $total = $checkout.find('[data-total-amount]');
        var symbol = $total.data('symbol');

        var updateTotals = function () {
            var $zone = $checkout.find('input[name=shipping_zone_id]:checked');
            var charge = $zone.length ? parseFloat($zone.data('charge')) : 0;
            var discount = $zone.length ? parseFloat($zone.data('discount')) || 0 : 0;
            $checkout.find('[data-shipping-amount]').text($zone.length ? (charge > 0 ? money(charge, symbol) : 'Free') : '—');
            $checkout.find('[data-shipping-discount]').text(discount > 0 ? '(' + ($zone.data('rule') || 'discount') + ': ' + money(discount, symbol) + ' off)' : '');
            $total.text(money(parseFloat($total.data('base')) + charge, symbol));
        };

        var updatePayment = function () {
            var method = $checkout.find('[data-payment-method]:checked').val();
            $checkout.find('[data-payment-info]').each(function () {
                var active = $(this).data('payment-info') === method;
                $(this).prop('hidden', !active).find('input').prop('disabled', !active);
            });
        };

        $checkout.on('change', 'input[name=shipping_zone_id]', updateTotals);
        $checkout.on('change', '[data-payment-method]', updatePayment);
        $checkout.on('submit', function () {
            $(this).find('button[type=submit]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Placing order…');
        });
        updateTotals();
        updatePayment();
    }

    /* ---------- Address: Division → District → Upazila (metheme /api/geo) ---------- */
    var geoBase = Efront.geoUrl;
    var divisionsRequest = null;

    function geoFill($select, items) {
        var selected = String($select.data('selected') || '');
        var placeholder = { division: 'Select division', district: 'Select district', upazila: 'Select upazila / thana' }[$select.data('geo-level')];
        $select.empty().append($('<option>', { value: '', text: placeholder }));
        $.each(items, function (_, item) {
            $select.append($('<option>', { value: item.id, text: item.name, selected: String(item.id) === selected }));
        });
        $select.prop('disabled', !items.length);
        if (selected && $select.val() === selected) {
            $select.data('selected', '');
            $select.trigger('change');
        }
    }

    function geoReset($select, text) {
        $select.empty().append($('<option>', { value: '', text: text })).prop('disabled', true);
    }

    function loadDivisions() {
        if (!divisionsRequest) {
            divisionsRequest = $.getJSON(geoBase + '/countries').then(function (res) {
                return res.data.length ? $.getJSON(geoBase + '/' + res.data[0].id + '/children') : { data: [] };
            });
        }
        return divisionsRequest;
    }

    function initGeoFields($scope) {
        $scope.find('[data-geo-fields]').each(function () {
            var $box = $(this);
            if ($box.data('ready')) { return; }
            $box.data('ready', true);

            var $division = $box.find('[data-geo-level=division]');
            var $district = $box.find('[data-geo-level=district]');
            var $upazila = $box.find('[data-geo-level=upazila]');

            $division.on('change', function () {
                geoReset($district, 'Select division first');
                geoReset($upazila, 'Select district first');
                if (this.value) {
                    $.getJSON(geoBase + '/' + this.value + '/children', function (res) { geoFill($district, res.data); });
                }
            });
            $district.on('change', function () {
                geoReset($upazila, 'Select district first');
                if (this.value) {
                    $.getJSON(geoBase + '/' + this.value + '/children', function (res) { geoFill($upazila, res.data); });
                }
            });

            loadDivisions().done(function (res) { geoFill($division, res.data); });
        });
    }

    /* Saved address picker: "Use a new address" opens the form */
    $(document).on('change', '[data-address-picker] input[type=radio]', function () {
        $(this).closest('[data-address-picker]').find('[data-address-new]').prop('hidden', this.value !== 'new');
    });

    /* Billing address same as shipping */
    $(document).on('change', '[data-billing-same]', function () {
        $('[data-billing-section]').prop('hidden', this.checked);
    });

    /* ---------- Profile photo: preview and upload on pick ---------- */
    $(document).on('change', '[data-avatar-input]', function () {
        var file = this.files && this.files[0];
        if (!file) { return; }
        var $preview = $('[data-avatar-preview]');
        var reader = new FileReader();
        reader.onload = function (e) {
            var $img = $('<img>', { src: e.target.result, alt: '', 'class': 'ef-avatar ef-avatar-img ef-avatar-xl', 'data-avatar-preview': '' });
            $preview.replaceWith($img);
        };
        reader.readAsDataURL(file);
        $(this).closest('form').trigger('submit');
    });

    /* ---------- OTP resend countdown ---------- */
    $('[data-resend-in]').each(function () {
        var $button = $(this);
        var $timer = $button.siblings('[data-resend-timer]');
        var left = parseInt($button.data('resend-in'), 10) || 0;
        function tick() {
            $button.prop('disabled', left > 0);
            $timer.text(left > 0 ? '(' + left + 's)' : '');
            if (left-- > 0) { setTimeout(tick, 1000); }
        }
        tick();
    });
    $(document).on('input', '.ef-otp-input', function () { this.value = this.value.replace(/\D/g, '').slice(0, 6); });

    /* ---------- FAQ: category filter + search ---------- */
    var faqFilter = 'all';
    function applyFaq() {
        var term = $.trim($('[data-faq-search]').val() || '').toLowerCase();
        var shown = 0;
        $('[data-faq-group]').each(function () {
            var $group = $(this);
            var inCategory = faqFilter === 'all' || $group.data('faq-group') === faqFilter;
            var visible = 0;
            $group.find('[data-faq-item]').each(function () {
                var match = inCategory && (!term || $(this).text().toLowerCase().indexOf(term) !== -1);
                $(this).toggleClass('d-none', !match);
                if (match) { visible++; }
            });
            $group.toggleClass('d-none', !visible);
            shown += visible;
        });
        $('[data-faq-empty]').toggleClass('d-none', shown > 0);
    }
    $(document).on('input', '[data-faq-search]', applyFaq);
    $(document).on('click', '[data-faq-filter]', function () {
        faqFilter = String($(this).data('faq-filter'));
        $('[data-faq-filter]').removeClass('active');
        $(this).addClass('active');
        applyFaq();
    });

    /* ---------- 429 page countdown ---------- */
    $('[data-retry-in]').each(function () {
        var $box = $(this);
        var left = parseInt($box.data('retry-in'), 10) || 0;
        var timer = setInterval(function () {
            left--;
            if (left <= 0) {
                clearInterval(timer);
                $box.html('<i class="fas fa-check-circle me-1"></i>You can try again now.');
                return;
            }
            $box.find('strong').text(left);
        }, 1000);
    });

    initPurchaseForms($(document));
    initCountdowns($(document));
    initGeoFields($(document));
})(jQuery);
