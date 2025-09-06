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

    // -------- Listing / filters ----------
    public string $search = '';
    public ?int $categoryFilter = null;
    public ?string $statusFilter = null; // pending|approved|rejected|null
    public ?bool $reservedOnly = null;
    public ?bool $activeOnly = null;
    public int $perPage = 10;

    // Bulk selection
    public array $selected = [];
    public bool $selectPage = false;
    public ?string $pendingBulkAction = null; // activate|deactivate|delete

    // -------- Wizard state (create/edit) ----------
    public ?int $editingId = null;
    public int $step = 1; // 1 or 2
    public int $modalKey = 0;

    // Step 1: core identity + pricing decision
    public string $name = '';
    public ?int $category_id = null;
    public bool $use_variants = false;
    public bool $is_reserved = false;
    public bool $is_signed = false;
    public bool $is_active = true;
    public float|string $price = ''; // base price when not variants nor reserved

    // Auction (reserved) – validated only if is_reserved
    public float|string $reserve_price = '';
    public float|string $min_increment = '';
    public float|string $buy_now_price = '';

    // Step 2: assets & enrichment
    public string $description = '';
    public string $video_url = '';
    public ?string $videoId = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newImages = [];
    /** @var array<int, string> */
    public array $existingImages = [];
    /** @var array<int, int|string> */
    public array $toRemove = [];

    public array $existingOrder = [];
    public array $newOrder = [];

    // Variants
    public array $variants = []; // [ {id, sku, price, stock, value_ids[], value_ids_by_attr{attrId:[valueId,...]}} ]
    public $attributeValues;
    public array $attributesIndex = [];
    public array $valuesByAttribute = [];

    // Certificate (signed items)
    public $certificateFile = null;
    public bool $hasCertificate = false;

    // Status (read-only)
    public string $status = 'pending';

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
        // Build attributes/value maps once
        $this->attributeValues = VariantAttributeValue::with('attribute')->get();
        $this->attributesIndex = $this->attributeValues
            ->pluck('attribute.name', 'attribute_id')
            ->unique()
            ->toArray();

        $this->valuesByAttribute = $this->attributeValues
            ->groupBy('attribute_id')
            ->map(fn($g) => $g->map(fn($v) => ['id' => $v->id, 'value' => $v->value])->values()->all())
            ->toArray();
    }

    // Pagination resetters
    public function updatingSearch(){ $this->resetPage(); }
    public function updatingCategoryFilter(){ $this->resetPage(); }
    public function updatingStatusFilter(){ $this->resetPage(); }
    public function updatingReservedOnly(){ $this->resetPage(); }
    public function updatingActiveOnly(){ $this->resetPage(); }

    /* ================== BULK ================== */

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

    /* ================== WIZARD (CREATE/EDIT) ================== */

    public function openCreate(): void
    {
        $this->resetForm();
        $this->buildFlatCategories();
        $this->step = 1;
        $this->modalKey++;
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

        // Populate Step 1
        $this->editingId    = $p->id;
        $this->name         = (string) $p->name;
        $this->category_id  = $p->category_id;
        $this->is_reserved  = (bool) $p->is_reserved;
        $this->is_signed    = (bool) $p->is_signed;
        $this->is_active    = (bool) $p->is_active;

        // price & variants
        $this->use_variants = $p->variants()->exists() && $p->variants()->whereNotNull('price')->exists();
        $this->price        = ($this->use_variants || $this->is_reserved) ? '' : (string) ($p->price ?? '');

        // Auction
        $this->reserve_price = (string) ($p->reserve_price ?? '');
        $this->min_increment = (string) ($p->min_increment ?? '');
        $this->buy_now_price = (string) ($p->buy_now_price ?? '');

        // Step 2
        $this->description  = (string) ($p->description ?? '');
        $this->video_url    = (string) ($p->video_url ?? '');
        $this->videoId      = $this->extractYoutubeId($this->video_url);

        $this->existingImages = is_array($p->images) ? array_values($p->images) : [];
        $this->newImages = [];
        $this->toRemove = [];
        $this->existingOrder = range(0, max(0, count($this->existingImages)-1));
        $this->newOrder = [];

        // Variants
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
                'value_ids'            => $v->attributeValues->pluck('id')->toArray(),
                'value_ids_by_attr'    => $byAttr,
            ];
        })->toArray();

        // Certificates presence
        $this->hasCertificate = $p->certificates->isNotEmpty();

        $this->buildFlatCategories();

        $this->status = (string) $p->status;
        $this->step = 1;
        $this->modalKey++;
        $this->dispatch('show-product-modal');
    }

    /* ------ Step navigation with per-step validation ------ */

    public function nextStep(): void
    {
        // Validate Step 1 only
        $this->validateStep1();
        $this->step = 2;
    }

    public function prevStep(): void
    {
        $this->step = 1;
    }

    /* ------ Step 1 rules ------ */
    protected function validateStep1(): void
    {
        $rules = [
            'name'        => ['required','string','max:200'],
            'category_id' => ['required', Rule::exists('categories','id')],
            'is_reserved' => ['boolean'],
            'is_signed'   => ['boolean'],
            'is_active'   => ['boolean'],
            'use_variants'=> ['boolean'],
        ];

        if ($this->is_reserved) {
            // Auction flow: price not required; auction fields apply
            $rules['price'] = ['nullable','numeric','min:0'];
            $rules['min_increment'] = ['required','numeric','min:1'];
            $rules['reserve_price'] = ['nullable','numeric','min:0'];
            $rules['buy_now_price'] = ['nullable','numeric','min:0'];
        } elseif ($this->use_variants) {
            // Variants pricing: base price not required here
            $rules['price'] = ['nullable','numeric','min:0'];
        } else {
            // Single price
            $rules['price'] = ['required','numeric','min:0'];
        }

        $this->validate($rules);

        // Logical auction constraint
        if ($this->is_reserved && $this->reserve_price !== '' && $this->buy_now_price !== '') {
            if ((float)$this->buy_now_price < (float)$this->reserve_price) {
                $this->addError('buy_now_price', 'Buy now price must be greater than or equal to reserve price.');
            }
        }
    }

    /* ------ Step 2 rules ------ */
    protected function validateStep2(): void
    {
        $rules = [
            'description' => ['nullable','string','max:5000'],
            'video_url'   => ['nullable','url','max:255'],
            'newImages.*' => ['image','mimes:jpg,jpeg,png,webp','max:4096'],
        ];

        // Image constraints: at least one image either existing or new
        $totalExisting = count($this->existingImages) - count($this->toRemove);
        if (!$this->editingId) {
            // New product must upload at least 1
            if (count($this->newImages) === 0) {
                $this->addError('newImages', 'Please upload at least one product image.');
            }
        } else {
            if ($totalExisting <= 0 && count($this->newImages) === 0) {
                $this->addError('newImages', 'Please keep or upload at least one product image.');
            }
        }
        if ($totalExisting + count($this->newImages) > 8) {
            $this->addError('newImages', 'You can have a maximum of 8 images per product.');
        }

        // Variants required pricing logic only when using variants AND not reserved
        if ($this->use_variants && !$this->is_reserved) {
            $rules['variants.*.price'] = ['required','numeric','min:0'];
        } else {
            $rules['variants.*.price'] = ['nullable','numeric','min:0'];
        }

        $rules = array_merge($rules, [
            'variants.*.sku'   => ['nullable','string','max:120'],
            'variants.*.stock' => ['nullable','integer','min:0'],
            'variants.*.value_ids' => ['array'],
            'variants.*.value_ids.*' => ['exists:variant_attribute_values,id'],
            'variants.*.value_ids_by_attr' => ['array'],
            'variants.*.value_ids_by_attr.*' => ['array'],
            'variants.*.value_ids_by_attr.*.*' => ['exists:variant_attribute_values,id'],
            'certificateFile' => ['nullable','file','mimes:pdf','max:4096'],
        ]);

        // Certificate requirement for signed items
        if ($this->is_signed) {
            $needsCertNow = false;
            if (!$this->editingId) {
                $needsCertNow = true;
            } else {
                $needsCertNow = !$this->hasCertificate && !$this->certificateFile;
            }
            if ($needsCertNow && !$this->certificateFile) {
                $this->addError('certificateFile', 'Signed items require a certificate PDF.');
            }
        }

        $this->validate($rules);
    }

    /* ------ Save from Step 2 (after both steps valid) ------ */
    public function save(): void
    {
        // Validate step 1 (in case user jumped back) and step 2
        $this->validateStep1();
        $this->validateStep2();

        // Also logical auction constraint (again defensively)
        if ($this->is_reserved && $this->reserve_price !== '' && $this->buy_now_price !== '') {
            if ((float)$this->buy_now_price < (float)$this->reserve_price) {
                $this->addError('buy_now_price', 'Buy now price must be greater than or equal to reserve price.');
                return;
            }
        }

        // Compute images count constraints already enforced above

        $isCreate = !$this->editingId;
        if ($this->editingId) {
            $product = Product::where('vendor_id', auth()->id())->findOrFail($this->editingId);
            $this->authorize('update', $product);
        } else {
            $product = new Product();
            $product->vendor_id = auth()->id();
        }

        // Reorder & removals for existing images
        if (!empty($this->existingOrder) && count($this->existingImages) > 1) {
            $reordered = [];
            foreach ($this->existingOrder as $k) {
                if (isset($this->existingImages[$k])) $reordered[] = $this->existingImages[$k];
            }
            $this->existingImages = $reordered;
        }
        if (!empty($this->toRemove)) {
            foreach ($this->toRemove as $key) {
                if (isset($this->existingImages[$key])) {
                    Storage::disk('public')->delete($this->existingImages[$key]);
                    unset($this->existingImages[$key]);
                }
            }
            $this->existingImages = array_values($this->existingImages);
        }

        // Reorder new uploads
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

        // Base price is null when using variants OR reserved
        $finalProductPrice = ($this->use_variants || $this->is_reserved)
            ? null
            : ((string)$this->price === '' ? null : (float)$this->price);

        // Save product (status -> pending)
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
            // Auction
            'reserve_price'   => $this->is_reserved && $this->reserve_price !== '' ? (float)$this->reserve_price : null,
            'min_increment'   => $this->is_reserved && $this->min_increment !== '' ? (float)$this->min_increment : null,
            'buy_now_price'   => $this->is_reserved && $this->buy_now_price !== '' ? (float)$this->buy_now_price : null,
            'is_active'       => (bool) $this->is_active,
        ]);
        $product->save();

        // Variants upsert (only meaningful when not reserved)
        $seenVariantIds = [];
        foreach ($this->variants as $v) {
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

                // Sync attribute values
                if (isset($v['value_ids_by_attr']) && is_array($v['value_ids_by_attr'])) {
                    $all = [];
                    foreach ($v['value_ids_by_attr'] as $ids) {
                        foreach ((array) $ids as $id) {
                            if ($id !== null && $id !== '') $all[] = (int) $id;
                        }
                    }
                    $valueIds = array_values(array_unique($all));
                } else {
                    $valueIds = array_values(array_unique(array_map('intval', (array) ($v['value_ids'] ?? []))));
                }
                $variant->attributeValues()->sync($valueIds);
            }
        }

        // Remove variants deleted in UI
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

        // UI feedback & reset
        session()->flash('success', $isCreate ? 'Product created and sent for approval.' : 'Product updated and sent for approval.');
        $this->dispatch('hide-product-modal');
        $this->dispatch('toast', ['type'=>'success','message'=>'Product saved. Awaiting admin approval.']);
        $this->resetForm();
    }

    /* ================== Inline interactions ================== */

    public function updatedUseVariants($value): void
    {
        if ($value && empty($this->variants)) {
            $this->addVariant();
        }
    }

    public function updatedIsReserved($value): void
    {
        if ($value) {
            $this->use_variants = false; // reserved disables variant pricing
        }
    }

    public function updatedVideoUrl(): void
    {
        $this->videoId = $this->extractYoutubeId($this->video_url);
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'id'                 => null,
            'sku'                => '',
            'price'              => null,
            'stock'              => null,
            'value_ids'          => [],
            'value_ids_by_attr'  => [],
        ];
        $this->use_variants = true;
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

        if (empty($this->variants)) {
            $this->use_variants = false;
        }
    }

    public function reorderExisting(array $orderedKeys): void
    {
        $this->existingOrder = array_map('intval', $orderedKeys);
    }

    public function reorderNew(array $orderedIdx): void
    {
        $this->newOrder = array_map('intval', $orderedIdx);
    }

    public function removeExistingImage($key): void
    {
        if (isset($this->existingImages[$key])) {
            $this->toRemove[] = $key;
        }
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

    /* ================== Helpers ================== */

    protected function resetForm(): void
    {
        $this->reset([
            'editingId','step','name','category_id','use_variants','is_reserved','is_signed','is_active',
            'price','reserve_price','min_increment','buy_now_price',
            'description','video_url','videoId',
            'newImages','existingImages','toRemove','existingOrder','newOrder',
            'variants','certificateFile','hasCertificate','status'
        ]);
        $this->step = 1;
        $this->status = 'pending';
        $this->is_active = true;
        $this->use_variants = false;
        $this->is_reserved = false;
        $this->is_signed = false;
        $this->price = '';
        $this->reserve_price = '';
        $this->min_increment = '';
        $this->buy_now_price = '';
        $this->description = '';
        $this->video_url = '';
        $this->videoId = null;
        $this->existingImages = [];
        $this->newImages = [];
        $this->toRemove = [];
        $this->existingOrder = [];
        $this->newOrder = [];
        $this->variants = [];
        $this->hasCertificate = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function buildCategoryOptions()
    {
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
                $c->full_name = $pathFor($c->id);
                return $c;
            })
            ->sortBy('full_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
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

    private function generateSku(Product $product): string
    {
        $base = strtoupper(Str::of($product->name)->slug('')->limit(10, ''));
        if ($base === '') $base = 'PRD';
        do {
            $sku = $base . '-' . $product->id . '-' . strtoupper(Str::random(4));
        } while (ProductVariant::where('sku', $sku)->exists());

        return $sku;
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
