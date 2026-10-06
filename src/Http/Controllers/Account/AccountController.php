<?php

namespace ME\Efront\Http\Controllers\Account;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use ME\Ecom\Enums\OrderStatus;
use ME\Ecom\Models\Order;
use ME\Ecom\Services\OrderService;
use ME\Efront\Http\Controllers\Controller;
use ME\Efront\Support\Phone;

/**
 * Customer account: dashboard, profile, password, orders.
 */
class AccountController extends Controller
{
    public function dashboard(): View
    {
        $customer = efront()->customer();

        return view('efront::account.dashboard', [
            'customer' => $customer,
            'stats' => [
                'orders' => $customer->orders()->count(),
                'active' => $customer->orders()->whereIn('status', [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped])->count(),
                'spent' => (float) $customer->orders()->countsAsSale()->sum('total'),
                'wishlist' => count(efront()->wishlistIds()),
            ],
            'defaultAddress' => $customer->addresses()->ofType('shipping')->with(['division', 'district', 'upazila'])->first(),
            'recentOrders' => $customer->orders()->withCount('items')->latest()->take(5)->get(),
        ]);
    }

    public function profile(): View
    {
        return view('efront::account.profile', ['customer' => efront()->customer()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = efront()->customer();
        $request->merge(['phone' => Phone::normalize((string) $request->input('phone'))]);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => ['required', 'string', Phone::RULE, Rule::unique('ecom_customers', 'phone')->ignore($customer->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('ecom_customers', 'email')->ignore($customer->id)],
        ], ['phone.regex' => Phone::MESSAGE]);

        // A changed phone / email is no longer verified
        $customer->update([
            ...$data,
            'phone_verified_at' => $data['phone'] === $customer->phone ? $customer->phone_verified_at : null,
            'email_verified_at' => ($data['email'] ?? null) === $customer->email ? $customer->email_verified_at : null,
        ]);

        return back()->with('success', 'Your profile has been updated.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:'.config('efront.avatar_max_kb', 2048),
        ], ['avatar.max' => 'The photo must be smaller than '.round(config('efront.avatar_max_kb', 2048) / 1024, 1).' MB.']);

        // Photo is kept in me_media (metheme); the old one goes to the media trash
        efront()->customer()->replaceMedia($request->file('avatar'), 'avatar');

        return back()->with('success', 'Your profile photo has been updated.');
    }

    public function removeAvatar(): RedirectResponse
    {
        efront()->customer()->clearMedia('avatar');

        return back()->with('success', 'Your profile photo has been removed.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $customer = efront()->customer();

        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        if (! Hash::check($data['current_password'], (string) $customer->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        $customer->update(['password' => $data['password']]);

        return back()->with('success', 'Your password has been changed.');
    }

    public function orders(Request $request): View
    {
        return view('efront::account.orders.index', [
            'orders' => efront()->customer()->orders()
                ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
                ->withCount('items')
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function showOrder(Order $order): View
    {
        $this->authorizeOrder($order);

        $order->load(['items.product.primaryImage', 'shippingZone']);

        return view('efront::account.orders.show', [
            'order' => $order,
            'history' => $order->notes()->whereNotNull('status')->oldest('id')->get(['status', 'created_at']),
            'steps' => [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered],
        ]);
    }

    /**
     * Customers can cancel only while the order is still pending.
     */
    public function cancelOrder(Order $order, OrderService $orders): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->status !== OrderStatus::Pending) {
            return back()->with('error', 'This order can no longer be cancelled. Please contact support.');
        }

        $orders->changeStatus($order, OrderStatus::Cancelled, 'Cancelled by customer');

        return back()->with('success', "Order {$order->order_number} has been cancelled.");
    }

    private function authorizeOrder(Order $order): void
    {
        abort_unless($order->customer_id === efront()->customer()->id, 404);
    }
}
