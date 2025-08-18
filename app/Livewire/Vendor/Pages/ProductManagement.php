<?php

namespace App\Livewire\Vendor\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\VariantAttributeValue;
use App\Models\Certificate;
use Illuminate\Support\Str;
use App\Livewire\Traits\WithCategoryPicker;

class ProductManagement extends Component
{
    use WithPagination, WithFileUploads;
     use WithCategoryPicker;

    // Filters & search
    public string $search = '';
    public ?int $categoryFilter = null;
    public ?string $statusFilter = null; // pending|approved|rejected|null
    public ?bool $reservedOnly = null;
    public ?bool $activeOnly = null;
    public int $perPage = 10;

    // Bulk selection
    public array $selected = [];   // product ids
    public bool $selectPage = false;
    public ?string $pendingBulkAction = null; // 'activate'|'deactivate'|'delete'

    // Form (create/edit)
    public ?int $editingId = null;
    public string $name = '';
    public ?int $category_id = null;
    public string $description = '';
    public float|string $price = '';
    public string $video_url = '';
    public ?string $videoId = null; // derived from video_url
    public bool $is_reserved = false;
    public bool $is_signed = false; // vendor can mark signed; admin approves certificate
    public bool $is_active = true;  // vendor can toggle visibility, status stays admin-controlled

    // Pricing mode toggle (UX only, not persisted): false = single price, true = per-variant pricing
    public bool $use_variants = false;

    // Auction fields (exist on Product model)
    public float|string $reserve_price = '';
    public float|string $min_increment = '';
    public float|string $buy_now_price = '';

    // Status (read-only on form; set to pending on save)
    public string $status = 'pending';

    // Images
    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newImages = [];            // new uploads
    /** @var array<int, string> */
    public array $existingImages = [];       // persisted *relative* storage paths
    /** @var array<int, int|string> */
    public array $toRemove = [];             // keys from existingImages to remove

    // Reordering (receive sorted keys from JS)
    public array $existingOrder = [];        // e.g., [2,0,1]
    public array $newOrder = [];             // e.g., [1,0,2]

    // Variants
    public array $variants = []; // [ ['id'=>?, 'sku'=>'', 'price'=>?, 'stock'=>?, 'value_ids'=>[], 'value_ids_by_attr'=>[]], ... ]
    public $attributeValues;     // Collection of VariantAttributeValue with 'attribute' relationship (for selects)
    public array $attributesIndex = []; // attribute_id => attribute_name
    public array $valuesByAttribute = []; // attribute_id => [ ['id'=>, 'value'=>], ... ]

    // Certificate (if signed)
    public $certificateFile = null; // UploadedFile|null
    public bool $hasCertificate = false; // computed for edit: true if product already has any certificate uploaded

