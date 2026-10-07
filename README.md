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
EFRONT_THEME=glass            # ডিফল্ট থিম স্টাইল: glass / classic (অ্যাডমিন → Storefront Theme থেকেও বদলানো যায়)
EFRONT_OTP_PHONE=true         # রেজিস্ট্রেশনে মোবাইলে OTP
EFRONT_OTP_EMAIL=true         # রেজিস্ট্রেশনে ইমেইলে OTP (true হলে ইমেইল বাধ্যতামূলক)
```

অন্য publish ট্যাগ: `efront-config` (config/efront.php — per_page, new_badge_days, max_quantity, hero ছবি)। রং, ফন্ট, বাটন, হোম পেজের সেকশন ও লেখা — অ্যাডমিনের **Storefront Theme** পেজ থেকে।

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
8. **প্রোফাইল ছবি:** `/account/profile` থেকে আপলোড/মুছে ফেলা (JPG/PNG/WebP, `efront.avatar_max_kb` ডিফল্ট ২ MB), ছবি metheme-এর `me_media` টেবিলে (কালেকশন `avatar`) সেভ হয়, পুরোনো ছবি ট্র্যাশে যায় (Admin → Media Library)। লগইন থাকলে হেডারে অ্যাকাউন্ট আইকনের জায়গায় ছবি বা প্রথম অক্ষর দেখায়।
9. লগইন দরকার এমন পেজ খুললে লগইনের পরে সেই পেজেই ফেরত যায় — নিজের সেশন key `efront.intended` দিয়ে (`AuthenticateCustomer::pullIntended()`), Laravel-এর `url.intended` নয়, কারণ ওটা অ্যাডমিন লগইনের সাথে শেয়ার হয়। অ্যাডমিন (`/{prefix}`) লিংকে কখনো যায় না।
10. পাসওয়ার্ড রিসেট লিংক যায় **ইমেইলে** (metheme মেইল সেটিং দিয়ে, টোকেন `efront_password_reset_tokens` টেবিলে, ৬০ মিনিট)। ইমেইল না থাকলে কাস্টমারকে যোগাযোগ করতে বলা হয়।

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

### ৩.১. অনলাইন পেমেন্ট (bKash, কার্ড — SSLCommerz)

1. চেকআউটে bKash বা Card বাছাই করে অর্ডার দিলে অর্ডার সেভ হয় (**Unpaid**), তারপর কাস্টমার গেটওয়ের পেজে যায়, পেমেন্ট করে ফেরত আসে। গেটওয়ে নিজে নিশ্চিত করলে তবেই অর্ডার **Paid** হয় (bKash execute / SSLCommerz validation API) — ব্রাউজারের কথায় বিশ্বাস করা হয় না।
2. ফলাফলের পেজে (`efront.checkout.success`) পেমেন্ট হলে সবুজ বক্সে টাকা আর TrxID (স্যান্ডবক্স হলে "Sandbox test" ব্যাজ)। ব্যর্থ / বাতিল হলে অর্ডার থাকে, দুইটা বাটন: **Pay with bKash** (আবার চেষ্টা) আর **Cash on Delivery instead**। লগইন করা কাস্টমার My Orders থেকেও **Pay now** দিতে পারে।
3. চেকআউটে অনলাইন মেথডের টাইলে "Sandbox" ব্যাজ আর লেখা "অর্ডার দেওয়ার পরে পেমেন্ট পেজে যাবেন"; যে মেথডের গেটওয়ে নেই (যেমন Nagad) সেখানে আগের মতো Transaction ID ঘর।
4. রুট: `efront.payment.pay` (signed), `efront.payment.cod` (signed, POST), `efront.payment.callback` (গেটওয়ের ফেরত URL), `efront.payment.done`। কলব্যাক রুট ইচ্ছা করে **web গ্রুপের বাইরে** — সেশন আর CSRF ছাড়া: SSLCommerz অন্য সাইট থেকে POST করে, তখন কুকি আসে না, নতুন সেশন বানালে কাস্টমার লগআউট হয়ে যেত। তাই কলব্যাক পেমেন্ট যাচাই করে 303 দিয়ে `done` পেজে পাঠায় (সেখানে কাস্টমারের সেশন থাকে)। প্রতিটা চেষ্টার নিজস্ব গোপন টোকেন URL-এ থাকে।
5. টেস্ট (স্যান্ডবক্স): bKash ওয়ালেট `01929918378`, OTP `123456`, PIN `12121`। কার্ড `4111 1111 1111 1111`, ভবিষ্যতের যেকোনো মেয়াদ, CVV `111`, তারপর টেস্ট ব্যাংক পেজে **Success**। লোকালে http হওয়ায় কার্ডের পরে Chrome "not secure" সতর্কতা দেখায় — **Send anyway** চাপুন (https সাইটে হয় না)।
6. Nagad-এর পাবলিক স্যান্ডবক্স কী নেই — Nagad থেকে মার্চেন্ট কী পেলে গেটওয়ে যোগ করা যাবে; ততক্ষণ Transaction ID হাতে দেওয়া।

### ৩.২. ইনভয়েস (কাস্টমার)

1. অর্ডারের ফলাফল পেজ, My Orders → অর্ডার পেজ আর ট্র্যাকিং পেজে **Invoice** (প্রিন্ট করা যায় এমন পেজ) আর **Download PDF** বাটন।
2. লিংক signed (`InvoiceController::url($order)`, `download: true` হলে PDF), ৩০ দিন চলে — গেস্টও খুলতে পারে, signature ছাড়া 403।
3. ডিজাইন ecom-এর অ্যাডমিন ইনভয়েসের মতোই (`ecom::invoices.document`); রং, প্রিফিক্স, শর্তাবলি — অ্যাডমিন → Shop Settings → Store Info → Invoice।

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
6. **প্রোডাক্ট কার্ড (মোবাইল, ≤ ৫৭৫px):** "+" বাটন কার্ডের ডান-নিচের কোণে গোল হয়ে বসে; দাম পুরো চওড়া (বাটনের উপরে), পুরনো দাম আর স্টার বাটনের পাশে জায়গা রেখে; ডিসকাউন্ট না থাকলেও পুরনো দামের লাইনের জায়গা থাকে, তাই সব কার্ড একই রকম; এক সারির সব কার্ড সমান উঁচু — কার্ডে সবসময় `class="h-100"` দিন।
5. **My Orders-এর স্ট্যাটাস ট্যাব (মোবাইল, ≤ ৭৬৭px):** এক লাইনে, আঙুল দিয়ে পাশে সরানো যায়; প্রতিটা ট্যাবে অর্ডারের সংখ্যা; বাছাই করা ট্যাব নিজে থেকে দেখা যায় এমন জায়গায় আসে; ডান পাশে হালকা ফেড মানে আরো ট্যাব আছে। ডেস্কটপ আগের মতো। নতুন এমন সারিতে `data-tab-scroller` দিলেই একই আচরণ (`efront.js`)।

### ৭. AJAX ও JavaScript

1. সব আচরণ `public/efront/js/efront.js` এ, `data-*` অ্যাট্রিবিউট দিয়ে: `data-quick-view`, `data-wishlist`, `data-purchase-form`, `data-cart-remove`, `data-cart-update`, `data-coupon-form`, `data-ajax-filter`, `data-countdown="ISO তারিখ"`, `data-search-open`, `data-qty`, `data-geo-fields` (ভিতরে `data-geo-level="division|district|upazila"` + `data-selected`), `data-address-picker`, `data-billing-same`, `data-avatar-input`, `data-faq-search` / `data-faq-filter`, `data-toggle-password` (পাসওয়ার্ড দেখানো/লুকানো চোখের বোতাম)।
2. কার্ট/উইশলিস্ট এন্ডপয়েন্ট AJAX হলে JSON দেয় (`count`, `mini` = মিনি কার্ট HTML, `cart` = কার্ট পেজ HTML), সাধারণ ফর্ম হলে back redirect — দুটোই কাজ করে।
3. শপের ফিল্টার/সর্ট/পেজিনেশন AJAX এ শুধু রেজাল্ট অংশ বদলায় আর URL আপডেট করে (`history.pushState`), তাই লিংক শেয়ার/রিফ্রেশ করলে একই ফল আসে।
4. টোস্ট মেসেজ: JS এ `Efront.toast('লেখা', 'error')`; কন্ট্রোলার থেকে `->with('success', ...)` / `->with('error', ...)` দিলে নিজে থেকেই দেখায়।
5. CSS/JS বদলালে আবার `php artisan vendor:publish --tag=efront-assets --force` চালান।
6. **অ্যাডমিন প্যানেলে ফাইল ডাউনলোডের লিংক** (Export, CSV, PDF …) এ সবসময় `download` অ্যাট্রিবিউট আর `no-loader` ক্লাস দিন: `<a download href="…" class="no-loader btn …">`। নইলে metheme-এর পেজ লোডার চালু হয়ে আটকে থাকে, কারণ ডাউনলোডে নতুন পেজ লোড হয় না।

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

### ১০. থিম — Storefront Theme (অ্যাডমিন থেকে পুরো কাস্টমাইজ)

অ্যাডমিন প্যানেলের সাইডবারে **Storefront Theme** (`/admin/storefront-theme`, route `efront.admin.theme.edit`, পারমিশন `efront_theme.edit`)। কোডে হাত না দিয়ে দোকানের চেহারা বদলানো যায়। ট্যাবগুলো:

| ট্যাব | কী বদলানো যায় |
|---|---|
| Style & Fonts | থিম স্টাইল (**Glassmorphism** / **Classic**), হেডিং ফন্ট, বডি ফন্ট (১৯টা Google Font, বাংলা ফন্টসহ — Hind Siliguri, Noto Sans Bengali), ছোট লেবেলের ধরন (পরিষ্কার UPPERCASE / হাতের লেখা), কার্ড ও বাটনের কোণ কতটা গোল |
| Colours | Primary, Secondary, হেডিং, বডি টেক্সট, দাম, লেবেল, কার্ডের ক্যাটাগরি নাম; **প্রোডাক্ট ব্যাজের রং** — Discount (-১২% / ক্যাম্পেইন ডিল), New (নতুন, `efront.new_badge_days` দিনের মধ্যে যোগ হওয়া), Hot (featured) — প্রতিটার আলাদা ব্যাকগ্রাউন্ড ও লেখার রং, সাথে লাইভ নমুনা; প্রতিটা preset-এ তিনটা আলাদা রং; গ্লাস স্টাইলের ব্যাকগ্রাউন্ড গ্রেডিয়েন্ট (৪টা রং), ৫টা glow-এর রং আর glow কতটা জোরালো |
| Header & Footer | টপবার দেখাবে কিনা, টপবার/মেনু বার/পেজ টাইটেল ব্যানার/ফুটারের ব্যাকগ্রাউন্ড ও লেখার রং |
| Buttons & Icons | প্রতিটা বাটন আলাদা — মেইন বাটন, Add to Cart, Buy Now, কার্ডের গোল বাটন, Checkout, Place Order: ব্যাকগ্রাউন্ড, লেখার রং, লেখা (label), আইকন। পাশে লাইভ প্রিভিউ। হেডারের আইকন (সার্চ, উইশলিস্ট, অ্যাকাউন্ট, কার্ট, মোবাইল মেনু) |
| Product Card | ছবি পুরোটা দেখাবে (`contain`, ডিফল্ট — কিছু কাটে না) নাকি বক্স ভরাবে (`cover`), ছবির উচ্চতা, চারপাশে ফাঁকা, ছবির ব্যাকগ্রাউন্ড; ক্যাটাগরি নাম / ছোট বিবরণ / রেটিং দেখাবে কিনা |
| Home Page | হোম পেজের ১২টা সেকশন — **ড্র্যাগ করে ক্রম বদলানো**, চালু/বন্ধ, প্রতিটার লেবেল/টাইটেল/হাইলাইট/বিবরণ/বাটনের লেখা, কয়টা দেখাবে, নিজস্ব ব্যাকগ্রাউন্ড ও টাইটেলের রং; Features সেকশনের ৪টা আইটেমের আইকন-লেখা |

আরও দুটো ট্যাব: **Presets** আর **History**। উপরের বাটন: **Live Preview**, View Store, **Export** (ড্রাফট থাকলে **Export Draft**ও), **Import**, Reset। (এগুলো আলাদা বাটন, ড্রপডাউন নয় — metheme-এর টাইটেল বারে `overflow: auto` থাকায় ড্রপডাউন মেনু কেটে যায়, দেখা যায় না। ওই বারে কখনো ড্রপডাউন দেবেন না।)

**এডিট করার নিরাপদ ধাপ — Draft → Preview → Publish:**

1. **Live Preview:** উপরের "Live Preview" চাপলে ডানে আসল দোকান খোলে (Desktop / Tablet / Mobile সাইজ, পেজ বাছাই — Home, Shop, Product, Cart, Login, Track order)। যেকোনো সেটিং বদলালে ~০.৭ সেকেন্ডে প্রিভিউ আপডেট হয়, স্ক্রল যেখানে ছিল সেখানেই থাকে। প্রিভিউর সেটিং শুধু **এই অ্যাডমিনের সেশনে** থাকে (`efront_theme_preview`) আর শুধু `efront_theme.edit` পারমিশনওয়ালা লগইন করা অ্যাডমিনকেই দেখায় — কাস্টমাররা কখনো দেখে না। প্রিভিউ চলাকালীন দোকানে নিচে বামে "Theme preview — not live yet · Exit" ব্যাজ দেখায় (প্রিভিউ প্যানেলের ভিতরে লুকানো)।
2. **Save Draft:** সেটিং সেভ হয় কিন্তু লাইভ হয় না (`settings` টেবিলে key `efront_theme_draft`)। পরের বার পেজ খুললে ড্রাফটই খোলে, উপরে হলুদ বার্তা আর **Discard draft** বাটন।
3. **Publish:** লাইভ হয়, ড্রাফট আর প্রিভিউ মুছে যায়, আর **ভার্সন** হিসেবে জমা হয় (ঐচ্ছিক নোটসহ, যেমন "Eid colours")।
4. **History:** `efront_theme_versions` টেবিলে শেষ **৩০টা** ভার্সন (কে, কখন, নোট)। প্রতিটার **Preview** (নতুন ট্যাবে দোকান সেই ভার্সনে, শুধু আপনার জন্য) আর **Restore** (আবার Publish করে, নতুন ভার্সন হিসেবে)। Reset আর Restore-ও ভার্সন হয়, তাই সবকিছু ফেরানো যায়।

**Presets:** ১২টা তৈরি চেহারা — Glass Red (ডিফল্ট), Minimal Black, Fresh Green, Ocean Blue, Royal Purple, Eid Mubarak, Puja Festive, Pohela Boishakh, **Bijoy Dibos** (পতাকার সবুজ-লাল — বিজয় দিবস, স্বাধীনতা দিবস ও অন্য জাতীয় দিবস), **Shok Dibos** (সাদা-কালো, শান্ত — ২১ ফেব্রুয়ারি, ১৫ আগস্ট, ১৪ ডিসেম্বর), **Borsha** (বর্ষার টিল-নীল), **Mega Sale 11.11** (গোলাপি-কমলা, ফ্ল্যাশ সেল)। সব preset-এর রং পড়ার মতো কিনা (contrast) আর ফন্ট তালিকায় আছে কিনা টেস্টে যাচাই হয়। **কোন preset চলছে:** অ্যাডমিনের Storefront Theme পেজে উপরে "Preset: Live: Bijoy Dibos" (প্রয়োগের তারিখসহ), ড্রাফটে অন্য preset থাকলে "In draft: …", পরে রং/ফন্ট বদলালে "customized"; Presets ট্যাবে সেই কার্ডে সবুজ **Live** / কমলা **In draft** চিহ্ন। Preset প্রয়োগ করলে সেটিংসে `preset` (key, applied_at) সেভ হয়; নোট ছাড়া Publish করলে History-তে নোট "Preset: …"। আগের সেভ করা থিমেও (রেকর্ড ছাড়া) মিলিয়ে preset চেনা যায় (`Theme::presetInfo()`, `livePreset()`, `draftPreset()`)। Apply করলে শুধু স্টাইল, ফন্ট আর রং বদলায় — লেখা, সেকশন, বাটনের লেখা/আইকন থাকে — আর সেটা **ড্রাফট** হয় (প্রিভিউ দেখে Publish করুন)। নতুন preset যোগ করতে `src/Config/theme_presets.php` এ একটা এন্ট্রি দিন।

**Export / Import:** Export দিয়ে লাইভ (বা ড্রাফট) থিম `.json` ফাইলে ডাউনলোড; Import দিয়ে সেই ফাইল এই বা অন্য দোকানে — ফর্মের মতোই পুরো যাচাই হয়, ভুল/ক্ষতিকর মান থাকলে বাতিল, ঠিক থাকলে **ড্রাফট** হিসেবে আসে।

**পড়ার সুবিধা (Contrast) সতর্কতা:** লেখা আর ব্যাকগ্রাউন্ডের রং খুব কাছাকাছি হলে (WCAG নিয়ম — সাধারণ লেখায় ৪.৫:১, বড়/বোল্ড লেখা ও বাটনে ৩:১) পেজের উপরে লাল বক্সে তালিকা দেখায়, এডিট করার সাথে সাথে আপডেট হয়; Publish করার পরও বার্তা দেখায়। যা চেক হয়: বডি টেক্সট ও হেডিং (পেজের ব্যাকগ্রাউন্ডে), দাম, মেনু বার, টপবার, পেজ টাইটেল ব্যানার, ফুটার, প্রতিটা বাটন, আর নিজস্ব ব্যাকগ্রাউন্ড দেওয়া সেকশনের টাইটেল। কোডে: `efront_theme()->contrastIssues($settings)`, `Theme::contrast('#000000', '#ffffff')`। (সতর্কতা শুধু জানায়, Publish আটকায় না।)

**কীভাবে কাজ করে:**

1. ডিফল্ট মান `src/Config/theme.php` এ (config key `efront_theme`)। অ্যাডমিন যা সেভ করে তা metheme-এর `settings` টেবিলে একটা JSON হিসেবে (key `efront_theme`) যায়; যা সেভ নেই সেটা ডিফল্ট থেকে আসে। ক্যাশ হয়, সেভ/রিসেটে ক্যাশ মুছে যায়।
2. কোডে `efront_theme()` হেল্পার (`ME\Efront\Support\Theme`): `published()`, `editable()` (ড্রাফট থাকলে ড্রাফট), `draft()`, `saveDraft()`, `publish($settings, $note, $userId)`, `startPreview()` / `stopPreview()` / `isPreviewing()`, `presets()`, `withPreset()`, `get('colors.primary')`, `isGlass()`, `homeSections()` (চালু সেকশন, ক্রম অনুযায়ী), `section('featured')`, `button('add_to_cart')`, `buttonContent('buy_now')` (আইকন + লেখা), `icon('cart')`, `sectionStyle('featured')`, `fontsUrl()`, `css()`।
3. স্টাইলশিটে রং আর ফন্ট এখন **CSS ভ্যারিয়েবল** (`--primary`, `--secondary`, `--ef-primary-rgb`, `--ef-primary-dark`, `--ef-font-heading`, `--ef-font-body`, `--ef-card-fit` …)। লেআউট `<style id="ef-theme">` এ `efront_theme()->css()` বসায় — সেটাই ভ্যারিয়েবলের মান আর বাটন/হেডার/ফুটারের রং দেয়। নতুন CSS লিখলে রং হাতে না লিখে এই ভ্যারিয়েবল ব্যবহার করুন (যেমন `rgba(var(--ef-primary-rgb), .1)`), নইলে অ্যাডমিনের রং সেখানে লাগবে না।
4. হোম পেজ এখন ভাগ করা: `resources/views/home/sections/{hero, marquee, features, categories, featured, campaign, promos, new_arrivals, lists, brands, testimonials, newsletter}.blade.php`, `home.blade.php` শুধু ক্রম অনুযায়ী এগুলো include করে। `HomeController` শুধু **চালু সেকশনের** ডেটা আনে (বন্ধ সেকশনের query হয় না)। প্রতিটা সেকশনে `data-ef-sec="key"` আর `style="{{ efront_theme()->sectionStyle('key') }}"` থাকতে হবে। নতুন সেকশন বানাতে: partial ফাইল, `Config/theme.php` এর `sections` এ ডিফল্ট, `Theme::SECTIONS` এ নাম, `HomeController` এ ডেটা।
5. **Hero** সেকশনের লেখা শুধু তখনই দেখায় যখন কোনো সক্রিয় "slider" ব্যানার নেই (Website → Banners); ব্যানার থাকলে স্লাইডার দেখায়।
6. **নিরাপত্তা:** সেভের সময় কড়া যাচাই — রং শুধু `#RRGGBB`, ফন্ট শুধু তালিকার, আইকন শুধু Font Awesome ক্লাস (`fas fa-…`), সংখ্যা সীমার মধ্যে। CSS বানানোর সময়ও প্রতিটা মান আবার যাচাই হয়, তাই ডাটাবেসে কেউ ভুল/ক্ষতিকর মান ঢুকালেও CSS ভাঙে না (ডিফল্ট বসে যায়)। প্রতিটা সেভ/রিসেট **Activity Log** এ আগে-পরে সহ লেখা থাকে।
7. **Reset** বাটন লাইভ সেটিং মুছে ডিফল্ট চেহারায় ফেরায় (ড্রাফট আর প্রিভিউও মুছে যায়); এটাও History-তে ভার্সন হয়, তাই আগেরটা Restore করা যায়।
8. ডাটাবেস না থাকলেও (500/503 এরর পেজ) থিম ডিফল্ট দিয়ে চলে।
9. ফর্মের যাচাই আর ফর্ম ↔ সেটিং রূপান্তর এক জায়গায়: `ME\Efront\Support\ThemeForm` (`rules()`, `validate()`, `toSettings()`, `toInput()`) — Save, Live Preview আর Import তিনটাই এটা ব্যবহার করে।

