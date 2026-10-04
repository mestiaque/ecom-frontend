# Efront

`mestiaque/efront` — ecom প্যাকেজের দোকানের **স্টোরফ্রন্ট** (কাস্টমার যেটা দেখে)। ডিজাইন Sarab থিম থেকে নেওয়া, ডেটা আসে ecom প্যাকেজ থেকে (প্রোডাক্ট, ক্যাটাগরি, ব্যানার, ক্যাম্পেইন, কুপন, শিপিং, অর্ডার)।

## প্রয়োজন

- `mestiaque/metheme` (সেটিং, মেইল/এসএমএস, geo API)
- `mestiaque/ecom` (সব দোকানের ডেটা ও `OrderService`)

## ইনস্টলেশন

```bash
composer require mestiaque/efront
php artisan migrate
php artisan vendor:publish --tag=efront-assets     # public/efront (css, js, fonts, img)
php artisan metheme:sync-geo-locations             # চেকআউটে বিভাগ → জেলা → উপজেলা
php artisan storage:link                           # প্রোডাক্ট/ব্যানার ছবি
```

`.env` (ঐচ্ছিক):

```env
EFRONT_ROUTE_PREFIX=          # খালি = সাইটের রুট (/), যেমন "store" দিলে /store
EFRONT_BANNER_TEXT=false      # true = ব্যানারের title/subtitle/button ছবির উপর লেখা হবে
EFRONT_OTP_PHONE=true         # রেজিস্ট্রেশনে মোবাইলে OTP
EFRONT_OTP_EMAIL=true         # রেজিস্ট্রেশনে ইমেইলে OTP (true হলে ইমেইল বাধ্যতামূলক)
```

অন্য publish ট্যাগ: `efront-config` (config/efront.php — per_page, new_badge_days, max_quantity, hero টেক্সট)।

---

## পেজ

| পেজ | URL | Route নাম |
|---|---|---|
| হোম | `/` | `efront.home` |
| শপ (ফিল্টার, সার্চ `?q=`) | `/shop` | `efront.shop` |
| ক্যাটাগরি / ব্র্যান্ড / ক্যাম্পেইন | `/category/{slug}`, `/brand/{slug}`, `/campaign/{slug}` | `efront.category`, `efront.brand`, `efront.campaign` |
| প্রোডাক্ট ডিটেইলস (+ কুইক ভিউ পপআপ) | `/product/{slug}` | `efront.product` |
| কার্ট / চেকআউট | `/cart`, `/checkout` | `efront.cart`, `efront.checkout` |
| স্ট্যাটিক পেজ (ecom Pages) | `/page/{slug}` | `efront.page` |
| যোগাযোগ | `/contact` | `efront.contact` |
| FAQ (অ্যাডমিন থেকে) | `/faq` | `efront.faq` |
| লগইন / রেজিস্টার / পাসওয়ার্ড ভুলে গেছি | `/account/login`, `/account/register`, `/account/forgot-password` | `efront.account.*` |
| রেজিস্ট্রেশন OTP যাচাই | `/account/register/verify` | `efront.account.register.verify` |
| ড্যাশবোর্ড, প্রোফাইল (ছবি + পাসওয়ার্ড), অর্ডার, উইশলিস্ট | `/account`, `/account/profile`, `/account/orders`, `/account/wishlist` | `efront.account.*` |
| ঠিকানা বই | `/account/addresses` | `efront.account.addresses*` |
| অর্ডার ট্র্যাকিং (গেস্ট) | `/track-order` | `ecom.track.form` (ecom-এর route, efront থিমে) |

ব্যানারের লিংক (`/category/women`, `/campaign/mega-flash-sale`, `/page/shipping-delivery`) সরাসরি এই URL গুলোতে যায়।

## নিয়মাবলি

### ১. লেআউট ও কম্পোনেন্ট

1. সব পেজ `@extends('efront::layouts.app')`। অংশগুলো `resources/views/partials/` এ — `topbar`, `navbar`, `search` (সার্চ ওভারলে), `footer`, `mini-cart`, `logo`।
2. বারবার লাগে এমন জিনিস Blade কম্পোনেন্ট — নতুন পেজে এগুলোই ব্যবহার করুন, নতুন করে HTML লিখবেন না:

