<?php

namespace ME\Efront\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use ME\Efront\Models\ThemeVersion;
use ME\Models\Setting;
use Throwable;

/**
 * Storefront theme: defaults from Config/theme.php merged with what admin saved
 * (settings key "efront_theme"). Builds the CSS variables / overrides printed in the layout.
 */
class Theme
{
    public const SETTING_KEY = 'efront_theme';

    /** Unpublished changes (Save Draft). */
    public const DRAFT_KEY = 'efront_theme_draft';

    /** Session key holding the settings shown in live preview (only for admins with efront_theme.edit). */
    public const PREVIEW_KEY = 'efront_theme_preview';

    private const CACHE_KEY = 'efront_theme_settings';

    /**
     * Google Fonts offered on the settings page => weights to load.
     *
     * @var array<string, string>
     */
    public const FONTS = [
        'Inter' => '400;500;600;700;800',
        'Plus Jakarta Sans' => '400;500;600;700;800',
        'Manrope' => '400;500;600;700;800',
        'DM Sans' => '400;500;600;700;800',
        'Outfit' => '400;500;600;700;800',
        'Urbanist' => '400;500;600;700;800',
        'Poppins' => '400;500;600;700;800',
        'Montserrat' => '400;500;600;700;800',
        'Work Sans' => '400;500;600;700;800',
        'Mulish' => '400;500;600;700;800',
        'Nunito Sans' => '400;600;700;800',
        'Open Sans' => '400;500;600;700;800',
        'Rubik' => '400;500;600;700;800',
        'Roboto' => '400;500;700;900',
        'Lato' => '400;700;900',
        'Hind Siliguri' => '400;500;600;700',
        'Noto Sans Bengali' => '400;500;600;700;800',
        'Lora' => '400;500;600;700',
        'Playfair Display' => '400;700;900',
    ];

    /**
     * Home page section key => name shown in admin.
     *
     * @var array<string, string>
     */
    public const SECTIONS = [
        'hero' => 'Hero (shown when there is no slider banner)',
        'marquee' => 'Category Marquee',
        'features' => 'Features (delivery, COD, returns, support)',
        'categories' => 'Browse by Category',
        'featured' => 'Featured Products',
        'campaign' => 'Running Campaign / Flash Sale',
        'promos' => 'Promo Banners',
        'new_arrivals' => 'New Arrivals',
        'lists' => 'Best Sellers / Top Rated / On Sale',
        'brands' => 'Brands',
        'testimonials' => 'Customer Reviews',
        'newsletter' => 'Create Account Band',
    ];

    /**
     * Buttons the admin can style => name shown in admin.
     *
     * @var array<string, string>
     */
    public const BUTTONS = [
        'primary' => 'Main buttons (Shop Now, View All …)',
        'add_to_cart' => 'Add to Cart',
        'buy_now' => 'Buy Now',
        'quick_add' => 'Product card round button (quick view)',
        'checkout' => 'Checkout (cart & mini cart)',
        'place_order' => 'Place Order (checkout page)',
    ];

