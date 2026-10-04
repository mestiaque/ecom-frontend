<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\Ecom\Models\Page;

class PageController extends Controller
{
    public function show(Page $page): View|RedirectResponse
    {
        // The FAQ is managed as separate entries now (Website → FAQ); old /page/faq links go there
        if ($page->slug === 'faq') {
            return redirect()->route('efront.faq', status: 301);
        }

        abort_unless($page->is_active, 404);

        return view('efront::pages.show', ['page' => $page]);
    }

    public function contact(): View
    {
        return view('efront::pages.contact');
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:150',
            'message' => 'required|string|max:3000',
        ]);

        $storeEmail = ecom_setting('store_email');

        if ($storeEmail) {
            me_mail($storeEmail, 'Contact form: '.$data['subject'], '<p><strong>'.e($data['name']).'</strong> ('.e($data['email'])
                .(! empty($data['phone']) ? ', '.e($data['phone']) : '').') wrote:</p><p>'.nl2br(e($data['message'])).'</p>');
        }

        return back()->with('success', 'Thank you! Your message has been sent. We will get back to you soon.');
    }
}