| কম্পোনেন্ট | কাজ |
|---|---|
| `<x-efront::product-card :product="$p" />` | গ্রিড কার্ড (ব্যাজ, উইশলিস্ট, কুইক ভিউ, রেটিং) |
| `<x-efront::product-mini-card :product="$p" />` | ছোট আড়াআড়ি কার্ড (সার্চ সাজেশন, বেস্ট সেলার) |
| `<x-efront::price :product="$p" />`, `<x-efront::rating :rating="4.5" :count="10" />` | দাম (ক্যাম্পেইনসহ), স্টার |
| `<x-efront::section-title label=".." title=".." highlight=".." />` | সেকশন শিরোনাম |
| `<x-efront::page-header title=".." :breadcrumbs="[...]" />` | পেজের উপরের ব্যানার + breadcrumb |
| `<x-efront::avatar :customer="$c" size="lg" />` | কাস্টমারের ছবি, ছবি না থাকলে নামের প্রথম অক্ষর (`ef-avatar`) |
| `category-card`, `empty`, `order-status`, `qty`, `wishlist-button`, `product-badge` | বাকি ছোট অংশ |

ঠিকানার ফর্ম সবসময় `@include('efront::partials.address-fields', ['prefix' => 'shipping', 'address' => $a])` — বিভাগ → জেলা → উপজেলা, পোস্ট অফিস, পোস্টাল কোড, ঠিকানা লেখার ঘর একসাথে আসে।

3. প্রোডাক্ট কার্ডের জন্য query সবসময় `efront()->products()` দিয়ে শুরু করুন — এতে ছবি, ক্যাটাগরি, ক্যাম্পেইন, রেটিং একসাথে লোড হয় (N+1 হয় না)।
4. `efront()` হেল্পার: `customer()`, `storeName()`, `logoUrl()`, `setting()`, `menuCategories()`, `wishlistIds()`, `cartCount()`, `paymentMethods()`, `freeShippingMin()`, `socialLinks()`।
5. দোকানের নাম, ফোন, ঠিকানা, সোশ্যাল লিংক, লোগো — সব ecom-এর **Store Info** সেটিং থেকে আসে, ভিউতে হাতে লিখবেন না।
6. **FAQ ডাইনামিক:** প্রশ্ন-উত্তর অ্যাডমিন প্যানেলের **Website → FAQ** থেকে যোগ/এডিট হয় (ecom `ecom_faqs` টেবিল)। `/faq` পেজে ক্যাটাগরি অনুযায়ী accordion, ক্যাটাগরি ট্যাব আর সার্চ; শুধু Published গুলো দেখায়, উত্তর `Html::clean()` দিয়ে। পুরোনো `/page/faq` লিংক `/faq` এ 301 রিডাইরেক্ট হয়, ফুটারে FAQ লিংক আছে।
7. ব্যানার ছবিতে লেখা আগে থেকেই থাকে ধরে নিয়ে শুধু ছবি দেখানো হয়; লেখা ছাড়া ছবি হলে `EFRONT_BANNER_TEXT=true` দিন।

### ২. কাস্টমার লগইন