    /** @var array<string, mixed>|null */
    private ?array $settings = null;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return config('efront_theme', []);
    }

    /**
     * Defaults with the saved settings on top. Sections come in the saved order; new sections are added at the end.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        return $this->settings = self::merge($this->previewSettings() ?? $this->saved());
    }

    /**
     * The live (published) theme, ignoring any preview.
     *
     * @return array<string, mixed>
     */
    public function published(): array
    {
        return self::merge($this->saved());
    }

    /**
     * What the admin form edits: the draft when there is one, otherwise the published theme.
     *
     * @return array<string, mixed>
     */
    public function editable(): array
    {
        return self::merge($this->draft() ?? $this->saved());
    }

    /**
     * Defaults with saved values on top. Sections come in the saved order; new sections are added at the end.
     *
     * @param  array<string, mixed>  $saved
     * @return array<string, mixed>
     */
    public static function merge(array $saved): array
    {
        $defaults = self::defaults();
        $merged = array_replace_recursive($defaults, Arr::except($saved, ['sections']));

        $sections = [];
        foreach (array_keys($saved['sections'] ?? []) as $key) {
            if (isset($defaults['sections'][$key]) && is_array($saved['sections'][$key])) {
                $sections[$key] = array_replace_recursive($defaults['sections'][$key], $saved['sections'][$key]);
            }
        }
        $merged['sections'] = $sections + $defaults['sections'];

        return $merged;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    public function isGlass(): bool
    {
        return $this->get('style') === 'glass';
    }

    /**
     * Enabled home sections in display order.
     *
     * @return array<string, array<string, mixed>>
     */
    public function homeSections(): array
    {
        return array_filter($this->get('sections'), fn ($section) => ! empty($section['enabled']));
    }

    /**
     * @return array<string, mixed>
     */
    public function section(string $key): array
    {
        return $this->get("sections.{$key}", []);
    }

    /**
     * @return array{label: ?string, icon: ?string, bg: string, text: string}
     */
    public function button(string $key): array
    {
        return $this->get("buttons.{$key}");
    }

    /**
     * Icon + label of a button as set in admin (escaped), e.g. <i class="fas fa-bolt"></i>Buy Now.
     */
    public function buttonContent(string $key): HtmlString
    {
        $button = $this->button($key);
        $icon = self::validIcon($button['icon'] ?? null) ? '<i class="'.e($button['icon']).'"></i>' : '';

        return new HtmlString($icon.e((string) ($button['label'] ?? '')));
    }

    public function icon(string $key): string
    {
        $icon = (string) $this->get("icons.{$key}");

        return self::validIcon($icon) ? $icon : (string) data_get(self::defaults(), "icons.{$key}");
    }

    /**
     * Inline style for a home section: its own background / heading colour when set.
     */
    public function sectionStyle(string $key): string
    {
        $section = $this->section($key);
        $styles = [];

        if ($bg = self::validColor($section['bg'] ?? null)) {
            $styles[] = 'background:'.($this->isGlass() ? "color-mix(in srgb, {$bg} 78%, transparent)" : $bg);
        }

        if ($heading = self::validColor($section['heading'] ?? null)) {
            $styles[] = "--ef-sec-heading:{$heading}";
        }

        return implode(';', $styles);
    }

    public function fontsUrl(): string
    {
        $families = array_unique(array_filter([
            $this->font('heading'),
            $this->font('body'),
            $this->get('fonts.label_style') === 'script' ? 'Dancing Script' : null,
        ]));

        $query = collect($families)
            ->map(fn ($font) => 'family='.str_replace(' ', '+', $font).':wght@'.(self::FONTS[$font] ?? '700'))
            ->implode('&');

        return "https://fonts.googleapis.com/css2?{$query}&display=swap";
    }

    /**
     * CSS printed after the theme stylesheets: variables plus overrides for fonts, areas, buttons and cards.
     */
    public function css(): string
    {
        $c = fn (string $key) => self::validColor($this->get($key)) ?? self::validColor(data_get(self::defaults(), $key)) ?? '#000000';
        $int = fn (string $key, int $min, int $max) => max($min, min($max, (int) $this->get($key)));

        $primary = $c('colors.primary');
        $secondary = $c('colors.secondary');
        $glass = $this->isGlass();
        $imageBg = $c('card.image_bg');
        $pageheadBg = $c('header.pagehead_bg');

        $vars = [
            '--primary' => $primary,
            '--secondary' => $secondary,
            '--ef-primary-rgb' => self::rgb($primary),
            '--ef-secondary-rgb' => self::rgb($secondary),
            '--ef-primary-dark' => self::shade($primary, -0.18),
            '--dark' => $c('colors.heading'),
            '--ef-text' => $c('colors.text'),
            '--ef-price' => $c('colors.price'),
            '--ef-label' => $c('colors.label'),
            '--ef-cat-label' => $c('colors.category_label'),
            '--ef-font-heading' => '"'.$this->font('heading').'", system-ui, sans-serif',
            '--ef-font-body' => '"'.$this->font('body').'", system-ui, sans-serif',
            '--ef-font-accent' => $this->get('fonts.label_style') === 'script' ? '"Dancing Script", cursive' : '"'.$this->font('heading').'", system-ui, sans-serif',
            '--ef-radius-card' => $int('shape.card_radius', 0, 40).'px',
            '--ef-radius-btn' => $int('shape.button_radius', 0, 50).'px',
            '--ef-card-fit' => $this->get('card.image_fit') === 'cover' ? 'cover' : 'contain',
            '--ef-card-img-h' => $int('card.image_height', 120, 420).'px',
            '--ef-card-pad' => $int('card.image_padding', 0, 40).'px',
            '--ef-card-img-bg' => $imageBg,
            '--ef-card-img-bg-glass' => "color-mix(in srgb, {$imageBg} 70%, transparent)",
            '--ef-topbar-bg' => $c('header.topbar_bg'),
            '--ef-navbar-bg' => $c('header.navbar_bg'),
            '--ef-pagehead-bg' => $pageheadBg,
            '--ef-special-bg' => self::validColor($this->get('sections.campaign.bg')) ?? $pageheadBg,
            '--ef-footer-bg' => $c('footer.bg'),
            '--ef-glow-opacity' => round($int('glass.glow', 0, 100) / 100, 2),
        ];

        foreach (['bg_1', 'bg_2', 'bg_3', 'bg_4'] as $i => $key) {
            $vars['--ef-bg-'.($i + 1)] = $c("glass.{$key}");
        }
        for ($i = 1; $i <= 5; $i++) {
            $vars["--ef-orb-{$i}"] = $c("glass.orb_{$i}");
        }

        $css = ':root{'.collect($vars)->map(fn ($value, $name) => "{$name}:{$value}")->implode(';').'}';

        $css .= <<<'CSS'
body.ef-themed{font-family:var(--ef-font-body);color:var(--ef-text)}
body.ef-themed h1,body.ef-themed h2,body.ef-themed h3,body.ef-themed h4,body.ef-themed h5,body.ef-themed h6,
body.ef-themed .stitle,body.ef-themed .htitle,body.ef-themed .sptitle,body.ef-themed .mtit,body.ef-themed .ef-pagehead-title,
body.ef-themed .ef-auth-title,body.ef-themed .ef-list-title,body.ef-themed .fnm{font-family:var(--ef-font-heading);letter-spacing:-.01em}
body.ef-themed .stitle,body.ef-themed .htitle{font-weight:800}
body.ef-themed h1,body.ef-themed h2,body.ef-themed h3,body.ef-themed h4,body.ef-themed h5,body.ef-themed h6,body.ef-themed .stitle,body.ef-themed .mtit{color:var(--dark)}
body.ef-themed .mtit{font-weight:700}
body.ef-themed .mprice,body.ef-themed .ef-price,body.ef-themed .ef-cart-unit,body.ef-themed .ef-radio-price{color:var(--ef-price)}
body.ef-themed .mprice{font-family:var(--ef-font-heading);font-weight:800}
body.ef-themed .slbl{color:var(--ef-label)}
body.ef-themed .mcat{color:var(--ef-cat-label)}
body.ef-themed [data-ef-sec] .stitle,body.ef-themed [data-ef-sec] .ef-list-title,body.ef-themed [data-ef-sec] .sptitle,body.ef-themed [data-ef-sec] .htitle,body.ef-themed [data-ef-sec] .nlw h2{color:var(--ef-sec-heading,var(--dark))}
body.ef-themed [data-ef-sec=campaign] .sptitle,body.ef-themed [data-ef-sec=newsletter] .nlw h2{color:var(--ef-sec-heading,#fff)}
body.ef-themed .fcard,body.ef-themed .mcard,body.ef-themed .catcard,body.ef-themed .tescard,body.ef-themed .ef-filter-box,
body.ef-themed .ef-toolbar,body.ef-themed .ef-stat,body.ef-themed .ef-feature,body.ef-themed .ef-mini-card,body.ef-themed .ef-accordion .accordion-item{border-radius:var(--ef-radius-card)}
body.ef-themed .mcard .mimg{border-radius:0}
CSS;

        if ($this->get('fonts.label_style') !== 'script') {
            $css .= 'body.ef-themed .slbl{font-family:var(--ef-font-heading);font-size:.78rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;margin-bottom:8px}';
        }

        // Header, page header, footer
        $css .= '#topbar,#topbar a,#topbar .ef-toplink{color:'.$c('header.topbar_text').'}'
            .'body.ef-themed #topbar{background-color:var(--ef-topbar-bg)}'
            .'body:not(.ef-glass).ef-themed #topbar{background:var(--ef-topbar-bg)}'
            .'body:not(.ef-glass).ef-themed #nav{background:var(--ef-navbar-bg)}'
            .'body.ef-themed #nav .nav-link,body.ef-themed #nav .ef-navicon,body.ef-themed #nav .blogo{color:'.$c('header.navbar_text').'}'
            .'body.ef-themed #nav .nav-link.active,body.ef-themed #nav .nav-link:hover{color:var(--primary)}'
            .'body:not(.ef-glass).ef-themed .ef-pagehead{background:linear-gradient(135deg,var(--ef-pagehead-bg),'.self::shade($pageheadBg, -0.35).')}'
            .'body.ef-themed .ef-pagehead-title,body.ef-themed .ef-pagehead .breadcrumb-item,body.ef-themed .ef-pagehead .breadcrumb-item a{color:'.$c('header.pagehead_text').'}'
            .'body.ef-themed .ef-pagehead .breadcrumb-item.active{color:var(--secondary)}'
            .'body:not(.ef-glass).ef-themed footer{background:var(--ef-footer-bg)}'
            .'body.ef-themed footer,body.ef-themed footer .fdesc,body.ef-themed footer .flinks a,body.ef-themed footer .fciinfo,body.ef-themed footer .fbot,body.ef-themed footer .fbot a{color:'.$c('footer.text').'}'
            .'body.ef-themed footer .ftit,body.ef-themed footer .fnm,body.ef-themed footer .fciinfo strong{color:'.$c('footer.heading').'}';

        // Buttons
        $selectors = [
            'primary' => '.btn-red',
            'add_to_cart' => '.ef-addcart,.mpaddcart',
            'buy_now' => '.ef-buynow',
            'quick_add' => '.madd',
            'checkout' => '.btn-red.ef-btn-checkout',
            'place_order' => '.btn-red.ef-btn-place-order',
        ];
        foreach ($selectors as $key => $selector) {
            $bg = $c("buttons.{$key}.bg");
            $text = $c("buttons.{$key}.text");
            $scoped = collect(explode(',', $selector))->map(fn ($s) => "body.ef-themed {$s}");
            $css .= $scoped->implode(',').'{background:linear-gradient(135deg,'.$bg.','.self::shade($bg, -0.15).');color:'.$text
                .';box-shadow:0 8px 22px -6px color-mix(in srgb,'.$bg.' 55%,transparent)'
                .($key === 'quick_add' ? '' : ';border-radius:var(--ef-radius-btn)').'}';
            $css .= $scoped->map(fn ($s) => "{$s}:hover")->implode(',').'{color:'.$text.';filter:brightness(1.06);box-shadow:0 12px 28px -6px color-mix(in srgb,'.$bg.' 65%,transparent)}';
        }
        $css .= 'body.ef-themed .ef-btn-outline,body.ef-themed .nlbtn{border-radius:var(--ef-radius-btn)}';

        // Product card parts
        foreach (['show_category' => '.mcard .mcat', 'show_description' => '.mcard .mdesc', 'show_rating' => '.mcard .mstars'] as $key => $selector) {
            if (! $this->get("card.{$key}")) {
                $css .= collect(explode(',', $selector))->map(fn ($s) => "body.ef-themed {$s}")->implode(',').'{display:none!important}';
            }
        }

        return $css;
    }

    /**
     * Make settings live (already validated) without recording a version. Prefer publish().
     *
     * @param  array<string, mixed>  $settings
     */
    public function save(array $settings): void
    {
        Setting::set(self::SETTING_KEY, json_encode($settings));
        Cache::forget(self::CACHE_KEY);
        $this->settings = null;
    }

    /**
     * Make settings live, record them in the history, and clear the draft and preview.
     *
     * @param  array<string, mixed>  $settings
     */
    public function publish(array $settings, ?string $note = null, ?int $userId = null): ThemeVersion
    {
        $this->save($settings);
        $this->discardDraft();
        $this->stopPreview();

        $version = ThemeVersion::create(['settings' => $settings, 'note' => $note, 'user_id' => $userId]);
        ThemeVersion::whereNotIn('id', ThemeVersion::latest('id')->take(ThemeVersion::KEEP)->pluck('id'))->delete();

        return $version;
    }

    /**
     * Back to the default look (recorded in the history, so it can be undone).
     */
    public function reset(?int $userId = null): ThemeVersion
    {
        Setting::where('key', self::SETTING_KEY)->delete();
        Cache::forget(self::CACHE_KEY);
        $this->settings = null;
        $this->discardDraft();
        $this->stopPreview();

        return ThemeVersion::create(['settings' => self::defaults(), 'note' => 'Reset to default', 'user_id' => $userId]);
    }

    /**
     * Unpublished settings saved with "Save Draft", or null.
     *
     * @return array<string, mixed>|null
     */
    public function draft(): ?array
    {
        $json = Setting::where('key', self::DRAFT_KEY)->value('value');
        $draft = is_string($json) ? json_decode($json, true) : null;

        return is_array($draft) ? $draft : null;
    }

    public function draftSavedAt(): ?Carbon
    {
        return Setting::where('key', self::DRAFT_KEY)->first()?->updated_at;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function saveDraft(array $settings): void
    {
        Setting::set(self::DRAFT_KEY, json_encode($settings));
    }

    public function discardDraft(): void
    {
        Setting::where('key', self::DRAFT_KEY)->delete();
    }

    /**
     * Show these settings (instead of the live theme) to the current admin only, until stopPreview().
     *
     * @param  array<string, mixed>  $settings
     */
    public function startPreview(array $settings): void
    {
        session()->put(self::PREVIEW_KEY, $settings);
        $this->settings = null;
    }

    public function stopPreview(): void
    {
        if (app()->bound('session') && request()->hasSession()) {
            session()->forget(self::PREVIEW_KEY);
        }
        $this->settings = null;
    }

    public function isPreviewing(): bool
    {
        return $this->previewSettings() !== null;
    }

    /**
     * Ready-made looks from Config/theme_presets.php.
     *
     * @return array<string, array{name: string, description: string, settings: array<string, mixed>}>
     */
    public static function presets(): array
    {
        return config('efront_theme_presets', []);
    }

    /**
     * $base with a preset's style / fonts / colours copied over it (texts and sections are kept).
     *
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    public static function withPreset(array $base, string $preset): array
    {
        return array_replace_recursive($base, self::presets()[$preset]['settings'] ?? []);
    }

    /**
     * Text / background pairs that are hard to read (WCAG contrast ratio below the minimum).
     *
     * @param  array<string, mixed>|null  $settings
     * @return array<int, array{label: string, ratio: float, min: float}>
     */
    public function contrastIssues(?array $settings = null): array
    {
        $settings = self::merge($settings ?? $this->published());
        $issues = [];

        foreach (self::contrastChecks($settings) as [$label, $fg, $bg, $min]) {
            $foreground = self::validColor(str_starts_with($fg, '#') ? $fg : data_get($settings, $fg));
            $background = self::validColor(str_starts_with($bg, '#') ? $bg : data_get($settings, $bg));

            if ($foreground && $background && ($ratio = self::contrast($foreground, $background)) < $min) {
                $issues[] = ['label' => $label, 'ratio' => round($ratio, 2), 'min' => $min];
            }
        }

        return $issues;
    }

    /**
     * [label, foreground (setting path or #hex), background (setting path or #hex), minimum ratio].
     * Normal text needs 4.5, large/bold text and buttons 3 (WCAG AA).
     *
     * @param  array<string, mixed>  $settings
     * @return array<int, array{0: string, 1: string, 2: string, 3: float}>
     */
    public static function contrastChecks(array $settings): array
    {
        $pageBg = ($settings['style'] ?? 'glass') === 'glass' ? 'glass.bg_1' : '#ffffff';
        $checks = [
            ['Body text on the page', 'colors.text', $pageBg, 4.5],
            ['Headings on the page', 'colors.heading', $pageBg, 3.0],
            ['Price on product cards', 'colors.price', 'card.image_bg', 3.0],
            ['Menu bar links', 'header.navbar_text', 'header.navbar_bg', 4.5],
            ['Page title banner text', 'header.pagehead_text', 'header.pagehead_bg', 3.0],
            ['Footer text', 'footer.text', 'footer.bg', 4.5],
            ['Footer headings', 'footer.heading', 'footer.bg', 3.0],
        ];

        if (! empty($settings['header']['topbar'])) {
            $checks[] = ['Top bar text', 'header.topbar_text', 'header.topbar_bg', 4.5];
        }

        foreach (self::BUTTONS as $key => $name) {
            $checks[] = ["{$name} button", "buttons.{$key}.text", "buttons.{$key}.bg", 3.0];
        }

        foreach ($settings['sections'] ?? [] as $key => $section) {
            if (! empty($section['enabled']) && filled($section['bg'] ?? null)) {
                $heading = filled($section['heading'] ?? null) ? "sections.{$key}.heading" : 'colors.heading';
                $checks[] = [(self::SECTIONS[$key] ?? $key).' — title on its background', $heading, "sections.{$key}.bg", 3.0];
            }
        }

        return $checks;
    }

    /**
     * WCAG contrast ratio between two #RRGGBB colours (1 to 21).
     */
    public static function contrast(string $a, string $b): float
    {
        $luminance = function (string $hex): float {
            [$r, $g, $b] = array_map(function (int $channel) {
                $c = $channel / 255;

                return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }, sscanf($hex, '#%02x%02x%02x'));

            return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        };

        [$light, $dark] = [max($luminance($a), $luminance($b)), min($luminance($a), $luminance($b))];

        return ($light + 0.05) / ($dark + 0.05);
    }

    public function font(string $role): string
    {
        $font = (string) $this->get("fonts.{$role}");

        return array_key_exists($font, self::FONTS) ? $font : (string) data_get(self::defaults(), "fonts.{$role}");
    }

    public static function validColor(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : null;
    }

    /**
     * Font Awesome class list such as "fas fa-shopping-cart" or "fa-solid fa-bolt".
     */
    public static function validIcon(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^(fa[srlbdt]?|fa-(solid|regular|light|brands|duotone|thin))( fa-[a-z0-9-]+){1,3}$/', $value);
    }

    /**
     * "232, 40, 26" for rgba(var(--x), .5).
     */
    public static function rgb(string $hex): string
    {
        return implode(', ', sscanf($hex, '#%02x%02x%02x'));
    }

    /**
     * Darken (negative) or lighten (positive) a colour by a fraction.
     */
    public static function shade(string $hex, float $amount): string
    {
        $channels = sscanf($hex, '#%02x%02x%02x');

        return '#'.implode('', array_map(function (int $channel) use ($amount) {
            $value = $amount < 0 ? $channel * (1 + $amount) : $channel + (255 - $channel) * $amount;

            return str_pad(dechex((int) round(max(0, min(255, $value)))), 2, '0', STR_PAD_LEFT);
        }, $channels));
    }

    /**
     * Preview settings for an admin who may edit the theme, or null.
     *
     * @return array<string, mixed>|null
     */
    private function previewSettings(): ?array
    {
        try {
            if (! app()->bound('session') || ! request()->hasSession() || ! session()->has(self::PREVIEW_KEY)) {
                return null;
            }

            $user = auth('web')->user();
            $preview = session(self::PREVIEW_KEY);

            return $user && method_exists($user, 'hasPermission') && $user->hasPermission('efront_theme.edit') && is_array($preview) ? $preview : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function saved(): array
    {
        try {
            $json = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::where('key', self::SETTING_KEY)->value('value'));
        } catch (Throwable) {
            return []; // no database (minimal error page) — use defaults
        }

        $saved = is_string($json) ? json_decode($json, true) : null;

        return is_array($saved) ? $saved : [];
    }
}
