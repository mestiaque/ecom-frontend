<?php

namespace ME\Efront\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ME\Ecom\Models\Product;
use ME\Efront\Models\ThemeVersion;
use ME\Efront\Support\Theme;
use ME\Efront\Support\ThemeForm;
use ME\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin → Storefront Theme: edit as a draft with live preview, publish with version history,
 * presets, JSON export/import and readability (contrast) warnings.
 */
class ThemeController extends Controller
{
    public function __construct(private Theme $theme)
    {
        $this->middleware('authorization:efront_theme.edit');
    }

    public function edit(): View
    {
        $editing = $this->theme->editable();
        $product = Product::active()->latest('id')->value('slug');

        return view('efront::admin.theme', [
            'theme' => $editing,
            'defaults' => Theme::defaults(),
            'hasDraft' => $this->theme->draft() !== null,
            'draftSavedAt' => $this->theme->draftSavedAt(),
            'versions' => ThemeVersion::with('user:id,name')->latest('id')->take(ThemeVersion::KEEP)->get(),
            'presets' => Theme::presets(),
            'livePreset' => $this->theme->livePreset(),
            'editPreset' => $this->theme->draftPreset() ?? $this->theme->livePreset(),
            'contrastIssues' => $this->theme->contrastIssues($editing),
            'contrastChecks' => Theme::contrastChecks($editing),
            'previewPages' => array_filter([
                'Home' => route('efront.home'),
                'Shop' => route('efront.shop'),
                'Product' => $product ? route('efront.product', $product) : null,
                'Cart' => route('efront.cart'),
                'Login' => route('efront.account.login'),
                'Track order' => route('ecom.track.form'),
            ]),
        ]);
    }