1. কাস্টমার আলাদা গার্ড **`customer`** দিয়ে লগইন করে (`ecom_customers` টেবিল, মডেল `ME\Efront\Models\Customer`)। অ্যাডমিন (`web` গার্ড, `users` টেবিল) আলাদা — দুজনে একই ব্রাউজারে একসাথে লগইন থাকতে পারে।
2. কাস্টমার লাগবে এমন route-এ `efront.auth` middleware দিন, লগইন/রেজিস্টার পেজে `efront.guest`। কোডে `efront()->customer()` ব্যবহার করুন, `auth()->user()` নয় (ওটা অ্যাডমিন দেয়)।
3. লগইন **মোবাইল নম্বর বা ইমেইল** + পাসওয়ার্ড দিয়ে। নম্বর সবসময় `01XXXXXXXXX` আকারে সেভ হয় (`ME\Efront\Support\Phone::normalize()` — +88, স্পেস, ড্যাশ বাদ)।
4. ব্লক করা কাস্টমার (অ্যাডমিন Customers পেজ) লগইন করতে পারে না, লগইন থাকলে বের করে দেওয়া হয়।
5. **রেজিস্ট্রেশনে OTP:** ফর্ম জমা দিলে অ্যাকাউন্ট তখনই তৈরি হয় না — মোবাইলে (SMS) আর ইমেইলে আলাদা ৬ সংখ্যার কোড যায়, দুটোই মিললে তবেই অ্যাকাউন্ট তৈরি হয় আর `phone_verified_at` / `email_verified_at` সেট হয়। কোড সেশনে hash করে রাখা, ১০ মিনিট মেয়াদ, ৫ বার ভুল হলে নতুন কোড লাগবে, আবার পাঠানো যায় ৬০ সেকেন্ড পর (`config('efront.registration_otp')`)।
6. SMS বা মেইল পাঠানো না গেলে রেজিস্ট্রেশন আটকে যায় (ভুল বার্তা দেখায়)। শুধু **local** পরিবেশে SMS/মেইল সেট না থাকলে কোডটা `storage/logs/laravel.log` এ লেখা হয়, যাতে ডেভেলপমেন্টে টেস্ট করা যায়। পাঠানোর কাজ `ME\Efront\Support\OtpSender` এ — টেস্টে এটাকে mock করুন।
7. প্রোফাইলে মোবাইল/ইমেইল বদলালে সেটা আর verified থাকে না।
8. **প্রোফাইল ছবি:** `/account/profile` থেকে আপলোড/মুছে ফেলা (JPG/PNG/WebP, `efront.avatar_max_kb` ডিফল্ট ২ MB), `public` ডিস্কে `ecom/customers/` এ সেভ হয়, পুরোনো ছবি মুছে যায়। লগইন থাকলে হেডারে অ্যাকাউন্ট আইকনের জায়গায় ছবি বা প্রথম অক্ষর দেখায়।
9. পাসওয়ার্ড রিসেট লিংক যায় **ইমেইলে** (metheme মেইল সেটিং দিয়ে, টোকেন `efront_password_reset_tokens` টেবিলে, ৬০ মিনিট)। ইমেইল না থাকলে কাস্টমারকে যোগাযোগ করতে বলা হয়।

### ৩. কার্ট ও চেকআউট

