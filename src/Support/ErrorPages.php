<?php

namespace ME\Efront\Support;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Storefront error pages. Hooked into Laravel's exception handler by EfrontServiceProvider:
 * storefront requests get efront-themed pages, everything else (admin under me_prefix(), metheme's
 * auth pages, API) returns null so the app's normal error pages (metheme) are used unchanged.
 */
class ErrorPages
{
    /** metheme auth pages that live outside the admin prefix */
    private const METHEME_PATHS = ['login', 'logout', 'register', 'send-otp', 'verify-otp', 'forget-password', 'reset-password', 'verify-reset-otp', 'password'];

    /** Pages built with the full storefront layout (header, footer, menus) */
    private const FULL_PAGES = [403, 404, 419, 429];

    public function render(Throwable $e, Request $request): ?Response
    {
        if ($request->expectsJson() || $request->is('api/*') || ! $this->isStorefront($request)) {
            return null;
        }

        if ($e instanceof HttpResponseException || $e instanceof AuthenticationException || $e instanceof ValidationException) {
            return null;
        }

        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

        // Real errors keep Laravel's debug screen during development
        if ($status >= 500 && $status !== 503 && config('app.debug')) {
            return null;
        }

        // Expired form (CSRF): back to the form with the typed values instead of an error page
        if ($status === 419 && ! $request->isMethod('GET')) {
            return back()->withInput($request->except(['password', 'password_confirmation', 'current_password', '_token']))
                ->with('error', 'Your session expired. Please try again.');
        }

        $data = [
            'status' => $status,
            'retryAfter' => (int) ($headers['Retry-After'] ?? 0),
            'page' => $this->texts($status),
        ];

        if (in_array($status, self::FULL_PAGES, true)) {
            try {
                return response(view('efront::errors.page', $data)->render(), $status, $headers);
            } catch (Throwable) {
                // The full layout itself failed (e.g. database down) — fall through to the simple page
            }
        }

        return response(view('efront::errors.minimal', $data)->render(), $status, $headers);
    }

    /**
     * Admin panel (me_prefix()) and metheme auth pages keep metheme's error pages.
     */
    private function isStorefront(Request $request): bool
    {
        $route = $request->route();

        if ($route && $route->getName() && $route->getName() !== 'efront.fallback') {
            return str_starts_with($route->getName(), 'efront.') || str_starts_with($route->getName(), 'ecom.track.');
        }

        $path = trim($request->path(), '/');
        $adminPrefix = trim(function_exists('me_prefix') ? (string) me_prefix() : 'admin', '/');

        if ($adminPrefix === '') {
            return false;
        }

        if ($path === $adminPrefix || str_starts_with($path, $adminPrefix.'/')) {
            return false;
        }

        return ! in_array(explode('/', $path)[0], self::METHEME_PATHS, true);
    }

    /**
     * @return array{icon: string, title: string, text: string}
     */
    private function texts(int $status): array
    {
        return match ($status) {
            403 => ['icon' => 'fas fa-lock', 'title' => 'Access denied', 'text' => 'You do not have permission to open this page.'],
            404 => ['icon' => 'fas fa-map-signs', 'title' => 'Page not found', 'text' => 'The page you are looking for was moved, removed or never existed.'],
            419 => ['icon' => 'fas fa-hourglass-end', 'title' => 'Page expired', 'text' => 'This page was open for too long. Please refresh and try again.'],
            429 => ['icon' => 'fas fa-hand-paper', 'title' => 'Slow down a little', 'text' => 'Too many requests in a short time. Please wait a moment and try again.'],
            503 => ['icon' => 'fas fa-tools', 'title' => 'We will be right back', 'text' => 'The shop is getting a quick update. Please check back in a few minutes.'],
            default => $status >= 500
                ? ['icon' => 'fas fa-bug', 'title' => 'Something went wrong', 'text' => 'An unexpected error happened on our side. Please try again in a moment.']
                : ['icon' => 'fas fa-exclamation-circle', 'title' => 'Something is not right', 'text' => 'We could not complete this request.'],
        };
    }
}