    /**
     * "Save Draft" keeps the changes private; "Publish" makes them live and records a version.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate(['action' => ['required', Rule::in(['draft', 'publish'])], 'note' => 'nullable|string|max:120']);
        $settings = ThemeForm::validate($request->only(['theme', 'section_order']));

        // The form has no preset field: keep the record of the last applied preset
        if ($preset = $this->theme->editable()['preset'] ?? null) {
            $settings['preset'] = $preset;
        }

        if ($request->input('action') === 'draft') {
            $this->theme->saveDraft($settings);
            $this->theme->startPreview($settings);

            return back()->with('success', 'Draft saved — only you see it in the live preview. Publish to make it live.');
        }

        $before = Arr::dot($this->theme->published());
        $note = $request->input('note');

        // Publishing a newly applied preset: say so in the history when no note was typed
        $preset = $settings['preset']['key'] ?? null;
        if (blank($note) && $preset && $preset !== ($this->theme->published()['preset']['key'] ?? null)) {
            $note = 'Preset: '.Theme::presets()[$preset]['name'];
        }

        $version = $this->theme->publish($settings, $note, auth()->id());
        me_change_log('Storefront theme published (version #'.$version->id.')', 'efront.theme.publish')->record($before, Arr::dot($this->theme->published()));

        return $this->publishedRedirect('Theme published — your store now uses it.');
    }

    /**
     * Live preview: validate the unsaved form and show it to this admin only.
     */
    public function preview(Request $request): JsonResponse
    {
        try {
            $settings = ThemeForm::validate($request->only(['theme', 'section_order']));
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first(), 'errors' => $e->errors()], 422);
        }

        $this->theme->startPreview($settings);

        return response()->json(['ok' => true, 'issues' => $this->theme->contrastIssues($settings)]);
    }

    public function exitPreview(Request $request): RedirectResponse
    {
        $this->theme->stopPreview();
        $back = (string) $request->query('return');

        // Only return to a page on this site
        return redirect()->to(str_starts_with($back, url('/')) ? $back : route('efront.home'));
    }

    public function discardDraft(): RedirectResponse
    {
        $this->theme->discardDraft();
        $this->theme->stopPreview();

        return redirect()->route('efront.admin.theme.edit')->with('success', 'Draft discarded — showing the published theme.');
    }

    /**
     * Copy a preset's style, fonts and colours over the theme being edited, as a draft.
     */
    public function applyPreset(string $preset): RedirectResponse
    {
        abort_unless(isset(Theme::presets()[$preset]), 404);

        $settings = Theme::withPreset($this->theme->editable(), $preset);
        $settings['preset'] = ['key' => $preset, 'applied_at' => now()->toIso8601String()];
        $this->theme->saveDraft($settings);
        $this->theme->startPreview($settings);

        return redirect()->route('efront.admin.theme.edit')
            ->with('success', '"'.Theme::presets()[$preset]['name'].'" applied as a draft. Check the live preview, then Publish.');
    }

    public function export(Request $request): StreamedResponse
    {
        $settings = $request->boolean('draft') ? $this->theme->editable() : $this->theme->published();
        $json = json_encode([
            'efront_theme' => 1,
            'exported_at' => now()->toIso8601String(),
            'store' => efront()->storeName(),
            'settings' => $settings,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(fn () => print ($json), 'storefront-theme-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json']);
    }

    /**
     * Import a theme JSON file (from Export) as a draft. Every value is validated like the form.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|max:512']);

        $data = json_decode((string) file_get_contents($request->file('file')->getRealPath()), true);
        $settings = is_array($data['settings'] ?? null) ? $data['settings'] : $data;

        if (! is_array($settings) || ! isset($settings['colors'])) {
            return back()->with('error', 'This is not a storefront theme file (use a file made with Export).');
        }

        try {
            $settings = ThemeForm::validate(ThemeForm::toInput(Theme::merge($settings)));
        } catch (ValidationException $e) {
            return back()->with('error', 'The theme file has invalid values: '.$e->validator->errors()->first());
        }

        if (isset(Theme::presets()[$data['settings']['preset']['key'] ?? $data['preset']['key'] ?? ''])) {
            $settings['preset'] = $data['settings']['preset'] ?? $data['preset'];
        }

        $this->theme->saveDraft($settings);
        $this->theme->startPreview($settings);

        return redirect()->route('efront.admin.theme.edit')->with('success', 'Theme imported as a draft. Check the live preview, then Publish.');
    }

    /**
     * Open the store showing an old version (to this admin only).
     */
    public function previewVersion(ThemeVersion $version): RedirectResponse
    {
        $this->theme->startPreview($version->settings);

        return redirect()->route('efront.home');
    }

    public function restore(ThemeVersion $version): RedirectResponse
    {
        $before = Arr::dot($this->theme->published());
        $settings = Theme::merge($version->settings);
        $new = $this->theme->publish($settings, 'Restored version #'.$version->id, auth()->id());
        me_change_log('Storefront theme restored from version #'.$version->id.' (now #'.$new->id.')', 'efront.theme.restore')->record($before, Arr::dot($this->theme->published()));

        return $this->publishedRedirect('Version #'.$version->id.' restored and published.');
    }

    public function reset(): RedirectResponse
    {
        $before = Arr::dot($this->theme->published());
        $this->theme->reset(auth()->id());
        me_change_log('Storefront theme reset to default', 'efront.theme.reset')->record($before, Arr::dot($this->theme->published()));

        return redirect()->route('efront.admin.theme.edit')->with('success', 'Theme reset to the default look (you can undo this from History).');
    }

    /**
     * After publishing, also warn about hard-to-read colour pairs.
     */
    private function publishedRedirect(string $message): RedirectResponse
    {
        $redirect = redirect()->route('efront.admin.theme.edit')->with('success', $message);
        $issues = $this->theme->contrastIssues();

        return $issues
            ? $redirect->with('error', 'Hard to read: '.collect($issues)->pluck('label')->implode(', ').'. See the Colours / Buttons tabs.')
            : $redirect;
    }
}