1. কার্ট **সেশনে** থাকে (`ME\Efront\Support\Cart`) — শুধু id আর পরিমাণ। দাম, স্টক আর ক্যাম্পেইন ডিসকাউন্ট প্রতিবার ডাটাবেস থেকে নতুন করে পড়া হয়, তাই পুরোনো দাম কখনো দেখায় না।
2. স্টকের বেশি বা `efront.max_quantity` (ডিফল্ট ২০) এর বেশি একটা আইটেম নেওয়া যায় না। ভ্যারিয়েন্ট প্রোডাক্টে অপশন না বাছলে কার্টে যায় না।
3. অর্ডার সবসময় ecom-এর `OrderService::place()` দিয়ে হয় — স্টক কমানো, কুপন, শিপিং চার্জ, অর্ডার নম্বর সব ওখানে। চেকআউটে আলাদা করে হিসাব করবেন না।
4. গেস্ট অর্ডার করতে পারে (`customer_id` = null)। লগইন থাকলে অর্ডার কাস্টমারের সাথে যুক্ত হয়।
5. **ডেলিভারি চার্জ** অ্যাডমিন প্যানেল থেকে সেট হয়, হিসাব হয় ecom-এর `ShippingCalculator` দিয়ে: জোনের চার্জ + প্রোডাক্টের বাড়তি/কম চার্জ × পরিমাণ (প্রোডাক্ট → Delivery), কার্টের সব প্রোডাক্ট "Free delivery" হলে ফ্রি, তারপর অর্ডার অ্যামাউন্ট অনুযায়ী ছাড় (Shipping → Delivery Discounts: ফ্রি / % ছাড় / নির্দিষ্ট টাকা ছাড়, সব জোনে বা একটা জোনে)। চেকআউটে প্রতিটা জোনের পাশে এই কার্টের আসল চার্জ দেখায় (ছাড় থাকলে আগের দাম কাটা), সামারিতে কত ছাড় পেল সেটাও। কার্টে "Free delivery" / "+৳X delivery per item" নোট, প্রোডাক্ট কার্ডে "Free" ব্যাজ, প্রোডাক্ট পেজে ডেলিভারি তথ্য। "Free delivery over ৳X" লেখা আসে সব-জোনের সবচেয়ে কম "free" রুল থেকে (`efront()->freeShippingMin()`)। চেকআউটে আলাদা করে চার্জ হিসাব করবেন না।
6. পেমেন্ট মেথড দেখায় শুধু যেগুলো **Payment Methods** পেজে চালু। অনলাইন গেটওয়ে (bKash/Nagad/SSLCommerz) রিডাইরেক্ট এখনো নেই — কাস্টমার চাইলে Transaction ID দেয়, সেটা অর্ডার নোটে যায়, অ্যাডমিন হাতে পেমেন্ট এন্ট্রি দেয়।
7. অর্ডারের পর কাস্টমারকে এসএমএস (আর ইমেইল থাকলে মেইল) যায় ট্র্যাকিং লিংকসহ; সাকসেস পেজটা signed লিংক (২৪ ঘণ্টা)।
8. চেকআউটের ধাপ: **১ শিপিং ঠিকানা → ২ বিলিং ঠিকানা → ৩ ডেলিভারি এরিয়া → ৪ পেমেন্ট।** লগইন থাকলে সেভ করা ঠিকানা কার্ড হিসেবে আসে (ডিফল্টটা আগে থেকে বাছা), নয়তো "Use a new address"; নতুন ঠিকানা "Save this address" টিক দিলে ঠিকানা বইয়ে জমা হয়।
9. বিলিং ঠিকানা ডিফল্টে "Same as shipping"। আলাদা দিলে অর্ডারের `billing_address` কলামে পুরো লেখা যায় (নাম, ফোন, ঠিকানা); একই হলে `null`। অ্যাডমিন অর্ডার পেজ ও ইনভয়েসে দেখায়।
10. অর্ডারে যায়: `customer_name` / `customer_phone` = শিপিং ঠিকানার নাম/ফোন, `shipping_address` = ঠিকানা + পোস্ট অফিস + পোস্টাল কোড, `city` = "উপজেলা, জেলা, বিভাগ"।

### ৪. ঠিকানা বই

1. ঠিকানা আলাদা টেবিলে — `efront_addresses` (মডেল `ME\Efront\Models\Address`): `type` (`shipping` / `billing`), `label` (Home, Office…), `name`, `phone`, `division_id` / `district_id` / `upazila_id` (metheme `geo_locations`), `post_office`, `postal_code`, `address_line`, `is_default`।
2. একজন কাস্টমারের যত খুশি ঠিকানা থাকতে পারে; **প্রতিটা ধরনে একটাই ডিফল্ট।** প্রথম ঠিকানাটা নিজে থেকেই ডিফল্ট; ডিফল্ট মুছলে পরেরটা ডিফল্ট হয়।
3. ঠিকানা সেভ করতে `$customer->saveAddress($address, $makeDefault)`, ডিফল্ট বদলাতে `$address->makeDefault()` — হাতে `is_default` বদলাবেন না। ডিফল্ট শিপিং ঠিকানা কাস্টমারের `address` / `city` তেও কপি হয় (অ্যাডমিন Customers পেজে দেখায়)।
4. যাচাই: `Address::rules($prefix)` + `Address::fromInput($data)->hasValidArea()` — উপজেলা সেই জেলার, জেলা সেই বিভাগের কিনা চেক হয়। বিভাগ/জেলা/উপজেলা বাধ্যতামূলক, তাই `php artisan metheme:sync-geo-locations` চালানো থাকতে হবে।
5. লেখার রূপ: `$address->street` (ঠিকানা + পোস্ট অফিস + কোড), `$address->area` (উপজেলা, জেলা, বিভাগ), `$address->full` (দুটো একসাথে)।
6. কাস্টমার শুধু নিজের ঠিকানা দেখতে/বদলাতে পারে — অন্যেরটায় 404; চেকআউটেও অন্যের ঠিকানার id দিলে বাতিল।

### ৫. অর্ডার ট্র্যাকিং

