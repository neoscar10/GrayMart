<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Store Profile</h4>
        <a href="{{ route('store.show', $this->profile->slug) }}" target="_blank"
            class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Public Store
        </a>
    </div>

    <form wire:submit.prevent="save" class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                {{-- Basics --}}
                <div class="col-md-6">
                    <label class="form-label">Store Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" wire:model.live="store_name">
                    @error('store_name') <small class="text-danger">{{ $message }}</small> @enderror
                    <small class="text-muted d-block">URL: {{ url('/store/' . $this->profile->slug) }}</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" wire:model.live="email">
                    @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" wire:model.live="phone">
                    @error('phone') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" class="form-control" wire:model.live="whatsapp" placeholder="+234...">
                    @error('whatsapp') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Website</label>
                    <input type="url" class="form-control" wire:model.live="website" placeholder="https://">
                    @error('website') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                {{-- Socials --}}
                <div class="col-md-6">
                    <label class="form-label">Facebook</label>
                    <input type="url" class="form-control" wire:model.live="socials.facebook"
                        placeholder="https://facebook.com/...">
                    @error('socials.facebook') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Instagram</label>
                    <input type="url" class="form-control" wire:model.live="socials.instagram"
                        placeholder="https://instagram.com/...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Twitter/X</label>
                    <input type="url" class="form-control" wire:model.live="socials.twitter"
                        placeholder="https://x.com/...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">TikTok</label>
                    <input type="url" class="form-control" wire:model.live="socials.tiktok"
                        placeholder="https://tiktok.com/@...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">YouTube</label>
                    <input type="url" class="form-control" wire:model.live="socials.youtube"
                        placeholder="https://youtube.com/...">
                </div>

                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" rows="4" wire:model.live="description"
                        placeholder="Tell customers about your store..."></textarea>
                    @error('description') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                {{-- Images --}}
                <div class="col-md-6">
                    <label class="form-label">Logo (JPG/PNG/WebP, max 2MB)</label>
                    <div class="d-flex align-items-center gap-3">
                        <div class="border rounded p-2 bg-light"
                            style="width:96px;height:96px;display:flex;align-items:center;justify-content:center;">
                            @if($logo)
                                <img src="{{ $logo->temporaryUrl() }}" alt="logo" style="max-height:90px;max-width:90px;">
                            @elseif($this->profile->logo_url)
                                <img src="{{ $this->profile->logo_url }}" alt="logo"
                                    style="max-height:90px;max-width:90px;">
                            @else
                                <i class="fa-regular fa-image text-muted"></i>
                            @endif
                        </div>
                        <div>
                            <input type="file" class="form-control" wire:model="logo" accept="image/*">
                            @error('logo') <small class="text-danger">{{ $message }}</small> @enderror
                            @if($this->profile->logo_url)
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" wire:click="removeLogo">
                                    <i class="fa-solid fa-trash-can me-1"></i> Remove
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Banner (JPG/PNG/WebP, max 4MB)</label>
                    <div class="border rounded p-2 bg-light" style="height:120px;">
                        @if($banner)
                            <img src="{{ $banner->temporaryUrl() }}" alt="banner" style="height:100%;width:auto;">
                        @elseif($this->profile->banner_url)
                            <img src="{{ $this->profile->banner_url }}" alt="banner" style="height:100%;width:auto;">
                        @else
                            <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                                <i class="fa-regular fa-image me-2"></i> No banner uploaded
                            </div>
                        @endif
                    </div>
                    <input type="file" class="form-control mt-2" wire:model="banner" accept="image/*">
                    @error('banner') <small class="text-danger">{{ $message }}</small> @enderror
                    @if($this->profile->banner_url)
                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" wire:click="removeBanner">
                            <i class="fa-solid fa-trash-can me-1"></i> Remove
                        </button>
                    @endif
                </div>

                {{-- Location --}}
                <div class="col-md-6">
                    <label class="form-label">Search Address</label>
                    <input type="text" id="autocomplete" class="form-control" placeholder="Type to search..."
                        autocomplete="off">
                    <small class="text-muted">Use Google Places to autofill address & coordinates.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Address Line</label>
                    <input type="text" class="form-control" wire:model.live="address_line">
                </div>

                <div class="col-md-3">
                    <label class="form-label">City</label>
                    <input type="text" class="form-control" wire:model.live="city">
                </div>
                <div class="col-md-3">
                    <label class="form-label">State</label>
                    <input type="text" class="form-control" wire:model.live="state">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Country</label>
                    <input type="text" class="form-control" wire:model.live="country">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Postal Code</label>
                    <input type="text" class="form-control" wire:model.live="postal_code">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Latitude</label>
                    <input type="text" class="form-control" wire:model.live="lat" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Longitude</label>
                    <input type="text" class="form-control" wire:model.live="lng" readonly>
                </div>

                {{-- Static map preview --}}
                <div class="col-md-6">
                    @if($lat && $lng)
                        <label class="form-label">Location Preview</label>
                        <img class="w-100 rounded border shadow-sm" style="max-height:220px;object-fit:cover"
                            src="https://maps.googleapis.com/maps/api/staticmap?center={{ $lat }},{{ $lng }}&zoom=14&size=640x320&markers=color:red%7C{{ $lat }},{{ $lng }}&key={{ config('services.google.maps_key') }}"
                            alt="map preview">
                    @endif
                </div>

            </div>
        </div>

        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('store.show', $this->profile->slug) }}" target="_blank" class="btn btn-outline-secondary">
                Preview Public
            </a>
            <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                </span>
                <span wire:loading wire:target="save">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...
                </span>
            </button>

        </div>
    </form>
</div>

@push('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_key') }}&libraries=places"
        async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('autocomplete');
            if (!input) return;

            const init = () => {
                const ac = new google.maps.places.Autocomplete(input, { types: ['geocode'] });
                ac.addListener('place_changed', () => {
                    const place = ac.getPlace();
                    if (!place || !place.address_components) return;

                    const get = (type) => (place.address_components.find(c => c.types.includes(type)) || {}).long_name || '';
                    const payload = {
                        place_id: place.place_id || null,
                        address_line: (place.formatted_address || '').substring(0, 255),
                        city: get('locality') || get('sublocality') || '',
                        state: get('administrative_area_level_1') || '',
                        country: get('country') || '',
                        postal_code: get('postal_code') || '',
                        lat: place.geometry && place.geometry.location ? place.geometry.location.lat() : null,
                        lng: place.geometry && place.geometry.location ? place.geometry.location.lng() : null,
                    };
                    for (const [k, v] of Object.entries(payload)) @this.set(k, v, true);
                });
            };

            const wait = setInterval(() => {
                if (window.google && google.maps && google.maps.places) {
                    clearInterval(wait); init();
                }
            }, 200);
        });
    </script>
@endpush