    // Delete (single)
    public ?int $deleteId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => null],
        'statusFilter' => ['except' => null],
        'reservedOnly' => ['except' => null],
        'activeOnly' => ['except' => null],
    ];

    public function mount(): void
    {
        $this->attributeValues = VariantAttributeValue::with('attribute')->get();
        // Build attribute -> values lists for nicer UI
        $this->attributesIndex = $this->attributeValues
            ->pluck('attribute.name', 'attribute_id')
            ->unique()
            ->toArray();

        $this->valuesByAttribute = $this->attributeValues
            ->groupBy('attribute_id')
            ->map(function ($group) {
                return $group->map(fn($v) => ['id' => $v->id, 'value' => $v->value])->values()->all();
            })
            ->toArray();
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingCategoryFilter() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingReservedOnly() { $this->resetPage(); }
    public function updatingActiveOnly() { $this->resetPage(); }

    

    /* ---------------------- BULK ACTIONS ---------------------- */

    public function updatedSelectPage($value): void
    {
        $this->selected = $value ? $this->pageProductIds() : [];
    }

    protected function pageProductIds(): array
    {
        $vendorId = auth()->id();
        $page = Product::query()
            ->where('vendor_id', $vendorId)
            ->when($this->search, function($q) {
                $s = '%'.$this->search.'%';
                $q->where(function($qq) use ($s) {
                    $qq->where('name', 'like', $s)
                       ->orWhere('description', 'like', $s);
                });
            })
            ->when($this->categoryFilter, fn($q)=>$q->where('category_id', $this->categoryFilter))
            ->when($this->statusFilter, fn($q)=>$q->where('status', $this->statusFilter))
            ->when($this->reservedOnly === true, fn($q)=>$q->where('is_reserved', true))
            ->when($this->activeOnly === true, fn($q)=>$q->where('is_active', true))
            ->latest('id')
            ->paginate($this->perPage);

        return $page->pluck('id')->toArray();
    }

    public function askBulkAction(string $action): void
    {
        if (!in_array($action, ['activate','deactivate','delete'])) return;
        if (count($this->selected) === 0) {
            $this->dispatch('toast', ['type'=>'warning','message'=>'Select at least one product.']);
            return;
        }
        $this->pendingBulkAction = $action;
        $this->dispatch('show-bulk-modal');
    }

    public function runBulkAction(): void
    {
        if (!$this->pendingBulkAction) return;

        $vendorId = auth()->id();
        $products = Product::where('vendor_id', $vendorId)
            ->whereIn('id', $this->selected)
            ->get();

        switch ($this->pendingBulkAction) {
            case 'activate':
                foreach ($products as $p) {
                    $this->authorize('update', $p);
                    $p->is_active = true;
                    $p->save();
                }
                $this->dispatch('toast', ['type'=>'success','message'=>'Selected products activated.']);
                break;

            case 'deactivate':
                foreach ($products as $p) {
                    $this->authorize('update', $p);
                    $p->is_active = false;
                    $p->save();
                }
                $this->dispatch('toast', ['type'=>'success','message'=>'Selected products deactivated.']);
                break;

            case 'delete':
                foreach ($products as $p) {
                    $this->authorize('delete', $p);
                    if (is_array($p->images)) {
                        foreach ($p->images as $path) {
                            Storage::disk('public')->delete($path);
                        }
                    }
                    $p->variants()->delete();
                    $p->delete();
                }
                $this->dispatch('toast', ['type'=>'success','message'=>'Selected products deleted.']);
                break;
        }

        $this->pendingBulkAction = null;
        $this->selected = [];
        $this->selectPage = false;
        $this->dispatch('hide-bulk-modal');
    }

    /* ---------------------- CRUD ---------------------- */

    public function openCreate(): void
    {
        $this->resetForm();
        $this->buildFlatCategories();
        $this->dispatch('show-product-modal');
    }

    public function openEdit(int $id): void
    {
        $p = Product::with([
                'variants.attributeValues.attribute',
                'auctions:id,product_id',
                'certificates:id,product_id,status'
            ])
            ->where('vendor_id', auth()->id())
            ->findOrFail($id);

        $this->authorize('update', $p);

        $this->editingId    = $p->id;
        $this->name         = (string) $p->name;
        $this->category_id  = $p->category_id;
        $this->description  = (string) ($p->description ?? '');
        $this->price        = (string) ($p->price ?? '');
        $this->video_url    = (string) ($p->video_url ?? '');
        $this->videoId      = $this->extractYoutubeId($this->video_url);
        $this->is_reserved  = (bool) $p->is_reserved;
        $this->is_signed    = (bool) $p->is_signed;
        $this->is_active    = (bool) $p->is_active;
        $this->status       = (string) $p->status;

        // Auction fields
        $this->reserve_price = (string) ($p->reserve_price ?? '');
        $this->min_increment = (string) ($p->min_increment ?? '');
        $this->buy_now_price = (string) ($p->buy_now_price ?? '');

        $this->existingImages = is_array($p->images) ? array_values($p->images) : [];
        $this->newImages = [];
        $this->toRemove = [];
        $this->existingOrder = range(0, max(0, count($this->existingImages)-1));
        $this->newOrder = [];

        // Variants (populate both legacy flat array and new per-attribute binding)
        $this->variants = $p->variants->map(function ($v) {
            $byAttr = $v->attributeValues
                ->groupBy('attribute_id')
                ->map(fn($g) => $g->pluck('id')->values()->all())
                ->toArray();

            return [
                'id'                   => $v->id,
                'sku'                  => $v->sku,
                'price'                => $v->price,
                'stock'                => $v->stock,
                'value_ids'            => $v->attributeValues->pluck('id')->toArray(), // legacy
                'value_ids_by_attr'    => $byAttr,                                     // new UI binding
            ];
        })->toArray();

        // Pricing mode: on if there is at least one variant with a price
        $this->use_variants = $p->variants->count() > 0 && $p->variants->whereNotNull('price')->count() > 0;

        // If reserved, UI must not allow/use variant pricing
        if ($this->is_reserved) {
            $this->use_variants = false;
        }

        // Certificates presence (any status)
        $this->hasCertificate = $p->certificates->isNotEmpty();

        $this->buildFlatCategories();

        $this->dispatch('show-product-modal');
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'id'                 => null,
            'sku'                => '',
            'price'              => null,
            'stock'              => null,
            'value_ids'          => [],   // legacy (safe to keep)
            'value_ids_by_attr'  => [],   // needed for multi-attribute UI
        ];
        $this->use_variants = true; // UX: adding a variant flips pricing mode on
    }

    public function removeVariant(int $i): void
    {
        if (!array_key_exists($i, $this->variants)) return;

        $v = $this->variants[$i];
        if (!empty($v['id'])) {
            $variant = ProductVariant::where('id', $v['id'])
                ->whereHas('product', fn($q)=>$q->where('vendor_id', auth()->id()))
                ->first();
            if ($variant) {
                $variant->attributeValues()->detach();
                $variant->delete();
            }
        }
        array_splice($this->variants, 1 * $i, 1);

        // If we removed the last one, fall back to single price mode
        if (empty($this->variants)) {
            $this->use_variants = false;
        }
    }

    public function updatedUseVariants($value): void
    {
        // When switching to variants mode, ensure there is at least one row to edit
        if ($value && empty($this->variants)) {
            $this->addVariant();
        }
    }

    // NEW: If reserved is turned on, force variants off (and UI will hide base price too)
    public function updatedIsReserved($value): void
    {
        if ($value) {
            $this->use_variants = false;
        }
    }

    public function updatedVideoUrl(): void
    {
        $this->videoId = $this->extractYoutubeId($this->video_url);
    }

    protected function extractYoutubeId(?string $url): ?string
    {
        if (!$url) return null;
        $patterns = [
            '~youtu\.be/([A-Za-z0-9_-]{6,})~i',
            '~youtube\.com.*[?&]v=([A-Za-z0-9_-]{6,})~i',
            '~youtube\.com/embed/([A-Za-z0-9_-]{6,})~i',
            '~youtube\.com/shorts/([A-Za-z0-9_-]{6,})~i',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $url, $m)) return $m[1];
        }
        return null;
    }

    public function save(): void
    {
        // Build dynamic rules for price and variants based on mode
        $rules = $this->rules();

        if ($this->is_reserved) {
            // Auction flow: no base price or variant price required/used
            $rules['price'] = ['nullable','numeric','min:0'];
            $rules['variants.*.price'] = ['nullable','numeric','min:0'];
        } elseif ($this->use_variants) {
            // Variant pricing (non-auction): variants need prices, base product price not required
            $rules['price'] = ['nullable','numeric','min:0'];
            $rules['variants.*.price'] = ['required','numeric','min:0'];
            if (empty($this->variants)) {
                $this->addError('variants', 'Add at least one variant for variant pricing.');
                return;
            }
        } else {
            // Single price (non-auction)
            $rules['price'] = ['required','numeric','min:0'];
            $rules['variants.*.price'] = ['nullable','numeric','min:0'];
        }

        // Auction field rules
        if ($this->is_reserved) {
            $rules['min_increment'] = ['required','numeric','min:1'];
            $rules['reserve_price'] = ['nullable','numeric','min:0'];
            $rules['buy_now_price'] = ['nullable','numeric','min:0'];
        } else {
            $rules['min_increment'] = ['nullable','numeric','min:0'];
            $rules['reserve_price'] = ['nullable','numeric','min:0'];
            $rules['buy_now_price'] = ['nullable','numeric','min:0'];
        }

        // Validate
        $data = $this->validate($rules);

        // Logical auction constraints
        if ($this->is_reserved && $this->reserve_price !== '' && $this->buy_now_price !== '') {
            if ((float)$this->buy_now_price < (float)$this->reserve_price) {
                $this->addError('buy_now_price', 'Buy now price must be greater than or equal to reserve price.');
                return;
            }
        }

        // Image constraints
        $totalExisting = count($this->existingImages) - count($this->toRemove);
        if (!$this->editingId) {
            if (count($this->newImages) === 0) {
                $this->addError('newImages', 'Please upload at least one product image.');
                return;
            }
        } else {
            if ($totalExisting <= 0 && count($this->newImages) === 0) {
                $this->addError('newImages', 'Please keep or upload at least one product image.');
                return;
            }
        }
        if ($totalExisting + count($this->newImages) > 8) {
            $this->addError('newImages', 'You can have a maximum of 8 images per product.');
            return;
        }

        // Certificate requirement
        if ($this->is_signed) {
            $needsCertNow = false;
            if (!$this->editingId) {
                $needsCertNow = true;
            } else {
                $needsCertNow = !$this->hasCertificate && !$this->certificateFile;
            }
            if ($needsCertNow && !$this->certificateFile) {
                $this->addError('certificateFile', 'Signed items require a certificate PDF.');
                return;
            }
        }

        $isCreate = !$this->editingId;
        if ($this->editingId) {
            $product = Product::where('vendor_id', auth()->id())->findOrFail($this->editingId);
            $this->authorize('update', $product);
        } else {
            $product = new Product();
            $product->vendor_id = auth()->id();
        }

        // Reorder existing images
        if (!empty($this->existingOrder) && count($this->existingImages) > 1) {
            $reordered = [];
            foreach ($this->existingOrder as $k) {
                if (isset($this->existingImages[$k])) $reordered[] = $this->existingImages[$k];
            }
            $this->existingImages = $reordered;
        }

        // Remove flagged existing images
        if (!empty($this->toRemove)) {
            foreach ($this->toRemove as $key) {
                if (isset($this->existingImages[$key])) {
                    Storage::disk('public')->delete($this->existingImages[$key]);
                    unset($this->existingImages[$key]);
                }
            }
            $this->existingImages = array_values($this->existingImages);
        }

        // Reorder new (by dragged order)
        if (!empty($this->newOrder) && count($this->newImages) > 1) {
            $reorderedNew = [];
            foreach ($this->newOrder as $idx) {
                if (isset($this->newImages[$idx])) $reorderedNew[] = $this->newImages[$idx];
            }
            $this->newImages = $reorderedNew;
        }

        // Store new images
        $stored = [];
        foreach ($this->newImages as $file) {
            $stored[] = $file->store('vendor/'.auth()->id().'/products', 'public');
        }
        $images = array_values(array_filter([...$this->existingImages, ...$stored]));

        // If using variants OR reserved for auction: base price is null
        $finalProductPrice = ($this->use_variants || $this->is_reserved)
            ? null
            : ((string)$this->price === '' ? null : (float)$this->price);

        // Save product (status back to pending)
        $product->fill([
            'name'            => $this->name,
            'category_id'     => $this->category_id,
            'description'     => $this->description ?: null,
            'price'           => $finalProductPrice,
            'video_url'       => $this->video_url ?: null,
            'is_reserved'     => (bool) $this->is_reserved,
            'is_signed'       => (bool) $this->is_signed,
            'images'          => $images,
            'status'          => 'pending',
            'rejection_reason'=> null,
            'rejected_at'     => null,
            // Auction fields
            'reserve_price'   => $this->is_reserved && $this->reserve_price !== '' ? (float)$this->reserve_price : null,
            'min_increment'   => $this->is_reserved && $this->min_increment !== '' ? (float)$this->min_increment : null,
            'buy_now_price'   => $this->is_reserved && $this->buy_now_price !== '' ? (float)$this->buy_now_price : null,
            'is_active'       => (bool) $this->is_active,
        ]);
        $product->save();

        // Variants upsert
        $seenVariantIds = [];
        foreach ($this->variants as $v) {
            // SKU: keep auto-generate fallback
            $providedSku = trim((string) ($v['sku'] ?? ''));
            $sku = $providedSku !== '' ? $providedSku : $this->generateSku($product);

            // Ensure uniqueness
            if (!empty($sku)) {
                $query = ProductVariant::where('sku', $sku);
                if (!empty($v['id'])) $query->where('id', '!=', $v['id']);
                if ($query->exists()) {
                    $prefix = Str::limit($sku, 110, '');
                    do { $sku = $prefix . '-' . strtoupper(Str::random(4)); }
                    while (ProductVariant::where('sku', $sku)->exists());
                }
            }

            // Variant price applies only when use_variants is on AND not reserved
            $variantPrice = ($this->use_variants && !$this->is_reserved) ? ($v['price'] ?? null) : null;

            $variant = null;
            if (!empty($v['id'])) {
                $variant = ProductVariant::where('id', $v['id'])
                    ->where('product_id', $product->id)->first();
                if ($variant) {
                    $variant->update([
                        'sku'   => $sku,
                        'price' => $variantPrice,
                        'stock' => $v['stock'] ?? null,
                    ]);
                }
            } else {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku'        => $sku,
                    'price'      => $variantPrice,
                    'stock'      => $v['stock'] ?? null,
                ]);
            }

            if ($variant) {
                $seenVariantIds[] = $variant->id;

                // Flatten multi-attribute selections, fallback to legacy flat array
                if (isset($v['value_ids_by_attr']) && is_array($v['value_ids_by_attr'])) {
                    $all = [];
                    foreach ($v['value_ids_by_attr'] as $ids) {
                        foreach ((array) $ids as $id) {
                            if ($id !== null && $id !== '') {
                                $all[] = (int) $id;
                            }
                        }
                    }
                    $valueIds = array_values(array_unique($all));
                } else {
                    $valueIds = array_values(array_unique(array_map('intval', (array) ($v['value_ids'] ?? []))));
                }

                $variant->attributeValues()->sync($valueIds);
            }
        }

        // Remove variants that were deleted in UI
        if ($product->exists) {
            ProductVariant::where('product_id', $product->id)
                ->whereNotIn('id', $seenVariantIds ?: [0])
                ->get()
                ->each(function($variant){
                    $variant->attributeValues()->detach();
                    $variant->delete();
                });
        }

        // Certificate handling
        if ($this->is_signed && $this->certificateFile) {
            $cert = $this->certificateFile->store('certificates', 'public');
            Certificate::create([
                'product_id' => $product->id,
                'file_path'  => $cert,
                'status'     => 'pending',
            ]);
        }

        // UI feedback
        session()->flash('success', $isCreate ? 'Product created and sent for approval.' : 'Product updated and sent for approval.');
        $this->dispatch('hide-product-modal');
        $this->dispatch('toast', ['type'=>'success','message'=>'Product saved. Awaiting admin approval.']);
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $product = Product::where('vendor_id', auth()->id())->findOrFail($id);
        $this->authorize('update', $product);
        $product->is_active = ! (bool) $product->is_active;
        $product->save();

        $this->dispatch('toast', [
            'type'=>'success',
            'message'=> $product->is_active ? 'Product activated.' : 'Product deactivated.'
        ]);
    }

    public function confirmDelete(int $id): void
    {
        $product = Product::where('vendor_id', auth()->id())->findOrFail($id);
        $this->authorize('delete', $product);

        $this->deleteId = $id;
        $this->dispatch('show-delete-modal');
    }

    public function deleteConfirmed(): void
    {
        if (!$this->deleteId) return;
        $product = Product::where('vendor_id', auth()->id())->findOrFail($this->deleteId);
        $this->authorize('delete', $product);

        if (is_array($product->images)) {
            foreach ($product->images as $path) {
                Storage::disk('public')->delete($path);
            }
        }
        $product->variants()->delete();
        $product->delete();

        $this->deleteId = null;
        session()->flash('success', 'Product deleted.');
        $this->dispatch('hide-delete-modal');
        $this->dispatch('toast', ['type'=>'success','message'=>'Product deleted.']);
    }

    /* ---------------------- Reorder from JS ---------------------- */

    public function reorderExisting(array $orderedKeys): void
    {
        $this->existingOrder = array_map('intval', $orderedKeys);
    }

    public function reorderNew(array $orderedIdx): void
    {
        $this->newOrder = array_map('intval', $orderedIdx);
    }

    /* ---------------------- Validation ---------------------- */
    private function shouldRequirePrice(): bool
    {
        // Keep this helper for future use if you want; current dynamic rules are in save()
        // Require a base price only when NOT reserved, NOT using variants, and there are NO variant rows.
        return !$this->is_reserved && !$this->use_variants && empty($this->variants);
    }

    protected function rules(): array
    {
        return [
            'name'         => ['required','string','max:200'],
            'category_id'  => ['required', Rule::exists('categories','id')],
            'description'  => ['nullable','string','max:5000'],
            // 'price' is set dynamically in save() depending on reserved/variants
            'video_url'    => ['nullable','url','max:255'],
            'is_reserved'  => ['boolean'],
            'is_signed'    => ['boolean'],
            'is_active'    => ['boolean'],
            'newImages.*'  => ['image','mimes:jpg,jpeg,png,webp','max:4096'],
            'variants.*.sku'                => ['nullable','string','max:120'],
            // 'variants.*.price' set dynamically in save()
            'variants.*.stock'              => ['nullable','integer','min:0'],

            // LEGACY flat list support
            'variants.*.value_ids'          => ['array'],
            'variants.*.value_ids.*'        => ['exists:variant_attribute_values,id'],

            // NEW per-attribute structure for multi-selects
            'variants.*.value_ids_by_attr'      => ['array'],
            'variants.*.value_ids_by_attr.*'    => ['array'],
            'variants.*.value_ids_by_attr.*.*'  => ['exists:variant_attribute_values,id'],

            'certificateFile'      => ['nullable','file','mimes:pdf','max:4096'],

            // Auction fields validated dynamically in save()
            'reserve_price' => ['nullable'],
            'min_increment' => ['nullable'],
            'buy_now_price' => ['nullable'],
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId','name','category_id','description','price','video_url','videoId',
            'is_reserved','is_signed','is_active','status','use_variants',
            'reserve_price','min_increment','buy_now_price',
            'newImages','existingImages','toRemove','existingOrder','newOrder',
            'variants','certificateFile','hasCertificate'
        ]);
        $this->is_active = true;
        $this->is_reserved = false;
        $this->is_signed = false;
        $this->status = 'pending';
        $this->use_variants = false;
        $this->reserve_price = '';
        $this->min_increment = '';
        $this->buy_now_price = '';
        $this->newImages = [];
        $this->existingImages = [];
        $this->toRemove = [];
        $this->existingOrder = [];
        $this->newOrder = [];
        $this->variants = [];
        $this->hasCertificate = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function removeExistingImage($key): void
    {
        if (isset($this->existingImages[$key])) {
            $this->toRemove[] = $key;
        }
    }

    private function generateSku(Product $product): string
    {
        $base = strtoupper(Str::of($product->name)->slug('')->limit(10, ''));
        if ($base === '') $base = 'PRD';
        do {
            $sku = $base . '-' . $product->id . '-' . strtoupper(Str::random(4));
        } while (ProductVariant::where('sku', $sku)->exists());

        return $sku;
    }

    private function buildCategoryOptions()
    {
        // Pull once, then compute full path in memory (no N+1)
        $cats = Category::where('is_active', true)->get(['id','name','parent_id']);
        $byId = $cats->keyBy('id');

        $cache = [];
        $pathFor = function ($id) use (&$byId, &$cache) {
            if (isset($cache[$id])) return $cache[$id];

            $cur = $byId->get($id);
            if (!$cur) return $cache[$id] = '';

            $segments = [$cur->name];
            $pid = $cur->parent_id;
            while ($pid && ($p = $byId->get($pid))) {
                array_unshift($segments, $p->name);
                $pid = $p->parent_id;
            }
            return $cache[$id] = implode(' - ', $segments);
        };

        return $cats
            ->map(function ($c) use ($pathFor) {
                // attach a computed label without changing your DB schema
                $c->full_name = $pathFor($c->id);
                return $c;
            })
            ->sortBy('full_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function render()
    {
        $vendorId = auth()->id();

        $products = Product::query()
            ->with(['category:id,name,parent_id', 'variants:id,product_id,price,stock,sku', 'auctions:id,product_id'])
            ->withCount('variants')
            ->where('vendor_id', $vendorId)
            ->when($this->search, function($q) {
                $s = '%'.$this->search.'%';
                $q->where(function($qq) use ($s) {
                    $qq->where('name', 'like', $s)
                       ->orWhere('description', 'like', $s);
                });
            })
            ->when($this->categoryFilter, fn($q)=>$q->where('category_id', $this->categoryFilter))
            ->when($this->statusFilter, fn($q)=>$q->where('status', $this->statusFilter))
            ->when($this->reservedOnly === true, fn($q)=>$q->where('is_reserved', true))
            ->when($this->activeOnly === true, fn($q)=>$q->where('is_active', true))
            ->latest('id')
            ->paginate($this->perPage);

        $categories = $this->buildCategoryOptions();

        return view('livewire.vendor.pages.product-management', [
            'products'   => $products,
            'categories' => $categories,
        ])->layout('components.layouts.vendor');
    }
}