1. পাবলিক ট্র্যাকিং পেজ (`/track-order` ফর্ম আর SMS-এর signed লিংক) ecom প্যাকেজের route/controller-ই, শুধু ভিউ efront থিমে — efront `resources/views/overrides/ecom/tracking/` ফোল্ডারটা `ecom` ভিউ namespace-এর আগে বসায় (`View::prependNamespace`)। ট্র্যাকিংয়ের নিয়ম বদলাতে ecom-এর `TrackingController` দেখুন, চেহারা বদলাতে efront-এর এই ভিউ।
2. **লগইন করা কাস্টমারের আলাদা ট্র্যাকিং পেজ নেই।** "Track Order" লিংক (`efront()->trackUrl()`) তাদের "My Orders" এ নেয়; `/track-order` খুললেও সেখানে পাঠানো হয়; নিজের অর্ডারের SMS লিংক খুললে অ্যাকাউন্টের অর্ডার পেজে যায় (`TrackOrderForCustomer` middleware, `RouteMatched` ইভেন্টে ecom-এর ট্র্যাকিং route-এ যোগ হয়)।
3. অ্যাকাউন্টের অর্ডার পেজেই পুরো ট্র্যাকিং: ধাপ (Order placed → Confirmed → Packed → On the way → Delivered, তারিখসহ), কুরিয়ার + ট্র্যাকিং আইডি + কুরিয়ার সাইটের লিংক, স্ট্যাটাস হিস্ট্রি। একই অংশ `@include('efront::partials.order-tracking')` — পাবলিক পেজেও এটাই ব্যবহার হয়। অ্যাডমিনের ভিতরের কমেন্ট কখনো দেখায় না।

### ৬. অ্যাকাউন্ট

1. কাস্টমার শুধু **নিজের** অর্ডার দেখে — অন্যেরটা খুললে 404।
2. শুধু **Pending** অর্ডার কাস্টমার নিজে বাতিল করতে পারে; বাতিল হলে স্টক ফেরত যায় (`OrderService::changeStatus`)।
3. উইশলিস্ট (`efront_wishlists`) ও রিভিউ দিতে লগইন লাগে। গেস্ট হার্টে চাপলে লগইন পেজে যায়।
4. রিভিউ একজন কাস্টমার একটা প্রোডাক্টে একবারই দিতে পারে, আর অ্যাডমিন **Reviews** পেজে approve না করা পর্যন্ত দেখায় না।

### ৭. AJAX ও JavaScript

1. সব আচরণ `public/efront/js/efront.js` এ, `data-*` অ্যাট্রিবিউট দিয়ে: `data-quick-view`, `data-wishlist`, `data-purchase-form`, `data-cart-remove`, `data-cart-update`, `data-coupon-form`, `data-ajax-filter`, `data-countdown="ISO তারিখ"`, `data-search-open`, `data-qty`, `data-geo-fields` (ভিতরে `data-geo-level="division|district|upazila"` + `data-selected`), `data-address-picker`, `data-billing-same`, `data-avatar-input`, `data-faq-search` / `data-faq-filter`, `data-toggle-password` (পাসওয়ার্ড দেখানো/লুকানো চোখের বোতাম)।
2. কার্ট/উইশলিস্ট এন্ডপয়েন্ট AJAX হলে JSON দেয় (`count`, `mini` = মিনি কার্ট HTML, `cart` = কার্ট পেজ HTML), সাধারণ ফর্ম হলে back redirect — দুটোই কাজ করে।
3. শপের ফিল্টার/সর্ট/পেজিনেশন AJAX এ শুধু রেজাল্ট অংশ বদলায় আর URL আপডেট করে (`history.pushState`), তাই লিংক শেয়ার/রিফ্রেশ করলে একই ফল আসে।
4. টোস্ট মেসেজ: JS এ `Efront.toast('লেখা', 'error')`; কন্ট্রোলার থেকে `->with('success', ...)` / `->with('error', ...)` দিলে নিজে থেকেই দেখায়।
5. CSS/JS বদলালে আবার `php artisan vendor:publish --tag=efront-assets --force` চালান।

### ৮. এরর পেজ

