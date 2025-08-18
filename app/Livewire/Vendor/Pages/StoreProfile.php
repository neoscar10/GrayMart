<?php

namespace App\Livewire\Vendor\Pages;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\VendorProfile;

class StoreProfile extends Component
{
    use WithFileUploads;

    public ?VendorProfile $profile = null;

    // Form fields
    public ?string $store_name = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $whatsapp = null;
    public ?string $website = null;
    public array $socials = [
        'facebook'  => null,
        'instagram' => null,
        'twitter'   => null,
        'tiktok'    => null,
        'youtube'   => null,
    ];
    public ?string $description = null;

    public ?string $address_line = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $country = null;
    public ?string $postal_code = null;
    public ?string $place_id = null;
    public ?float  $lat = null;
    public ?float  $lng = null;

    public $logo;   // UploadedFile|null
    public $banner; // UploadedFile|null

    public function mount(): void
    {
        $this->profile = auth()->user()->vendorProfile;

        if (!$this->profile) {
            $this->profile = VendorProfile::create([
                'user_id'    => auth()->id(),
                'store_name' => auth()->user()->name . "'s Store",
                'slug'       => VendorProfile::uniqueSlug(auth()->user()->name.' store '.auth()->id()),
                'email'      => auth()->user()->email,
            ]);
        }

        // hydrate form
        $this->store_name   = $this->profile->store_name;
        $this->email        = $this->profile->email;
        $this->phone        = $this->profile->phone;
        $this->whatsapp     = $this->profile->whatsapp;
        $this->website      = $this->profile->website;
        $this->socials      = array_merge($this->socials, $this->profile->socials ?? []);
        $this->description  = $this->profile->description;

        $this->address_line = $this->profile->address_line;
        $this->city         = $this->profile->city;
        $this->state        = $this->profile->state;
        $this->country      = $this->profile->country;
        $this->postal_code  = $this->profile->postal_code;
        $this->place_id     = $this->profile->place_id;
        $this->lat          = $this->profile->lat;
        $this->lng          = $this->profile->lng;
    }

    protected function rules(): array
    {
        return [
            'store_name'  => ['required','string','max:120', Rule::unique('vendor_profiles','store_name')->ignore($this->profile->id)],
            'email'       => ['nullable','email','max:120'],
            'phone'       => ['nullable','string','max:50'],
            'whatsapp'    => ['nullable','string','max:50'],
            'website'     => ['nullable','url','max:255'],
            'socials.facebook'  => ['nullable','url','max:255'],
            'socials.instagram' => ['nullable','url','max:255'],
            'socials.twitter'   => ['nullable','url','max:255'],
            'socials.tiktok'    => ['nullable','url','max:255'],
            'socials.youtube'   => ['nullable','url','max:255'],
            'description' => ['nullable','string','max:5000'],

            'address_line'=> ['nullable','string','max:255'],
            'city'        => ['nullable','string','max:120'],
            'state'       => ['nullable','string','max:120'],
            'country'     => ['nullable','string','max:120'],
            'postal_code' => ['nullable','string','max:60'],
            'place_id'    => ['nullable','string','max:255'],
            'lat'         => ['nullable','numeric','between:-90,90'],
            'lng'         => ['nullable','numeric','between:-180,180'],

            'logo'   => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'banner' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
        ];
    }

    public function updated($field)
    {
        $this->validateOnly($field, $this->rules());
    }

    public function removeLogo(): void
    {
        $this->authorize('update', $this->profile);
        if ($this->profile->logo_path) {
            Storage::disk('public')->delete($this->profile->logo_path);
            $this->profile->update(['logo_path' => null]);
        }
        $this->dispatch('toast', ['type'=>'success','message'=>'Logo removed']);
    }

    public function removeBanner(): void
    {
        $this->authorize('update', $this->profile);
        if ($this->profile->banner_path) {
            Storage::disk('public')->delete($this->profile->banner_path);
            $this->profile->update(['banner_path' => null]);
        }
        $this->dispatch('toast', ['type'=>'success','message'=>'Banner removed']);
    }

    public function save(): void
    {
        $this->authorize('update', $this->profile);
        $data = $this->validate($this->rules());

        // uploads
        $paths = [];
        if ($this->logo) {
            $paths['logo_path'] = $this->logo->store('vendor/'.auth()->id().'/logo', 'public');
        }
        if ($this->banner) {
            $paths['banner_path'] = $this->banner->store('vendor/'.auth()->id().'/banner', 'public');
        }

        $this->profile->update([
            'store_name'   => $data['store_name'],
            'slug'         => VendorProfile::uniqueSlug($data['store_name'], $this->profile->id),
            'email'        => $data['email'] ?? null,
            'phone'        => $data['phone'] ?? null,
            'whatsapp'     => $data['whatsapp'] ?? null,
            'website'      => $data['website'] ?? null,
            'socials'      => $data['socials'] ?? null,
            'description'  => $data['description'] ?? null,

            'address_line' => $data['address_line'] ?? null,
            'city'         => $data['city'] ?? null,
            'state'        => $data['state'] ?? null,
            'country'      => $data['country'] ?? null,
            'postal_code'  => $data['postal_code'] ?? null,
            'place_id'     => $data['place_id'] ?? null,
            'lat'          => $data['lat'] ?? null,
            'lng'          => $data['lng'] ?? null,

            ...$paths,
        ]);

        $this->dispatch('toast', ['type'=>'success','message'=>'Store profile updated successfully.']);
    }

    public function render()
    {
        return view('livewire.vendor.pages.store-profile')
            ->layout('components.layouts.vendor');
    }
}
