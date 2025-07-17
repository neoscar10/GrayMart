<?php

namespace App\Livewire\Admin\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductManagement extends Component
{
    use WithPagination, WithFileUploads;

    // Filters
    public $search = '';
    public $vendorFilter = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $auctionOnly = false;

    // Editing fields
    public $selectedProductId;
    public $name;
    public $slug;
    public $description;
    public $price;
    public $category_id;
    public $video_url;
    public $is_reserved = false;
    public $is_active   = true;
    public $status      = 'pending';

    // Images
    public $images = [];       // existing URLs
    public $newImages = [];    // uploads via Livewire

    // Rejection
    public $rejectionReason = '';

    protected $queryString = [
        'search','vendorFilter','categoryFilter',
        'statusFilter','auctionOnly'
    ];

    protected function rules()
    {
        $uniqueSlug = $this->selectedProductId
            ? 'unique:products,slug,'.$this->selectedProductId
            : 'unique:products,slug';

        return [
            'name'             => 'required|string|min:3',
            'slug'             => 'nullable|string|'.$uniqueSlug,
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'category_id'      => 'nullable|exists:categories,id',
            'video_url'        => 'nullable|url',
            'is_reserved'      => 'boolean',
            'is_active'        => 'boolean',
            'status'           => 'required|in:pending,approved,rejected',
            'rejectionReason'  => 'required_if:status,rejected|string|min:5',
            'newImages.*'      => 'image|max:2048',  // 2MB max each
        ];
    }

    public function updatingSearch()         { $this->resetPage(); }
    public function updatingVendorFilter()   { $this->resetPage(); }
    public function updatingCategoryFilter() { $this->resetPage(); }
    public function updatingStatusFilter()   { $this->resetPage(); }
    public function updatingAuctionOnly()    { $this->resetPage(); }

    private function getDescendantIds(Category $cat)
    {
        $ids = collect([$cat->id]);
        foreach ($cat->children as $child) {
            $ids = $ids->merge($this->getDescendantIds($child));
        }
        return $ids->all();
    }

    public function resetForm()
    {
        $this->reset([
            'name','slug','description','price','category_id',
            'video_url','is_reserved','is_active','status',
            'rejectionReason','selectedProductId',
            'images','newImages'
        ]);
    }

    public function openEditModal($id)
    {
        $p = Product::findOrFail($id);
        $this->selectedProductId = $p->id;
        $this->name        = $p->name;
        $this->slug        = $p->slug;
        $this->description = $p->description;
        $this->price       = $p->price;
        $this->category_id = $p->category_id;
        $this->video_url   = $p->video_url;
        $this->is_reserved = $p->is_reserved;
        $this->is_active   = $p->is_active;
        $this->status      = $p->status;
        $this->images      = $p->images ?? [];
        $this->newImages   = [];
        $this->dispatch('showProductModal');
    }

    public function removeExistingImage($index)
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    public function updateProduct()
    {
        $this->validate();

        $p = Product::findOrFail($this->selectedProductId);

        // Handle new uploads
        $allImages = $this->images;
        foreach ($this->newImages as $upload) {
            $path = $upload->store('admin_products','public');
            $allImages[] = Storage::url($path);
        }

        $p->update([
            'name'             => $this->name,
            'slug'             => $this->slug ?: Str::slug($this->name),
            'description'      => $this->description,
            'price'            => $this->price,
            'category_id'      => $this->category_id,
            'video_url'        => $this->video_url,
            'is_reserved'      => $this->is_reserved,
            'is_active'        => $this->is_active,
            'status'           => $this->status,
            'images'           => $allImages,
            'rejection_reason' => $this->status === 'rejected'
                                   ? $this->rejectionReason
                                   : null,
            'rejected_at'      => $this->status === 'rejected'
                                   ? now()
                                   : null,
        ]);

        $this->dispatch('hideProductModal');
        session()->flash('success','Product updated successfully.');
        $this->resetForm();
    }

    public function openRejectModal($id)
    {
        $this->reset('rejectionReason');
        $this->selectedProductId = $id;
        $this->dispatch('showRejectModal');
    }

    public function rejectProductConfirmed()
    {
        $this->status = 'rejected';
        $this->validateOnly('rejectionReason');

        $p = Product::findOrFail($this->selectedProductId);
        $p->update([
            'status'           => 'rejected',
            'rejection_reason' => $this->rejectionReason,
            'rejected_at'      => now(),
        ]);

        $this->dispatch('hideRejectModal');
        session()->flash('success','Product rejected successfully.');
        $this->resetForm();
    }

    public function render()
    {
        $query = Product::with(['vendor','category'])
            ->when($this->search,         fn($q)=> $q->where('name','like',"%{$this->search}%"))
            ->when($this->vendorFilter,   fn($q)=> $q->where('vendor_id',$this->vendorFilter))
            ->when($this->categoryFilter, function($q){
                if ($cat = Category::find($this->categoryFilter)) {
                    $ids = $this->getDescendantIds($cat);
                    $q->whereIn('category_id',$ids);
                }
            })
            ->when($this->statusFilter,   fn($q)=> $q->where('status',$this->statusFilter))
            ->when($this->auctionOnly,    fn($q)=> $q->where('is_reserved',true))
            ->orderByDesc('created_at');

        $products   = $query->paginate(10);
        $vendors    = User::where('role','vendor')->get();
        $categories = Category::all();

        return view('livewire.admin.pages.product-management', compact(
            'products','vendors','categories'
        ))->layout('components.layouts.admin');
    }
}