1. **জায়গা অনুযায়ী আলাদা এরর পেজ:** স্টোরফ্রন্টের পেজ (`efront.*`, `ecom.track.*` route আর অচেনা URL) এ efront থিমের পেজ; অ্যাডমিন (`me_prefix()` এর নিচে, যেমন `/admin/...`) আর metheme-এর লগইন/রেজিস্টার পেজে **metheme-এর পেজই** (অ্যাপের `resources/views/errors/`) — metheme-এ কিছু বদলানো হয়নি।
2. কাজটা `ME\Efront\Support\ErrorPages` করে, `EfrontServiceProvider` এটাকে Laravel-এর exception handler-এ `renderable()` দিয়ে লাগায়। স্টোরফ্রন্ট না হলে `null` ফেরত দেয়, তখন Laravel নিজের মতো (metheme-এর পেজ) দেখায়।
3. **403, 404, 419, 429:** হেডার-ফুটারসহ পুরো লেআউটে (`efront::errors.page`) — 404-এ প্রোডাক্ট সার্চ, 429-এ কত সেকেন্ড পর আবার চেষ্টা করা যাবে (কাউন্টডাউন), 403-এ লগইন বাটন। **500, 503:** ডাটাবেস ছাড়া সাধারণ পেজ (`efront::errors.minimal`) — ডাটাবেস নষ্ট হলেও যেন দেখাতে পারে; পুরো লেআউট বানাতে গিয়ে ভাঙলেও এটাই দেখায়।
4. `APP_DEBUG=true` থাকলে 500-এ Laravel-এর ডিবাগ স্ক্রিন দেখায় (ডেভেলপমেন্টে এরর লুকায় না)। JSON / AJAX রিকোয়েস্টে আগের মতো JSON এরর।
5. ফর্ম জমা দেওয়ার সময় সেশন শেষ (419) হলে এরর পেজ না দেখিয়ে ফর্মে ফেরত যায়, লেখা তথ্য থাকে (পাসওয়ার্ড বাদে), "Your session expired" মেসেজসহ।
6. অচেনা URL-এর জন্য efront একটা `Route::fallback` (`efront.fallback`) রাখে, যাতে 404 পেজেও সেশন চালু থাকে — লগইন করা কাস্টমারের ছবি, কার্টের সংখ্যা ঠিক দেখায়। অন্য কোথাও আরেকটা `Route::fallback` বানাবেন না।

### ৯. নিরাপত্তা

1. অ্যাডমিনের Summernote লেখা (প্রোডাক্ট ডেসক্রিপশন, পেজ) সবসময় `ME\Efront\Support\Html::clean()` দিয়ে দেখান — script, iframe, `on*` অ্যাট্রিবিউট, `javascript:` লিংক বাদ যায়। সরাসরি `{!! $product->description !!}` লিখবেন না।
2. লগইন, রেজিস্টার, পাসওয়ার্ড রিসেট, চেকআউট, কুপন, রিভিউ, যোগাযোগ ফর্ম — সবকটায় throttle আছে; নতুন POST ফর্মেও দিন। **সবসময় নামওয়ালা limiter ব্যবহার করুন** (`throttle:efront-checkout`, `EfrontServiceProvider::configureRateLimiting()` এ সংজ্ঞা) — `throttle:10,1` লিখবেন না: Laravel সেটার counter ভিজিটর-প্রতি একটাই রাখে, ফলে কার্ট, সার্চ, geo API-র রিকোয়েস্টও চেকআউটের লিমিট খেয়ে ফেলে আর 429 দেখায়। লিমিট পার হলে ফর্ম পেজে মেসেজসহ ফেরত যায় (AJAX হলে JSON 429)।
3. "পাসওয়ার্ড ভুলে গেছি" সবসময় একই উত্তর দেয় — কোন ইমেইলে অ্যাকাউন্ট আছে সেটা বোঝা যায় না।
4. কাস্টমার আর অ্যাডমিন লগইন সম্পূর্ণ আলাদা: কাস্টমারের লগইন তথ্য (`password`, `remember_token`) শুধু `ecom_customers` টেবিলে, অ্যাডমিনের `users` টেবিলে। কাস্টমার লগইন দিয়ে `/admin` এ ঢোকা যায় না, অ্যাডমিন লগইন দিয়ে কাস্টমার অ্যাকাউন্টে নয়।

## লাইসেন্স

MIT
