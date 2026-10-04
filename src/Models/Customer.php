<?php

namespace ME\Efront\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ME\Ecom\Models\Customer as EcomCustomer;
use ME\Ecom\Models\Product;

/**
 * Shop customer that can log in to the storefront ("customer" guard). Same ecom_customers row
 * the admin panel shows, with login, password reset and wishlist added.
 */
class Customer extends EcomCustomer implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable;
    use CanResetPassword;

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->latest('id');
    }

    public function defaultAddress(string $type = 'shipping'): ?Address
    {
        return $this->addresses()->ofType($type)->first();
    }

    /**
     * Save an address to the book; it becomes the default when asked or when it is the first of its type.
     */
    public function saveAddress(Address $address, bool $makeDefault = false): Address
    {
        $address->customer_id = $this->id;
        $address->save();

        if ($makeDefault || ! $this->addresses()->ofType($address->type)->where('is_default', true)->whereKeyNot($address->id)->exists()) {
            $address->makeDefault();
        }

        return $address;
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'efront_wishlists')->withTimestamps()->latest('efront_wishlists.created_at');
    }

    /**
     * Reset link by e-mail through metheme's mail settings.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = route('efront.account.password.reset', ['token' => $token, 'email' => $this->email]);
        $minutes = config('auth.passwords.customers.expire', 60);

        me_mail(
            $this->email,
            'Reset your password',
            '<p>Hi '.e($this->name).',</p>'
            .'<p>We received a request to reset the password of your '.e(efront()->storeName()).' account.</p>'
            .'<p><a href="'.e($url).'">Reset password</a></p>'
            ."<p>This link expires in {$minutes} minutes. If you did not ask for it, you can ignore this e-mail.</p>",
        );
    }
}
