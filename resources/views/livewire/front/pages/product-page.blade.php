{{-- resources/views/livewire/front/pages/product-page.blade.php --}}
@php
    use Illuminate\Support\Facades\Storage;
    $mainImg = Storage::url($images[$imageIndex] ?? 'images/placeholder.png');
@endphp

<div>
    <section class="py-80">
        <div class="container container-lg">
            <div class="row g-4">

                {{-- Left: Gallery (shorter hero, still responsive) --}}
                <div class="col-lg-6">
                    <div class="border border-gray-100 rounded-16 p-16 bg-white">
                        <div class="ratio ratio-4x3 rounded-12 overflow-hidden bg-gray-50" style="max-height:400px;">
                            <img src="{{ $mainImg }}" alt="{{ $product->name }}" class="w-100 h-100 object-fit-cover">
                        </div>

                        @if(count($images) > 1)
                            <div class="d-flex gap-10 mt-12 flex-wrap">
                                @foreach($images as $i => $img)
                                    @php $src = Storage::url($img); @endphp
                                    <button type="button"
                                        class="border rounded-8 overflow-hidden p-0 @if($imageIndex === $i) border-main-600 @else border-gray-100 @endif"
                                        style="width:70px;height:70px" wire:click="setImage({{ $i }})">
                                        <img src="{{ $src }}" alt="thumb" class="w-100 h-100 object-fit-cover">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right: Info --}}
                <div class="col-lg-6">
                    <div class="border border-gray-100 rounded-16 p-24 bg-white">
                        {{-- Title --}}
                        <h2 class="mb-6">{{ $product->name }}</h2>

                        {{-- Vendor + Rating (same row; logo + store link) --}}
                        <div class="d-flex justify-content-between align-items-center mb-16">
                            <div class="d-flex align-items-center gap-8">
                                <span class="text-xs fw-medium text-gray-500">
                                    {{ $ratingAvg !== null ? number_format($ratingAvg, 1) : 'New' }}
                                </span>
                                <i class="ph-fill ph-star text-warning-600"></i>
                                <span class="text-xs fw-medium text-gray-500">({{ number_format($ratingCount) }})</span>
                            </div>

                            @if($storeName)
                                <a href="{{ url('/store/' . $storeSlug) }}"
                                    class="d-inline-flex align-items-center gap-8 text-decoration-none">
                                    <span class="rounded-circle overflow-hidden border border-gray-100"
                                        style="width:28px;height:28px;display:inline-flex;">
                                        <img src="{{ $storeLogo ?: asset('assets/images/store-default.png') }}"
                                            alt="Store Logo" class="w-100 h-100 object-fit-cover">
                                    </span>
                                    <span class="badge bg-main-50 text-gray-800 fw-medium px-10 py-6 rounded-8">
                                        {{ $storeName }}
                                    </span>
                                </a>
                            @endif
                        </div>

                        {{-- Price --}}
                        <div class="product-card__price my-20">
                            @if($hasVariants)
                                @if($selectedVariantId)
                                    <span class="text-heading text-xl fw-semibold">₦{{ number_format($price, 2) }}</span>
                                    <span class="text-gray-500 fw-normal">/Qty</span>
                                @else
                                    @if($priceMin !== null && $priceMax !== null)
                                        @if(abs($priceMax - $priceMin) < 0.01)
                                            <span class="text-heading text-xl fw-semibold">₦{{ number_format($priceMin, 2) }}</span>
                                        @else
                                            <span class="text-heading text-xl fw-semibold">
                                                ₦{{ number_format($priceMin, 2) }} – ₦{{ number_format($priceMax, 2) }}
                                            </span>
                                        @endif
                                        <span class="text-gray-500 fw-normal">/Qty</span>
                                    @else
                                        <span class="text-heading text-xl fw-semibold">See options</span>
                                    @endif
                                @endif
                            @else
                                <span class="text-heading text-xl fw-semibold">₦{{ number_format($price, 2) }}</span>
                                <span class="text-gray-500 fw-normal">/Qty</span>
                            @endif
                        </div>

                        {{-- Variant selectors --}}
                        @if($hasVariants)
                            <div class="mb-20">
                                @foreach($attributeGroups as $attrId => $group)
                                    <div class="mb-12">
                                        <div class="text-sm text-gray-700 mb-6">{{ $group['name'] }}</div>
                                        <div class="d-flex flex-wrap gap-8">
                                            @foreach($group['values'] as $opt)
                                                @php $active = isset($selected[$attrId]) && (int) $selected[$attrId] === (int) $opt['id']; @endphp
                                                <button type="button"
                                                    class="btn btn-sm rounded-8 px-12 py-8 {{ $active ? 'bg-main-600 text-white' : 'bg-gray-50 text-heading border border-gray-200 hover-bg-main-600 hover-text-white' }}"
                                                    wire:click="selectOption({{ (int) $attrId }}, {{ (int) $opt['id'] }})">
                                                    {{ $opt['label'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Stock status --}}
                            <div class="mb-12">
                                @if($selectedVariantId !== null)
                                    @if(($stock ?? 0) > 0)
                                        <span class="badge bg-success">In stock: {{ $stock }}</span>
                                    @else
                                        <span class="badge bg-danger">Out of stock</span>
                                    @endif
                                @else
                                    <span class="text-muted small">Select options to see availability</span>
                                @endif
                            </div>
                        @endif

                        {{-- Quantity + Add to Cart --}}
                        <div class="d-flex align-items-center gap-12">
                            <div class="d-inline-flex align-items-center border border-gray-200 rounded-8">
                                <button type="button" class="btn px-12 py-8" wire:click="decreaseQty">&minus;</button>
                                <span class="px-12 fw-semibold">{{ $quantity }}</span>
                                <button type="button" class="btn px-12 py-8" wire:click="increaseQty">&#43;</button>
                            </div>

                            <button type="button"
                                class="btn bg-main-600 hover-bg-main-700 text-white rounded-8 py-12 px-20"
                                wire:click="addToCart" @if($hasVariants && $selectedVariantId === null) disabled @endif
                                @if($hasVariants && $selectedVariantId !== null && ($stock ?? 0) <= 0) disabled @endif>
                                <span wire:loading.remove wire:target="addToCart">
                                    <i class="ph ph-shopping-cart me-1"></i> Add to Cart
                                </span>
                                <span wire:loading wire:target="addToCart">
                                    <i class="fas fa-spinner fa-spin me-1"></i> Adding…
                                </span>
                            </button>
                        </div>

                        {{-- Alerts --}}
                        <div class="mt-12">
                            @if(session()->has('success'))
                                <div class="alert alert-success py-8 px-12 mb-0">{{ session('success') }}</div>
                            @endif
                            @if(session()->has('error'))
                                <div class="alert alert-danger py-8 px-12 mb-0">{{ session('error') }}</div>
                            @endif
                        </div>

                        {{-- Description --}}
                        <div class="mt-20">
                            <h6 class="mb-8">Description</h6>
                            <div class="text-gray-700">
                                {!! nl2br(e($product->description)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- =================== Related Products =================== --}}
            @if($relatedProducts && $relatedProducts->count())
                <div class="mt-48">
                    <div class="d-flex justify-content-between align-items-center mb-20">
                        <h5 class="mb-0">Related Products</h5>
                        <a href="{{ url('/shop?category=' . $product->category_id) }}"
                            class="text-decoration-none text-gray-700">
                            View more →
                        </a>
                    </div>

                    <div class="list-grid-wrapper d-grid"
                        style="grid-template-columns: repeat(auto-fill,minmax(220px,1fr)); gap:16px;">
                        @foreach($relatedProducts as $rp)
                            @php
                                $img = (is_array($rp->images) && count($rp->images))
                                    ? Storage::url($rp->images[0])
                                    : asset('assets/images/thumbs/product-placeholder.png');

                                $hasVariants = ($rp->variants_count ?? 0) > 0;
                                if ($hasVariants) {
                                    $min = $rp->variants->min(fn($v) => $v->price ?? $rp->price);
                                    $max = $rp->variants->max(fn($v) => $v->price ?? $rp->price);
                                } else {
                                    $base = (float) $rp->price;
                                    $deal = ($rp->buy_now_price && $rp->buy_now_price > 0 && $rp->buy_now_price < $rp->price)
                                        ? (float) $rp->buy_now_price
                                        : null;
                                }

                                $rAvg = $rp->reviews_avg_rating ? round($rp->reviews_avg_rating, 1) : null;
                                $rCnt = (int) ($rp->reviews_count ?? 0);
                            @endphp

                            <div
                                class="product-card h-100 p-16 border border-gray-100 hover-border-main-600 rounded-16 position-relative transition-2">
                                <a href="{{ route('store.product', $rp->slug) }}"
                                    class="product-card__thumb flex-center rounded-8 bg-gray-50 position-relative">
                                    <img src="{{ $img }}" alt="{{ $rp->name }}" width="100" height="130"
                                        class="object-fit-cover rounded-8" />
                                </a>

                                <div class="product-card__content mt-16 w-100">
                                    <h6 class="title text-lg fw-semibold mt-12 mb-8">
                                        <a href="{{ route('store.product', $rp->slug) }}"
                                            class="link text-line-2">{{ $rp->name }}</a>
                                    </h6>

                                    {{-- rating row --}}
                                    <div class="flex-between mb-12 mt-16 gap-6">
                                        <div class="flex-align gap-6">
                                            <span class="text-xs fw-medium text-gray-500">
                                                {{ $rAvg !== null ? number_format($rAvg, 1) : 'New' }}
                                            </span>
                                            <span class="text-xs fw-medium text-warning-600 d-flex"><i
                                                    class="ph-fill ph-star"></i></span>
                                            <span class="text-xs fw-medium text-gray-500">({{ number_format($rCnt) }})</span>
                                        </div>
                                    </div>

                                    {{-- price --}}
                                    <div class="product-card__price my-20">
                                        @if($hasVariants)
                                            @if($min == $max)
                                                <span class="text-heading text-md fw-semibold">₦{{ number_format((float) $min, 2) }}
                                                    <span class="text-gray-500 fw-normal">/Qty</span></span>
                                            @else
                                                <span class="text-heading text-md fw-semibold">₦{{ number_format((float) $min, 2) }} –
                                                    ₦{{ number_format((float) $max, 2) }} <span
                                                        class="text-gray-500 fw-normal">/Qty</span></span>
                                            @endif
                                        @else
                                            @if(!empty($deal))
                                                <span
                                                    class="text-gray-400 text-md fw-semibold text-decoration-line-through">₦{{ number_format($base, 2) }}</span>
                                                <span class="text-heading text-md fw-semibold">₦{{ number_format($deal, 2) }} <span
                                                        class="text-gray-500 fw-normal">/Qty</span></span>
                                            @else
                                                <span class="text-heading text-md fw-semibold">₦{{ number_format($base, 2) }} <span
                                                        class="text-gray-500 fw-normal">/Qty</span></span>
                                            @endif
                                        @endif
                                    </div>

                                    <a href="{{ route('store.product', $rp->slug) }}"
                                        class="product-card__cart btn bg-gray-50 text-heading hover-bg-main-600 hover-text-white py-11 px-24 rounded-8 d-flex justify-content-center gap-8 fw-medium">
                                        <span>View Details <i class="ph ph-arrow-right"></i></span>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            {{-- =================== /Related Products =================== --}}
        </div>
    </section>
</div>