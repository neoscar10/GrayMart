@php
    $imgs = is_array($product->images) ? $product->images : [];
    $main = count($imgs) ? Storage::url($imgs[0]) : asset('assets/images/thumbs/product-placeholder.png');
    $base = (float) $product->price;
    $deal = (!is_null($product->buy_now_price) && $product->buy_now_price > 0 && $product->buy_now_price < $product->price)
        ? (float) $product->buy_now_price
        : null;
@endphp

<div class="container container-lg py-5">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="border rounded-16 p-3 bg-white">
                <img src="{{ $main }}" class="w-100 rounded-12 object-fit-cover" alt="{{ $product->name }}">
            </div>

            @if(count($imgs) > 1)
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    @foreach($imgs as $i)
                        <img src="{{ Storage::url($i) }}" width="80" height="80" class="rounded-8 object-fit-cover border">
                    @endforeach
                </div>
            @endif
        </div>

        <div class="col-md-6">
            <h3 class="mb-2">{{ $product->name }}</h3>
            <div class="mb-3 text-muted small">
                @if($product->category) <span>{{ $product->category->name }}</span> @endif
                @if($product->vendor) <span> • by {{ $product->vendor->name ?? 'Vendor' }}</span> @endif
            </div>

            <div class="mb-3">
                @if($deal)
                    <div class="fs-4 fw-semibold">₦{{ number_format($deal, 2) }}</div>
                    <div class="text-decoration-line-through text-muted">₦{{ number_format($base, 2) }}</div>
                @else
                    <div class="fs-4 fw-semibold">₦{{ number_format($base, 2) }}</div>
                @endif
            </div>

            <div class="mb-4">
                {!! nl2br(e(Str::limit($product->description, 600))) !!}
            </div>

            @if($product->is_reserved)
                <a href="#" class="btn btn-outline-secondary rounded-pill px-4 py-2" onclick="return false;">
                    Reserved – see details
                </a>
            @else
                <button wire:click="addToCart" class="btn btn-dark rounded-pill px-4 py-2">
                    <span wire:loading.remove>Add to Cart</span>
                    <span wire:loading><i class="fas fa-spinner fa-spin me-1"></i> Adding…</span>
                </button>
            @endif

            @if (session('success'))
                <div class="alert alert-success mt-3 mb-0">{{ session('success') }}</div>
            @endif
        </div>
    </div>
</div>