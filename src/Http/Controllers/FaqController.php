<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\View\View;
use ME\Ecom\Models\Faq;

/**
 * FAQ page — entries come from the admin panel (Website → FAQ).
 */
class FaqController extends Controller
{
    public function index(): View
    {
        return view('efront::pages.faq', [
            'groups' => Faq::active()->ordered()->get()->groupBy('category_name'),
        ]);
    }
}
