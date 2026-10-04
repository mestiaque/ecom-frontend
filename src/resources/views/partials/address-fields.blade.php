{{--
    Address form fields. $prefix = "shipping" / "billing" makes names like shipping[name]; "" for plain names.
    $address = model to edit (optional). Division → District → Upazila come from metheme's /api/geo.
--}}
@php
    $prefix = $prefix ?? '';
    $address = $address ?? null;
    $name = fn (string $field) => $prefix ? "{$prefix}[{$field}]" : $field;
    $key = fn (string $field) => $prefix ? "{$prefix}.{$field}" : $field;
    $value = fn (string $field) => old($key($field), $address?->{$field});
    $id = fn (string $field) => ($prefix ?: 'address').'_'.$field;
@endphp
<div class="row g-3" data-geo-fields>
    <div class="col-sm-6">
        <label class="flbl" for="{{ $id('name') }}">Full name *</label>
        <input type="text" id="{{ $id('name') }}" name="{{ $name('name') }}" class="fctrl @error($key('name')) is-invalid @enderror" value="{{ $value('name') }}" autocomplete="name" required>
        @error($key('name'))<div class="ef-error">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-6">
        <label class="flbl" for="{{ $id('phone') }}">Mobile number *</label>
        <input type="tel" id="{{ $id('phone') }}" name="{{ $name('phone') }}" class="fctrl @error($key('phone')) is-invalid @enderror" value="{{ $value('phone') }}" placeholder="01XXXXXXXXX" autocomplete="tel" required>
        @error($key('phone'))<div class="ef-error">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-4">
        <label class="flbl" for="{{ $id('division_id') }}">Division *</label>
        <select id="{{ $id('division_id') }}" name="{{ $name('division_id') }}" class="fctrl @error($key('division_id')) is-invalid @enderror" data-geo-level="division" data-selected="{{ $value('division_id') }}" required>
            <option value="">Loading…</option>
        </select>
        @error($key('division_id'))<div class="ef-error">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-4">
        <label class="flbl" for="{{ $id('district_id') }}">District *</label>
        <select id="{{ $id('district_id') }}" name="{{ $name('district_id') }}" class="fctrl @error($key('district_id')) is-invalid @enderror" data-geo-level="district" data-selected="{{ $value('district_id') }}" required disabled>
            <option value="">Select division first</option>
        </select>
        @error($key('district_id'))<div class="ef-error">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-4">
        <label class="flbl" for="{{ $id('upazila_id') }}">Upazila / Thana *</label>
        <select id="{{ $id('upazila_id') }}" name="{{ $name('upazila_id') }}" class="fctrl @error($key('upazila_id')) is-invalid @enderror" data-geo-level="upazila" data-selected="{{ $value('upazila_id') }}" required disabled>
            <option value="">Select district first</option>
        </select>
        @error($key('upazila_id'))<div class="ef-error">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-7">
        <label class="flbl" for="{{ $id('post_office') }}">Post office</label>
        <input type="text" id="{{ $id('post_office') }}" name="{{ $name('post_office') }}" class="fctrl @error($key('post_office')) is-invalid @enderror" value="{{ $value('post_office') }}" placeholder="e.g. New Market">
        @error($key('post_office'))<div class="ef-error">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-5">
        <label class="flbl" for="{{ $id('postal_code') }}">Postal code</label>
        <input type="text" id="{{ $id('postal_code') }}" name="{{ $name('postal_code') }}" class="fctrl @error($key('postal_code')) is-invalid @enderror" value="{{ $value('postal_code') }}" placeholder="e.g. 1205" inputmode="numeric" maxlength="6" autocomplete="postal-code">
        @error($key('postal_code'))<div class="ef-error">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="flbl" for="{{ $id('address_line') }}">Address *</label>
        <textarea id="{{ $id('address_line') }}" name="{{ $name('address_line') }}" rows="2" class="fctrl @error($key('address_line')) is-invalid @enderror" placeholder="House, road, block, area" autocomplete="street-address" required>{{ $value('address_line') }}</textarea>
        @error($key('address_line'))<div class="ef-error">{{ $message }}</div>@enderror
    </div>
</div>
