<div class="container py-4">
    {{-- Banner --}}
    <div class="mb-3">
        @if($profile->banner_url)
            <img src="{{ $profile->banner_url }}" alt="{{ $profile->store_name }}" class="img-fluid rounded shadow-sm"
                style="max-height:260px;width:100%;object-fit:cover;">
        @else
            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="height:220px;">
                <span class="text-muted"><i class="fa-regular fa-image me-2"></i> No banner</span>
            </div>
        @endif
    </div>

    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <div class="border rounded-circle d-flex align-items-center justify-content-center bg-white shadow-sm"
            style="width:84px;height:84px;">
            @if($profile->logo_url)
                <img src="{{ $profile->logo_url }}" class="rounded-circle" alt="logo"
                    style="width:80px;height:80px;object-fit:cover;">
            @else
                <i class="fa-regular fa-image text-muted"></i>
            @endif
        </div>
        <div class="flex-grow-1">
            <h3 class="mb-1">{{ $profile->store_name }}</h3>
            <div class="text-muted small">
                @if($profile->city || $profile->country)
                    <i class="fa-solid fa-location-dot me-1"></i>
                    {{ trim(($profile->city ?? '') . ', ' . ($profile->country ?? ''), ' ,') }}
                @endif
                @if($profile->phone)
                    <span class="ms-3"><i class="fa-solid fa-phone me-1"></i> {{ $profile->phone }}</span>
                @endif
                @if($profile->email)
                    <span class="ms-3"><i class="fa-regular fa-envelope me-1"></i> {{ $profile->email }}</span>
                @endif
                @if($profile->whatsapp)
                    <span class="ms-3"><i class="fa-brands fa-whatsapp me-1"></i> {{ $profile->whatsapp }}</span>
                @endif
            </div>
            <div class="mt-2">
                @if($profile->website)
                    <a href="{{ $profile->website }}" target="_blank" class="me-2"><i class="fa-solid fa-globe me-1"></i>
                        Website</a>
                @endif
                @php $s = $profile->socials ?? []; @endphp
                @if(($s['facebook'] ?? null)) <a href="{{ $s['facebook'] }}" target="_blank" class="me-2"><i
                class="fa-brands fa-facebook"></i></a> @endif
                @if(($s['instagram'] ?? null)) <a href="{{ $s['instagram'] }}" target="_blank" class="me-2"><i
                class="fa-brands fa-instagram"></i></a> @endif
                @if(($s['twitter'] ?? null)) <a href="{{ $s['twitter'] }}" target="_blank" class="me-2"><i
                class="fa-brands fa-x-twitter"></i></a> @endif
                @if(($s['tiktok'] ?? null)) <a href="{{ $s['tiktok'] }}" target="_blank" class="me-2"><i
                class="fa-brands fa-tiktok"></i></a> @endif
                @if(($s['youtube'] ?? null)) <a href="{{ $s['youtube'] }}" target="_blank" class="me-2"><i
                class="fa-brands fa-youtube"></i></a> @endif
            </div>
        </div>
    </div>

    {{-- Description --}}
    @if($profile->description)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                {!! nl2br(e($profile->description)) !!}
            </div>
        </div>
    @endif

    {{-- Map --}}
    @if($profile->lat && $profile->lng)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">Location</div>
            <div class="card-body">
                <img class="w-100 rounded border" style="max-height:260px;object-fit:cover" alt="map"
                    src="https://maps.googleapis.com/maps/api/staticmap?center={{ $profile->lat }},{{ $profile->lng }}&zoom=14&size=640x320&markers=color:red%7C{{ $profile->lat }},{{ $profile->lng }}&key={{ config('services.google.maps_key') }}">
                @if($profile->address_line)
                    <div class="small text-muted mt-2">
                        <i class="fa-solid fa-location-dot me-1"></i> {{ $profile->address_line }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Products --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Products</h5>
    </div>

    <div class="row g-3">
        @forelse($products as $product)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    @php
                        $thumb = is_array($product->images) && count($product->images) ? asset('storage/' . $product->images[0]) : null;
                      @endphp
                    <div class="ratio ratio-4x3 bg-light">
                        @if($thumb)
                            <img src="{{ $thumb }}" class="card-img-top" style="object-fit:cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center text-muted">
                                <i class="fa-regular fa-image me-2"></i> No image
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="small text-muted mb-1">{{ $product->category->name ?? 'Uncategorized' }}</div>
                        <div class="fw-semibold">{{ $product->name }}</div>
                        <div class="mt-1">₦{{ number_format((float) $product->price, 2) }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center text-muted py-5">No products yet.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $products->links() }}
    </div>
</div>