@php use Illuminate\Support\Facades\Storage; @endphp

<div>
    {{-- Flash --}}
    <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index:1080; width: min(520px, 92%);" aria-live="polite" aria-atomic="true">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow rounded-12 d-flex align-items-start gap-2" role="alert">
                <i class="ph-fill ph-check-circle fs-5 mt-1"></i>
                <div class="flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <script>
              setTimeout(() => document.querySelector('.alert .btn-close')?.click(), 2200);
            </script>
        @endif
    </div>

    {{-- ===== Hero / Banner ===== --}}
    @php
        $banner = $this->vendor->banner_url ?? asset('assets/images/thumbs/vendor-banner-placeholder.jpg');
        $logo   = $this->vendor->logo_url   ?? asset('assets/images/thumbs/vendor-placeholder.png');
    @endphp
    <section class="position-relative rounded-16 overflow-hidden mb-32">
        <div class="w-100" style="height: 260px; background: url('{{ $banner }}') center/cover no-repeat;"></div>
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(180deg, rgba(0,0,0,.20) 0%, rgba(0,0,0,.35) 100%);"></div>

        <div class="container container-lg">
            <div class="position-relative" style="transform: translateY(-48px);">
                <div class="d-flex align-items-end gap-16">
                    <img src="{{ $logo }}" alt="{{ $this->vendor->store_name }}" class="rounded-circle border border-white shadow"
                         style="width:88px;height:88px;object-fit:cover;">
                    <div class="pb-2">
                        <h3 class="mb-2 text-white">{{ $this->vendor->store_name }}</h3>
                        <div class="d-flex flex-wrap gap-12">
                            @if($this->vendor->city || $this->vendor->state)
                                <span class="badge bg-white text-dark fw-medium">
                                    <i class="ph ph-map-pin-line me-6"></i>
                                    {{ trim(($this->vendor->city ? $this->vendor->city : '') . ( $this->vendor->state ? ', '.$this->vendor->state : '')) }}
                                </span>
                            @endif
                            @if($this->vendor->website)
                                <a href="{{ $this->vendor->website }}" target="_blank" class="badge bg-white text-dark text-decoration-none">
                                    <i class="ph ph-globe me-6"></i> Website
                                </a>
                            @endif
                            @if($this->vendor->whatsapp)
                                <a href="https://wa.me/{{ preg_replace('/\D/','',$this->vendor->whatsapp) }}" target="_blank" class="badge bg-white text-dark text-decoration-none">
                                    <i class="ph ph-whatsapp-logo me-6"></i> WhatsApp
                                </a>
                            @endif
                            @if($this->vendor->phone)
                                <a href="tel:{{ $this->vendor->phone }}" class="badge bg-white text-dark text-decoration-none">
                                    <i class="ph ph-phone me-6"></i> {{ $this->vendor->phone }}
                                </a>
                            @endif
                            @if($this->vendor->email)
                                <a href="mailto:{{ $this->vendor->email }}" class="badge bg-white text-dark text-decoration-none">
                                    <i class="ph ph-at me-6"></i> Email
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if($this->vendor->description)
                    <div class="bg-white border rounded-12 shadow-sm p-16 mt-12">
                        <p class="mb-0 text-gray-800" style="max-width: 80ch;">{{ $this->vendor->description }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ===== Products Grid (no filters) ===== --}}
    <section class="py-24">
        <div class="container container-lg">
            <div class="d-flex align-items-center justify-content-between mb-20">
                <h5 class="mb-0">Products </h5>
                <div class="text-muted small">Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }}</div>
            </div>

            <div class="list-grid-wrapper">
                @forelse ($products as $product)
                    @php
                        $img = (is_array($product->images) && count($product->images))
                            ? Storage::url($product->images[0])
                            : asset('assets/images/thumbs/product-placeholder.png');

                        $basePrice = (float) ($product->p_eff_price ?? $product->price);
                        $dealPrice = (!is_null($product->buy_now_price) && $product->buy_now_price > 0 && $product->buy_now_price < $product->price)
                            ? (float) $product->buy_now_price
                            : null;

                        $isLimited = (bool) $product->is_reserved;
                        $hasVariants = ($product->variants_count ?? 0) > 0;
                        $vMin = $hasVariants ? (float) $product->v_min_price : null;
                        $vMax = $hasVariants ? (float) $product->v_max_price : null;
                    @endphp

                    <div class="product-card h-100 p-16 border border-gray-100 hover-border-main-600 rounded-16 position-relative transition-2"
                         wire:key="vp-{{ $product->id }}">
                        <a href="{{ route('store.product', $product->slug) }}"
                           class="product-card__thumb flex-center rounded-8 bg-gray-50 position-relative">
                            <img src="{{ $img }}" alt="{{ $product->name }}" width="100" height="130"
                                 class="object-fit-cover rounded-8" />
                            @if ($isLimited)
                                <span class="product-card__badge bg-primary-600 px-8 py-4 text-sm text-white position-absolute inset-inline-start-0 inset-block-start-0">
                                    Limited
                                </span>
                            @endif
                        </a>

                        <div class="product-card__content mt-16 w-100">
                            <h6 class="title text-lg fw-semibold mt-12 mb-8">
                                <a href="{{ route('store.product', $product->slug) }}" class="link text-line-2" tabindex="0">
                                    {{ $product->name }}
                                </a>
                            </h6>

                            @php
                                $avg = $product->reviews_avg_rating ? round($product->reviews_avg_rating, 1) : null;
                                $count = (int) ($product->reviews_count ?? 0);
                            @endphp
                            <div class="flex-between mb-12 mt-16 gap-6">
                                <div class="flex-align gap-6">
                                    <span class="text-xs fw-medium text-gray-500">
                                        {{ $avg !== null ? number_format($avg, 1) : 'New' }}
                                    </span>
                                    <span class="text-xs fw-medium text-warning-600 d-flex">
                                        <i class="ph-fill ph-star"></i>
                                    </span>
                                    <span class="text-xs fw-medium text-gray-500">
                                        ({{ number_format($count) }})
                                    </span>
                                </div>
                                <span class="text-xs fw-medium text-gray-600 text-truncate" title="{{ $this->vendor->store_name }}">
                                    {{ $this->vendor->store_name }}
                                </span>
                            </div>

                            {{-- Price row (same logic as shop) --}}
                            @if ($isLimited)
                                <div class="product-card__price my-20">
                                    <span class="text-heading text-md fw-semibold">Price: Highest bidder</span>
                                </div>
                            @else
                                <div class="product-card__price my-20">
                                    @if ($hasVariants)
                                        @if ($vMin !== null && $vMax !== null)
                                            @if (abs($vMax - $vMin) < 0.01)
                                                <span class="text-heading text-md fw-semibold">
                                                    ${{ number_format($vMin, 2) }} <span class="text-gray-500 fw-normal">/Qty</span>
                                                </span>
                                            @else
                                                <span class="text-heading text-md fw-semibold">
                                                    ${{ number_format($vMin, 2) }} – ${{ number_format($vMax, 2) }}
                                                    <span class="text-gray-500 fw-normal">/Qty</span>
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-heading text-md fw-semibold">See options</span>
                                        @endif
                                    @else
                                        @if ($dealPrice)
                                            <span class="text-gray-400 text-md fw-semibold text-decoration-line-through">
                                                ${{ number_format($basePrice, 2) }}
                                            </span>
                                            <span class="text-heading text-md fw-semibold">
                                                ${{ number_format($dealPrice, 2) }} <span class="text-gray-500 fw-normal">/Qty</span>
                                            </span>
                                        @else
                                            <span class="text-heading text-md fw-semibold">
                                                ${{ number_format($basePrice, 2) }} <span class="text-gray-500 fw-normal">/Qty</span>
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            @endif

                            {{-- CTA --}}
                            @if ($isLimited)
                                @php
                                    $auction = $product->auctions()
                                        ->select('id','starts_at','ends_at','status')
                                        ->whereIn('status', ['live','scheduled'])
                                        ->orderByRaw("FIELD(status,'live','scheduled')")
                                        ->orderBy('starts_at')
                                        ->first();
                                @endphp
                                @if($auction)
                                    <a href="{{ route('store.auctions.show', ['auction' => $auction->id]) }}"
                                       class="product-card__cart btn bg-gray-50 text-heading hover-bg-main-600 hover-text-white py-11 px-24 rounded-8 flex-center gap-8 fw-medium"
                                       tabindex="0">
                                        <span>Bid for Item <i class="fa-solid fa-money-check-dollar"></i></span>
                                    </a>
                                @else
                                    <button class="product-card__cart btn bg-gray-700  py-11 px-24 rounded-8 flex-center fw-medium" disabled>
                                        <span>Auction Coming Soon</span>
                                    </button>
                                @endif
                            @else
                                @if (($product->variants_count ?? 0) > 0)
                                    <a wire:click.prevent="openVariantPicker({{ $product->id }}, '{{ addslashes($product->name) }}')" href="#"
                                       class="product-card__cart btn bg-gray-50 text-heading hover-bg-main-600 hover-text-white py-11 px-24 rounded-8 flex-center gap-8 fw-medium"
                                       tabindex="0">
                                        <span>Choose Options <i class="ph ph-squares-four"></i></span>
                                    </a>
                                @else
                                    <a wire:click.prevent="addToCart({{ $product->id }})" href="#"
                                       class="product-card__cart btn bg-gray-50 text-heading hover-bg-main-600 hover-text-white py-11 px-24 rounded-8 flex-center gap-8 fw-medium"
                                       tabindex="0">
                                        <span wire:loading.remove wire:target="addToCart({{ $product->id }})">
                                            Add To Cart <i class="ph ph-shopping-cart"></i>
                                        </span>
                                        <span wire:loading wire:target="addToCart({{ $product->id }})">
                                            <i class="fas fa-spinner fa-spin me-1"></i> Adding…
                                        </span>
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="alert alert-warning">No items available in this shop yet.</div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </section>

    {{-- ===== Variant Picker Small Modal ===== --}}
    @if ($showVariantModal)
        <div class="position-fixed top-0 start-0 w-100 h-100" style="background: rgba(0,0,0,0.45); z-index: 1050;"
             wire:click.self="closeVariantPicker" wire:keydown.escape="closeVariantPicker">
            <div class="bg-white rounded-12 shadow-lg p-20" style="max-width: 520px; width: 92%; margin: 10vh auto;">
                <div class="d-flex justify-content-between align-items-center mb-16">
                    <h6 class="mb-0">{{ $variantProductName ?? 'Choose Options' }}</h6>
                    <button type="button"
                            class="w-32 h-32 flex-center border border-gray-100 rounded-circle hover-bg-main-600 hover-text-white"
                            wire:click="closeVariantPicker">
                        <i class="ph ph-x"></i>
                    </button>
                </div>

                @if (count($variantOptions))
                    <div class="mb-16">
                        @foreach ($variantOptions as $opt)
                            <label class="d-flex align-items-center justify-content-between border border-gray-100 rounded-8 px-12 py-10 mb-10 cursor-pointer">
                                <div class="d-flex align-items-center gap-10">
                                    <input type="radio" class="form-check-input" wire:model.live="selectedVariantId" value="{{ $opt['id'] }}">
                                    <span class="text-sm text-heading">{{ $opt['label'] }}</span>
                                </div>
                                <span class="text-sm fw-semibold">${{ number_format($opt['price'], 2) }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning mb-16">No variant options available.</div>
                @endif

                <div class="d-flex gap-8 justify-content-end">
                    <button type="button" class="btn btn-secondary hover-bg-gray-500 rounded-8" wire:click="closeVariantPicker">Cancel</button>
                    <button type="button" class="btn bg-main-600 hover-bg-main-700 text-white rounded-8"
                            wire:click="confirmVariantAddToCart" wire:loading.attr="disabled" @disabled(!$selectedVariantId)>
                        <span wire:loading.remove wire:target="confirmVariantAddToCart">Add To Cart</span>
                        <span wire:loading wire:target="confirmVariantAddToCart">
                            <i class="fas fa-spinner fa-spin me-1"></i> Adding…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
