@extends('efront::account.layout')

@section('title', 'Address Book')
@section('account_title', 'Address Book')

@section('account')
    @foreach(\ME\Efront\Models\Address::TYPES as $type => $label)
        <div class="fcard mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="ef-summary-title mb-0">{{ $label }} Addresses</h5>
                <a href="{{ route('efront.account.addresses.create', ['type' => $type]) }}" class="ef-btn-outline ef-btn-sm"><i class="fas fa-plus me-1"></i>Add {{ strtolower($label) }} address</a>
            </div>
            @php($list = $addresses->get($type, collect()))
            @if($list->isEmpty())
                <p class="text-muted mb-0">No {{ strtolower($label) }} address yet.</p>
            @else
                <div class="ef-address-grid">
                    @foreach($list as $address)
                        <div @class(['ef-address-option', 'is-default' => $address->is_default])>
                            <span class="ef-address-body">@include('efront::partials.address-card')</span>
                            <div class="ef-address-actions">
                                <a href="{{ route('efront.account.addresses.edit', $address) }}"><i class="fas fa-pen me-1"></i>Edit</a>
                                @unless($address->is_default)
                                    <form method="POST" action="{{ route('efront.account.addresses.default', $address) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"><i class="fas fa-star me-1"></i>Make default</button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('efront.account.addresses.destroy', $address) }}" onsubmit="return confirm('Delete this address?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger"><i class="fas fa-trash-alt me-1"></i>Delete</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
@endsection
