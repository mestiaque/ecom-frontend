<?php

namespace ME\Efront\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Converts between the admin theme form (theme[...] + section_order[]) and the stored settings array,
 * and validates it. Used by save, live preview and JSON import so all three follow the same rules.
 */
class ThemeForm
{
    private const HEX = 'regex:/^#[0-9a-fA-F]{6}$/';

    /**
     * Validate form input and return the settings array (same shape as Config/theme.php).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function validate(array $input): array
    {
        Validator::make($input, self::rules(), [
            'regex' => 'Colours must look like #ff0000.',
        ])->validate();

        return self::toSettings($input);
    }

    /**
     * Settings array → form input (what the admin form would post). Used for import and tests.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public static function toInput(array $settings): array
    {
        foreach ($settings['sections'] ?? [] as $key => $section) {
            $settings['sections'][$key]['custom_bg'] = filled($section['bg'] ?? null);
            $settings['sections'][$key]['custom_heading'] = filled($section['heading'] ?? null);
            $settings['sections'][$key]['bg'] = $section['bg'] ?? '#ffffff';
            $settings['sections'][$key]['heading'] = $section['heading'] ?? '#1a1a1a';
        }

        array_walk_recursive($settings, function (&$value) {
            $value = is_bool($value) ? ($value ? '1' : '0') : $value;
        });

        return ['theme' => $settings, 'section_order' => array_keys($settings['sections'] ?? [])];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $defaults = Theme::defaults();
        $hex = ['required', self::HEX];
        $text = ['nullable', 'string', 'max:200'];
        $icon = fn (bool $required) => [$required ? 'required' : 'nullable', 'string', 'max:60', function ($attribute, $value, $fail) {
            if (filled($value) && ! Theme::validIcon($value)) {
                $fail('Use a Font Awesome icon class, e.g. "fas fa-shopping-cart".');
            }
        }];

        $rules = [
            'theme' => 'required|array',
            'theme.style' => ['required', Rule::in(['glass', 'classic'])],
            'theme.fonts.heading' => ['required', Rule::in(array_keys(Theme::FONTS))],
            'theme.fonts.body' => ['required', Rule::in(array_keys(Theme::FONTS))],
            'theme.fonts.label_style' => ['required', Rule::in(['caps', 'script'])],
            'theme.shape.card_radius' => 'required|integer|min:0|max:40',
            'theme.shape.button_radius' => 'required|integer|min:0|max:50',
            'theme.glass.glow' => 'required|integer|min:0|max:100',
            'theme.card.image_fit' => ['required', Rule::in(['contain', 'cover'])],
            'theme.card.image_height' => 'required|integer|min:120|max:420',
            'theme.card.image_padding' => 'required|integer|min:0|max:40',
            'theme.card.image_bg' => $hex,
            'section_order' => 'required|array',
            'section_order.*' => ['string', Rule::in(array_keys($defaults['sections']))],
        ];

        foreach (['colors', 'footer'] as $group) {
            foreach (array_keys($defaults[$group]) as $key) {
                $rules["theme.{$group}.{$key}"] = $hex;
            }
        }
        foreach (array_keys($defaults['glass']) as $key) {
            if ($key !== 'glow') {
                $rules["theme.glass.{$key}"] = $hex;
            }
        }
        foreach (array_keys($defaults['header']) as $key) {
            if ($key !== 'topbar') {
                $rules["theme.header.{$key}"] = $hex;
            }
        }
        foreach (array_keys($defaults['buttons']) as $key) {
            $rules["theme.buttons.{$key}.bg"] = $hex;
            $rules["theme.buttons.{$key}.text"] = $hex;
            $rules["theme.buttons.{$key}.label"] = ['nullable', 'string', 'max:40'];
            $rules["theme.buttons.{$key}.icon"] = $icon(false);
        }
        foreach (array_keys($defaults['icons']) as $key) {
            $rules["theme.icons.{$key}"] = $icon(true);
        }
        foreach ($defaults['sections'] as $key => $section) {
            foreach (['label', 'title', 'highlight', 'text', 'button', 'badge'] as $field) {
                if (array_key_exists($field, $section)) {
                    $rules["theme.sections.{$key}.{$field}"] = $field === 'text' ? ['nullable', 'string', 'max:400'] : $text;
                }
            }
            if (array_key_exists('limit', $section)) {
                $rules["theme.sections.{$key}.limit"] = 'required|integer|min:1|max:24';
            }
            $rules["theme.sections.{$key}.bg"] = ['nullable', self::HEX];
            $rules["theme.sections.{$key}.heading"] = ['nullable', self::HEX];
            foreach (array_keys($section['items'] ?? []) as $i) {
                $rules["theme.sections.{$key}.items.{$i}.icon"] = $icon(false);
                $rules["theme.sections.{$key}.items.{$i}.title"] = ['nullable', 'string', 'max:60'];
                $rules["theme.sections.{$key}.items.{$i}.text"] = ['nullable', 'string', 'max:120'];
            }
        }

        return $rules;
    }

    /**
     * Build the settings array from validated form input.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function toSettings(array $input): array
    {
        $defaults = Theme::defaults();
        $get = fn (string $key) => data_get($input, "theme.{$key}");
        $bool = fn (string $key) => filter_var($get($key), FILTER_VALIDATE_BOOLEAN);
        $color = fn (string $key) => strtolower((string) $get($key));

        $settings = [
            'style' => $get('style'),
            'fonts' => [
                'heading' => $get('fonts.heading'),
                'body' => $get('fonts.body'),
                'label_style' => $get('fonts.label_style'),
            ],
            'shape' => [
                'card_radius' => (int) $get('shape.card_radius'),
                'button_radius' => (int) $get('shape.button_radius'),
            ],
            'card' => [
                'image_fit' => $get('card.image_fit'),
                'image_height' => (int) $get('card.image_height'),
                'image_padding' => (int) $get('card.image_padding'),
                'image_bg' => $color('card.image_bg'),
                'show_category' => $bool('card.show_category'),
                'show_description' => $bool('card.show_description'),
                'show_rating' => $bool('card.show_rating'),
            ],
        ];

        foreach (['colors', 'footer'] as $group) {
            foreach (array_keys($defaults[$group]) as $key) {
                $settings[$group][$key] = $color("{$group}.{$key}");
            }
        }
        foreach (array_keys($defaults['glass']) as $key) {
            $settings['glass'][$key] = $key === 'glow' ? (int) $get('glass.glow') : $color("glass.{$key}");
        }
        foreach (array_keys($defaults['header']) as $key) {
            $settings['header'][$key] = $key === 'topbar' ? $bool('header.topbar') : $color("header.{$key}");
        }
        foreach (array_keys($defaults['buttons']) as $key) {
            $settings['buttons'][$key] = [
                'label' => filled($get("buttons.{$key}.label")) ? trim($get("buttons.{$key}.label")) : null,
                'icon' => filled($get("buttons.{$key}.icon")) ? trim($get("buttons.{$key}.icon")) : null,
                'bg' => $color("buttons.{$key}.bg"),
                'text' => $color("buttons.{$key}.text"),
            ];
        }
        foreach (array_keys($defaults['icons']) as $key) {
            $settings['icons'][$key] = trim((string) $get("icons.{$key}"));
        }

        // Sections in the posted order; a section missing from the form keeps its default place at the end
        $order = array_values(array_unique(array_merge((array) ($input['section_order'] ?? []), array_keys($defaults['sections']))));
        foreach ($order as $key) {
            $section = [];

            foreach ($defaults['sections'][$key] as $field => $default) {
                $value = $get("sections.{$key}.{$field}");
                $section[$field] = match (true) {
                    in_array($field, ['enabled', 'filter'], true) => $bool("sections.{$key}.{$field}"),
                    $field === 'limit' => (int) $value,
                    in_array($field, ['bg', 'heading'], true) => $bool("sections.{$key}.custom_{$field}") && filled($value) ? strtolower($value) : null,
                    $field === 'items' => collect($default)->keys()->map(fn ($i) => [
                        'icon' => (string) $get("sections.{$key}.items.{$i}.icon"),
                        'title' => (string) $get("sections.{$key}.items.{$i}.title"),
                        'text' => (string) $get("sections.{$key}.items.{$i}.text"),
                    ])->all(),
                    default => (string) $value,
                };
            }

            $settings['sections'][$key] = $section;
        }

        return $settings;
    }
}
