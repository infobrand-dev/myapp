<form method="POST" action="{{ route('storefront.public.checkout.store', $product) }}" class="row g-3">
    @csrf
    <input type="hidden" name="qty" value="1">
    <input type="hidden" name="checkout_channel" value="{{ $checkoutChannel ?? 'direct_offer' }}">
    <input type="hidden" name="fulfillment_method" value="{{ $offer['delivery_type'] === 'physical' ? 'pickup' : 'pickup' }}">

    <div class="col-md-6">
        <label class="form-label">Nama</label>
        <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email') }}">
    </div>
    <div class="col-12">
        <label class="form-label">No. WhatsApp</label>
        <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}" required>
    </div>
    <div class="col-12">
        <label class="form-label">Catatan</label>
        <textarea name="customer_note" class="form-control" rows="3">{{ old('customer_note') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Pembayaran</label>
        <select name="payment_method" class="form-select" required>
            <option value="manual">Manual / transfer</option>
            @if($activeGatewayProvider)
                <option value="{{ $activeGatewayProvider }}">{{ $activeGatewayLabel ?: $activeGatewayProvider }}</option>
            @endif
        </select>
    </div>
    <div class="col-12 d-grid">
        <button type="submit" class="btn btn-lg rounded-pill" style="background: {{ $storefrontBrand['accent'] ?? '#223756' }}; color: #fff;">
            {{ $offer['cta_label'] ?: 'Beli sekarang' }}
        </button>
    </div>
</form>