**ডিফল্ট চেহারা:** Glassmorphism, হেডিং **Plus Jakarta Sans**, বডি **Inter** (আগের Playfair/Dancing Script বাদ), প্রোডাক্টের ছবি পুরোটা দেখায় (`contain`)।

**Glassmorphism স্টাইল (`public/efront/css/glass.css`):**

1. পেছনে নরম রঙিন গ্রেডিয়েন্ট আর ৫টা ঝাপসা, আলো-ছড়ানো "orb" (ধীরে নড়ে), উপরে সব কার্ড/প্যানেল ফ্রস্টেড গ্লাস — আধা-স্বচ্ছ, `backdrop-filter` ব্লার, পাতলা সাদা বর্ডার, উপরের কিনারায় হাইলাইট, কয়েক স্তরের শ্যাডো। টপবার, পেজ হেডার, ক্যাম্পেইন সেকশন আর ফুটার ডার্ক গ্লাস; নেভবার, ড্রপডাউন, মিনি কার্ট, কুইক ভিউ — সব গ্লাস। হিরো স্লাইডারের তীর (দুই পাশে, মাঝ বরাবর) আর ডট (গ্লাস পিল, চালু ডট লম্বা) এবং ক্যাটাগরি মারকি স্ট্রিপও গ্লাস। মারকি একটানা লুপে চলে — দুটো হুবহু অর্ধেক, প্রতিটা স্ক্রিনের চেয়ে চওড়া (ক্যাটাগরি দরকারমতো রিপিট হয়), তাই শুরু/শেষ বোঝা যায় না; মাউস রাখলে থামে। প্রোমো ব্যানার: ওপরে সেকশন টাইটেল (ডিফল্ট "Special Offers / Deals & Offers", অ্যাডমিন → Storefront Theme → Home Page → Promo Banners থেকে বদলানো যায়, টাইটেল ফাঁকা রাখলে হেডিং লুকায়); সংখ্যা অনুযায়ী কলাম (১টা → চওড়া, ২ → অর্ধেক, ৩ → তিন ভাগ, ৪ → ২×২), তাই ফাঁকা জায়গা থাকে না; গ্লাস ফ্রেম, হোভারে ছবি একটু বড় হয়, আলোর ঝলক আর গ্লাস তীর। `EFRONT_BANNER_TEXT=false` (ডিফল্ট) হলে ছবির ওপর আলাদা লেখা ছাপা হয় না — ডিজাইন করা ছবিতে লেখা থাকেই। রংগুলো অ্যাডমিনের সেটিং থেকে (`color-mix` দিয়ে স্বচ্ছ করা)।
2. **লেআউট বা সাইজ কিছুই বদলায় না** — শুধু রং, স্বচ্ছতা, ব্লার, বর্ডার আর শ্যাডো। সব নিয়ম `body.ef-glass` এর ভিতরে; Classic স্টাইলে glass.css লোডই হয় না।
3. `.env` এ `EFRONT_THEME=classic` দিলে **ডিফল্ট** স্টাইল Classic হয় (অ্যাডমিনে সেভ করা স্টাইল থাকলে সেটাই চলে)।
4. পেছনের orb: `efront::partials.glass-background`। নতুন আলাদা `<html>` লেআউট বানালে `layouts/app` এর মতো font link, glass.css, `<style id="ef-theme">`, body-তে `ef-themed` (আর গ্লাস হলে `ef-glass`) ক্লাস আর orb partial দিন।
5. নতুন কার্ড/প্যানেলে আগের ক্লাস ব্যবহার করুন (`fcard`, `ef-filter-box`, `ef-stat` …) — গ্লাস আর অ্যাডমিনের রং নিজে থেকেই লাগবে।
6. **সতর্কতা:** কোনো `input`-এ `backdrop-filter` দেবেন না — ইনপুটের ভিতরের আইকন ঢেকে যায়। গ্লাস রুল কোনো বোতামের `.active` / `:hover` রং ঢেকে দিলে glass.css-এ সেই অবস্থার রং আবার লিখুন।
7. "Reduce motion" চালু থাকলে orb নড়ে না; মোবাইলে orb ছোট; `backdrop-filter` না থাকা ব্রাউজারে কার্ড প্রায় সাদা।
7.0. **সব পপআপ নেভবারের ঠিক নিচ থেকে শুরু হয়** — সার্চ, মেগা মেনু, অ্যাকাউন্ট ড্রপডাউন, মিনি কার্ট, কুইক ভিউ, ফিল্টার অফক্যানভাস। `efront.js` নেভবারের নিচের কিনারা `--ef-nav-bottom` CSS ভেরিয়েবলে রাখে (টপবার স্ক্রল হলে বদলায়)। নতুন পপআপে `top: var(--ef-nav-bottom)` দিন; নেভবারের ড্রপডাউনে `data-bs-display="static"` আর `top: 100%` (`#nav > .container` relative)।
7.0.1. **ক্লাসের নাম:** ফর্মের ঘরের নিচের লাল লেখা = `.ef-error`; ৪০৪/৫০০ এরর পেজের সেকশন = `.ef-error-page`। নতুন ক্লাস বানানোর আগে নাম আগে থেকে আছে কিনা খুঁজে নিন (`.ef-account` = অ্যাকাউন্ট পেজ, তাই নেভবারের ড্রপডাউন `.ef-nav-account`)।
7.1. **সতর্কতা:** যে এলিমেন্টের ভেতরে ব্লার করা ড্রপডাউন/প্যানেল আছে (যেমন `#nav`), তার নিজের গায়ে `backdrop-filter` দেবেন না — তাহলে ভেতরের মেগা মেনু শুধু নেভবারটুকু ব্লার করে, পেছনের পেজ নয় (স্বচ্ছ দেখায়)। এমন জায়গায় গ্লাস `::before`-এ দিন, যেমন `.ef-glass #nav::before`।
8. CSS বদলালে আবার `php artisan vendor:publish --tag=efront-assets --force` চালান। লেআউটে নিজের CSS/JS `efront_asset('efront/css/x.css')` দিয়ে দিন — URL-এ ফাইলের সময় (`?v=`) যোগ হয়, তাই ব্রাউজার পুরনো ক্যাশ করা ফাইল দেখায় না।

### ছবি (me_media)

1. স্টোরফ্রন্টের সব ছবি (প্রোডাক্ট, ক্যাটাগরি, ব্র্যান্ড, ব্যানার, ক্যাম্পেইন, কাস্টমার, স্টোর লোগো) metheme-এর `me_media` টেবিল থেকে আসে — কোনো `ecom_*` টেবিলে ছবির কলাম নেই।
2. ভিউতে সরাসরি পাথ নয়, অ্যাক্সেসর ব্যবহার করুন: `$product->thumbnail`, `$category->image_url`, `$brand->logo_url`, `$banner->image_url`, `$campaign->banner_url`, `$customer->avatar_url`, `efront()->logoUrl()`।
3. কুয়েরিতে `->with('media')` দিন (প্রোডাক্ট লিস্টে `primaryImage`), নইলে প্রতি কার্ডে আলাদা কুয়েরি হয়। হোমপেজ আর মেনু ক্যাটাগরিতে এটা আগে থেকেই আছে।
4. লোগো আছে এমন ব্র্যান্ড: `Brand::whereHas('media', fn ($q) => $q->where('collection', 'logo'))`।

## লাইসেন্স

MIT
