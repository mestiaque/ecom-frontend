<?php

namespace ME\Efront\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use ME\Efront\Support\Phone;
use ME\Models\GeoLocation;

/**
 * Address book entry (shipping or billing). Also used unsaved at checkout for a typed address,
 * so both share the same validation and formatting.
 */
class Address extends Model
{
    public const TYPES = ['shipping' => 'Shipping', 'billing' => 'Billing'];

    protected $table = 'efront_addresses';

    protected $fillable = [
        'customer_id', 'type', 'label', 'name', 'phone', 'division_id', 'district_id', 'upazila_id',
        'post_office', 'postal_code', 'address_line', 'is_default',
    ];

    protected $casts = ['is_default' => 'boolean', 'division_id' => 'integer', 'district_id' => 'integer', 'upazila_id' => 'integer'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(GeoLocation::class, 'division_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(GeoLocation::class, 'district_id');
    }

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(GeoLocation::class, 'upazila_id');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Validation rules for an address form; $prefix is "shipping." / "billing." at checkout.
     *
     * @return array<string, mixed>
     */
    public static function rules(string $prefix = ''): array
    {
        $geo = fn (string $type) => ['required', 'integer', fn (string $attribute, mixed $value, Closure $fail) => GeoLocation::whereKey($value)->ofType($type)->exists() ?: $fail("Choose a valid {$type}.")];

        return [
            $prefix.'name' => 'required|string|max:100',
            $prefix.'phone' => ['required', 'string', Phone::RULE],
            $prefix.'division_id' => $geo(GeoLocation::TYPE_DIVISION),
            $prefix.'district_id' => $geo(GeoLocation::TYPE_DISTRICT),
            $prefix.'upazila_id' => $geo(GeoLocation::TYPE_UPAZILA),
            $prefix.'post_office' => 'nullable|string|max:100',
            $prefix.'postal_code' => 'nullable|digits_between:4,6',
            $prefix.'address_line' => 'required|string|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(string $prefix = ''): array
    {
        return [
            $prefix.'phone.regex' => Phone::MESSAGE,
            $prefix.'division_id.required' => 'Choose a division.',
            $prefix.'district_id.required' => 'Choose a district.',
            $prefix.'upazila_id.required' => 'Choose an upazila / thana.',
            $prefix.'address_line.required' => 'Write the house, road and area.',
        ];
    }

    /**
     * Unsaved address from validated input, with its geo names loaded. Checks that the upazila
     * belongs to the district and the district to the division.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromInput(array $data): self
    {
        $address = new self([...$data, 'phone' => Phone::normalize((string) $data['phone'])]);
        $places = GeoLocation::whereIn('id', [$address->division_id, $address->district_id, $address->upazila_id])->get()->keyBy('id');

        $address->setRelation('division', $places->get($address->division_id));
        $address->setRelation('district', $places->get($address->district_id));
        $address->setRelation('upazila', $places->get($address->upazila_id));

        return $address;
    }

    public function hasValidArea(): bool
    {
        return $this->upazila?->parent_id === $this->district_id && $this->district?->parent_id === $this->division_id;
    }

    /**
     * "Upazila, District"
     */
    public function getCityAttribute(): string
    {
        return collect([$this->upazila?->name, $this->district?->name])->filter()->implode(', ');
    }

    /**
     * "Upazila, District, Division"
     */
    public function getAreaAttribute(): string
    {
        return collect([$this->upazila?->name, $this->district?->name, $this->division?->name])->filter()->implode(', ');
    }

    /**
     * Address line with post office and postal code: "House 5, Road 2, Dhanmondi (Post office: New Market - 1205)".
     */
    public function getStreetAttribute(): string
    {
        $post = trim(($this->post_office ? 'Post office: '.$this->post_office : '').($this->postal_code ? ' - '.$this->postal_code : ''), ' -');

        return trim($this->address_line.($post !== '' ? " ({$post})" : ''));
    }

    /**
     * One-line address for orders, invoices and the courier.
     */
    public function getFullAttribute(): string
    {
        return collect([$this->street, $this->area])->filter()->implode(', ');
    }

    /**
     * Make this the default of its type and unset the old default. The default shipping address is
     * also copied to the customer row (address / city) so the admin panel shows it.
     */
    public function makeDefault(): void
    {
        DB::transaction(function (): void {
            self::where('customer_id', $this->customer_id)->ofType($this->type)->whereKeyNot($this->id)->update(['is_default' => false]);
            $this->forceFill(['is_default' => true])->save();

            if ($this->type === 'shipping') {
                $this->loadMissing(['division', 'district', 'upazila']);
                $this->customer()->update(['address' => $this->street, 'city' => $this->area]);
            }
        });
    }
}
