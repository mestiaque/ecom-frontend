<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use ME\Efront\Http\Controllers\Account\AccountController;
use ME\Efront\Http\Controllers\Account\AddressController;
use ME\Efront\Http\Controllers\Account\AuthController;
use ME\Efront\Http\Controllers\CartController;
use ME\Efront\Http\Controllers\CheckoutController;
use ME\Efront\Http\Controllers\FaqController;
use ME\Efront\Http\Controllers\HomeController;
use ME\Efront\Http\Controllers\InvoiceController;
use ME\Efront\Http\Controllers\PageController;
use ME\Efront\Http\Controllers\PaymentController;
use ME\Efront\Http\Controllers\ProductController;
use ME\Efront\Http\Controllers\ReviewController;
use ME\Efront\Http\Controllers\ShopController;
use ME\Efront\Http\Controllers\WishlistController;

// Storefront. URL prefix from config('efront.route_prefix') (.env EFRONT_ROUTE_PREFIX); route names start with "efront.".
Route::middleware('web')->prefix(config('efront.route_prefix'))->name('efront.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Catalog
    Route::get('/shop', [ShopController::class, 'index'])->name('shop');
    Route::get('/category/{category:slug}', [ShopController::class, 'category'])->name('category');
    Route::get('/brand/{brand:slug}', [ShopController::class, 'brand'])->name('brand');
    Route::get('/campaign/{campaign:slug}', [ShopController::class, 'campaign'])->name('campaign');
    Route::get('/search/suggest', [ShopController::class, 'suggest'])->middleware('throttle:efront-search')->name('search.suggest');
    Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product');
    Route::get('/product/{product:slug}/quick-view', [ProductController::class, 'quickView'])->name('product.quick-view');
    Route::post('/product/{product:slug}/reviews', [ReviewController::class, 'store'])->middleware(['efront.auth', 'throttle:efront-review'])->name('reviews.store');

    // Cart & checkout (guests can order too)
    Route::get('/cart', [CartController::class, 'index'])->name('cart');
    Route::get('/cart/mini', [CartController::class, 'mini'])->name('cart.mini');
    Route::post('/cart', [CartController::class, 'store'])->middleware('throttle:efront-cart')->name('cart.store');
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->middleware('throttle:efront-coupon')->name('cart.coupon');
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
    Route::patch('/cart/{key}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{key}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:efront-checkout')->name('checkout.store');
    Route::get('/checkout/success/{order:order_number}', [CheckoutController::class, 'success'])->middleware('signed')->name('checkout.success');
    Route::get('/order/{order:order_number}/invoice', [InvoiceController::class, 'show'])->middleware('signed')->name('invoice');

    // Online payment (bKash / SSLCommerz). Pay & switch links are signed; see PaymentController
    Route::get('/payment/{order:order_number}/pay', [PaymentController::class, 'pay'])->middleware(['signed', 'throttle:efront-checkout'])->name('payment.pay');
    Route::post('/payment/{order:order_number}/cod', [PaymentController::class, 'switchToCod'])->middleware('signed')->name('payment.cod');
    Route::get('/payment/done/{transaction}/{token}', [PaymentController::class, 'done'])->name('payment.done');

    // Content
    Route::get('/page/{page:slug}', [PageController::class, 'show'])->name('page');
    Route::get('/faq', [FaqController::class, 'index'])->name('faq');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::post('/contact', [PageController::class, 'sendContact'])->middleware('throttle:efront-contact')->name('contact.send');

    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->middleware('efront.auth')->name('wishlist.toggle');

    // Customer account
    Route::prefix('account')->name('account.')->group(function () {
        Route::middleware('efront.guest')->group(function () {
            Route::get('/login', [AuthController::class, 'login'])->name('login');
            Route::post('/login', [AuthController::class, 'loginStore'])->middleware('throttle:efront-login')->name('login.store');
            Route::get('/register', [AuthController::class, 'register'])->name('register');
            Route::post('/register', [AuthController::class, 'registerStore'])->middleware('throttle:efront-register')->name('register.store');
            Route::get('/register/verify', [AuthController::class, 'verify'])->name('register.verify');
            Route::post('/register/verify', [AuthController::class, 'verifyStore'])->middleware('throttle:efront-verify')->name('register.verify.store');
            Route::post('/register/resend', [AuthController::class, 'resend'])->middleware('throttle:efront-resend')->name('register.resend');
            Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.forgot');
            Route::post('/forgot-password', [AuthController::class, 'forgotPasswordStore'])->middleware('throttle:efront-password')->name('password.email');
            Route::get('/reset-password/{token}', [AuthController::class, 'resetPassword'])->name('password.reset');
            Route::post('/reset-password', [AuthController::class, 'resetPasswordStore'])->middleware('throttle:efront-password')->name('password.update');
        });

        Route::middleware('efront.auth')->group(function () {
            Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
            Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
            Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
            Route::put('/password', [AccountController::class, 'updatePassword'])->name('password.change');
            Route::post('/avatar', [AccountController::class, 'updateAvatar'])->name('avatar.update');
            Route::delete('/avatar', [AccountController::class, 'removeAvatar'])->name('avatar.remove');
            Route::get('/addresses', [AddressController::class, 'index'])->name('addresses');
            Route::get('/addresses/create', [AddressController::class, 'create'])->name('addresses.create');
            Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
            Route::get('/addresses/{address}/edit', [AddressController::class, 'edit'])->name('addresses.edit');
            Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
            Route::patch('/addresses/{address}/default', [AddressController::class, 'makeDefault'])->name('addresses.default');
            Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
            Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
            Route::get('/orders/{order:order_number}', [AccountController::class, 'showOrder'])->name('orders.show');
            Route::patch('/orders/{order:order_number}/cancel', [AccountController::class, 'cancelOrder'])->name('orders.cancel');
            Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
    });
});

// Unknown URLs: run the web middleware (session, logged-in customer, cart) before the 404 page.
// Support/ErrorPages still sends admin URLs to metheme's page.
Route::middleware('web')->group(function () {
    Route::fallback(fn () => abort(404))->name('efront.fallback');
});

// Gateway return URL — outside the "web" group on purpose: no session (a cross-site POST would replace the customer's
// session cookie) and no CSRF token (the gateway posts it). Protected by the secret token of the payment try.
Route::match(['get', 'post'], trim(config('efront.route_prefix'), '/').'/payment/callback/{transaction}/{token}/{result?}', [PaymentController::class, 'callback'])
    ->middleware(SubstituteBindings::class)
    ->whereIn('result', ['success', 'fail', 'cancel'])
    ->name('efront.payment.callback');
