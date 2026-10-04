<?php

namespace ME\Efront\Http\Controllers\Account;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ME\Efront\Http\Controllers\Controller;
use ME\Efront\Models\Address;

/**
 * Customer address book: shipping and billing addresses, one default of each type.
 */
class AddressController extends Controller
{
    public function index(): View
    {
        return view('efront::account.addresses.index', [
            'addresses' => efront()->customer()->addresses()->with(['division', 'district', 'upazila'])->get()->groupBy('type'),
        ]);
    }

    public function create(Request $request): View
    {
        $customer = efront()->customer();

        return view('efront::account.addresses.form', [
            'address' => new Address([
                'type' => array_key_exists($request->query('type'), Address::TYPES) ? $request->query('type') : 'shipping',
                'name' => $customer->name,
                'phone' => $customer->phone,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = efront()->customer();
        $data = $this->validated($request);

        $customer->saveAddress(new Address($data), $request->boolean('is_default'));

        return redirect()->route('efront.account.addresses')->with('success', 'Address saved.');
    }

    public function edit(Address $address): View
    {
        $this->authorizeAddress($address);

        return view('efront::account.addresses.form', ['address' => $address]);
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeAddress($address);
        $customer = efront()->customer();

        $typeChanged = $address->type !== $request->input('type');
        $address->fill([...$this->validated($request), 'is_default' => $typeChanged ? false : $address->is_default]);
        $customer->saveAddress($address, $request->boolean('is_default') || $address->is_default);

        return redirect()->route('efront.account.addresses')->with('success', 'Address updated.');
    }

    public function destroy(Address $address): RedirectResponse
    {
        $this->authorizeAddress($address);
        $customer = efront()->customer();
        $wasDefault = $address->is_default;

        $address->delete();

        if ($wasDefault && ($next = $customer->addresses()->ofType($address->type)->first())) {
            $next->makeDefault();
        }

        return back()->with('success', 'Address deleted.');
    }

    public function makeDefault(Address $address): RedirectResponse
    {
        $this->authorizeAddress($address);

        $address->makeDefault();

        return back()->with('success', 'Default '.strtolower(Address::TYPES[$address->type]).' address changed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Address::TYPES))],
            'label' => 'nullable|string|max:50',
            ...Address::rules(),
        ], Address::messages());

        $address = Address::fromInput($data);

        if (! $address->hasValidArea()) {
            throw ValidationException::withMessages(['upazila_id' => 'The upazila does not belong to the chosen district.']);
        }

        return [...$data, 'phone' => $address->phone];
    }

    private function authorizeAddress(Address $address): void
    {
        abort_unless($address->customer_id === efront()->customer()->id, 404);
    }
}
