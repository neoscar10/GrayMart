<?php

namespace App\Livewire\Admin\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\ProductVariant;
use App\Models\VariantAttributeValue;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductManagement extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $vendorFilter = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $reservedOnly = false;

    public $selectedProductId;
    public $name;
    public $slug;
    public $description;
    public $price;
    public $category_id;
    public $video_url;
    public $is_reserved = false;
    public $is_signed = false;
    public $is_active = true;
    public $status = 'pending';
    public $currentCertificate;

    public $images = [];
    public $newImages = [];
    public $certificateFile;

    public $variants = [];
    public $attributeValues;

    public $rejectionReason = '';

    protected $queryString = [
        'search','vendorFilter','categoryFilter','statusFilter','reservedOnly'
    ];

    public function mount()
    {
        $this->attributeValues = VariantAttributeValue::with('attribute')->get();
    }

    protected function rules()
    {
        $uniqueSlug = $this->selectedProductId
            ? 'unique:products,slug,'.$this->selectedProductId
            : 'unique:products,slug';

        return [
            'name' => 'required|string|min:3',
            'slug' => 'nullable|string|'.$uniqueSlug,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'video_url' => 'nullable|url',
            'is_reserved' => 'boolean',
            'is_signed' => 'boolean',
            'is_active' => 'boolean',
            'status' => 'required|in:pending,approved,rejected',
            'rejectionReason' => 'required_if:status,rejected|string|min:5',
            'newImages.*' => 'image|max:2048',
            'certificateFile' => 'nullable|file|mimes:pdf|max:4096',
            'variants.*.sku' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'nullable|integer|min:0',
            'variants.*.value_ids' => 'array',
            'variants.*.value_ids.*' => 'exists:variant_attribute_values,id',
        ];
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingVendorFilter() { $this->resetPage(); }
    public function updatingCategoryFilter() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingReservedOnly() { $this->resetPage(); }

    private function getDescendantIds($cat)
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
            'video_url','is_reserved','is_signed','is_active','status',
            'rejectionReason','selectedProductId',
            'images','newImages','certificateFile',
            'variants',
        ]);
    }

    public function openEditModal($id)
    {
        $p = Product::with(['certificates','variants.attributeValues.attribute'])->findOrFail($id);

        $this->selectedProductId  = $p->id;
        $this->name               = $p->name;
        $this->slug               = $p->slug;
        $this->description        = $p->description;
        $this->price              = $p->price;
        $this->category_id        = $p->category_id;
        $this->video_url          = $p->video_url;
        $this->is_reserved        = $p->is_reserved;
        $this->is_signed          = $p->is_signed;
        $this->is_active          = $p->is_active;
        $this->status             = $p->status;
        $this->images             = $p->images ?? [];
        $this->newImages          = [];
        $this->certificateFile    = null;
        $this->currentCertificate = $p->certificates->last();

        $this->variants = $p->variants->map(fn($v) => [
            'id' => $v->id,
            'sku' => $v->sku,
            'price' => $v->price,
            'stock' => $v->stock,
            'value_ids' => $v->attributeValues->pluck('id')->toArray(),
            'values' => $v->attributeValues->map(fn($val) => [
                'attribute_name' => $val->attribute->name,
                'value' => $val->value,
            ]),
        ])->toArray();

        $this->dispatch('showProductModal');
    }

    public function removeExistingImage($i)
    {
        unset($this->images[$i]);
        $this->images = array_values($this->images);
    }

    public function addVariant()
    {
        $this->variants[] = [
            'id' => null,
            'sku' => '',
            'price' => null,
            'stock' => null,
            'value_ids' => [],
        ];
    }

    public function removeVariant($i)
    {
        if (!empty($this->variants[$i]['id'])) {
            ProductVariant::destroy($this->variants[$i]['id']);
        }
        array_splice($this->variants, $i, 1);
    }

    public function updateProduct()
    {
        $this->validate();

        $p = Product::findOrFail($this->selectedProductId);

        $allImages = $this->images;
        foreach ($this->newImages as $img) {
            $path = $img->store('admin_products','public');
            $allImages[] = Storage::url($path);
        }

        if ($this->is_signed && $this->certificateFile) {
            $certPath = $this->certificateFile->store('certificates','public');
            Certificate::create([
                'product_id' => $p->id,
                'file_path' => $certPath,
                'status' => 'pending',
            ]);
        }

        $p->update([
            'name' => $this->name,
            'slug' => $this->slug ?: Str::slug($this->name),
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category_id,
            'video_url' => $this->video_url,
            'is_reserved' => $this->is_reserved,
            'is_signed' => $this->is_signed,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'images' => $allImages,
            'rejection_reason' => $this->status==='rejected' ? $this->rejectionReason : null,
            'rejected_at' => $this->status==='rejected' ? now() : null,
        ]);

        foreach ($this->variants as $v) {
            if ($v['id']) {
                $variant = ProductVariant::find($v['id']);
                $variant->update([
                    'sku' => $v['sku'],
                    'price' => $v['price'],
                    'stock' => $v['stock'],
                ]);
            } else {
                $variant = ProductVariant::create([
                    'product_id'=> $p->id,
                    'sku' => $v['sku'],
                    'price' => $v['price'],
                    'stock' => $v['stock'],
                ]);
            }
            $variant->attributeValues()->sync($v['value_ids']);
        }

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

        Product::find($this->selectedProductId)
            ->update([
                'status' => 'rejected',
                'rejection_reason' => $this->rejectionReason,
                'rejected_at' => now(),
            ]);

        $this->dispatch('hideRejectModal');
        session()->flash('success','Product rejected successfully.');
        $this->resetForm();
    }

    public function render()
    {
        $query = Product::with(['vendor','category','certificates'])
            ->when($this->search, fn($q)=> $q->where('name','like',"%{$this->search}%"))
            ->when($this->vendorFilter, fn($q)=> $q->where('vendor_id',$this->vendorFilter))
            ->when($this->categoryFilter, function($q){
                if ($c = Category::find($this->categoryFilter)) {
                    $q->whereIn('category_id',$this->getDescendantIds($c));
                }
            })
            ->when($this->statusFilter, fn($q)=> $q->where('status',$this->statusFilter))
            ->when($this->reservedOnly, fn($q)=> $q->where('is_reserved',true))
            ->orderByDesc('created_at');

        $products = $query->paginate(10);
        $vendors = User::where('role','vendor')->get();
        $categories = Category::all();
        $attributeValues = $this->attributeValues;

        return view('livewire.admin.pages.product-management', compact(
            'products','vendors','categories','attributeValues'
        ))->layout('components.layouts.admin');
    }
}
