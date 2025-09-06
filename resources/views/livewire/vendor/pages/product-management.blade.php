@php use Illuminate\Support\Str; @endphp


<div class="container-fluid py-4">

    {{-- Flash --}}
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Toolbar --}}
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Products</h4>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text" class="form-control" style="max-width:240px" placeholder="Search..."
                wire:model.live.debounce.400ms="search">

            <select class="form-select" style="max-width:200px" wire:model.live="categoryFilter">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
        @endforeach
      </select>

      <select class="form-select" style="max-width:170px" wire:model.live="statusFilter">
        <option value="">Any Status</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>

      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="reservedOnly" wire:model.live="reservedOnly">
        <label for="reservedOnly" class="form-check-label">Reserved</label>
      </div>

      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="activeOnly" wire:model.live="activeOnly">
        <label for="activeOnly" class="form-check-label">Active only</label>
      </div>

      <div class="btn-group">
        <button class="btn btn-outline-success text-nowrap" wire:click="askBulkAction('activate')">
          <i class="fa-solid fa-toggle-on me-1"></i> Activate
        </button>
        <button class="btn btn-outline-warning text-nowrap" wire:click="askBulkAction('deactivate')">
          <i class="fa-solid fa-toggle-off me-1"></i> Deactivate
        </button>
        <button class="btn btn-outline-danger text-nowrap" wire:click="askBulkAction('delete')">
          <i class="fa-solid fa-trash-can me-1"></i> Delete
        </button>
      </div>

      <button class="btn btn-primary text-nowrap" wire:click="openCreate">
        <i class="fa-solid fa-plus me-1"></i> New Product
      </button>
    </div>
  </div>

  {{-- Table --}}
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <table class="table table-hover table-resposive align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:28px;">
              <input type="checkbox" class="form-check-input" wire:model.live="selectPage">
            </th>
            <th style="width:64px;"></th>
            <th>Name</th>
            <th>Category</th>
            <th class="text-end">Price</th>
            <th>Variants</th>
            <th>Auctions</th>
            <th>Status</th>
            <th>Active</th>
            <th>Reserved</th>
            <th style="width:240px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($products as $p)
            @php
                $thumb = (is_array($p->images) && count($p->images)) ? asset('storage/' . $p->images[0]) : null;
                if ($p->variants_count > 0) {
                    $min = $p->variants->min(fn($v) => $v->price ?? $p->price);
                    $max = $p->variants->max(fn($v) => $v->price ?? $p->price);
                }
            @endphp
            <tr>
              <td>
                <input type="checkbox" class="form-check-input" value="{{ $p->id }}" wire:model.live="selected">
              </td>
              <td>
                <div class="ratio ratio-1x1 bg-light rounded" style="width:48px; overflow:hidden;">
                  @if($thumb)
                    <img src="{{ $thumb }}" class="w-100 h-100" style="object-fit:cover;">
                  @else
                    <div class="d-flex align-items-center justify-content-center w-100 h-100 text-muted">
                      <i class="fa-regular fa-image"></i>
                    </div>
                  @endif
                </div>
              </td>
              <td>
                <div class="fw-semibold">
                  @if($p->is_reserved)<i class="fa-solid fa-tag text-info me-1"></i>@endif
                  @if($p->is_signed)<i class="fa-solid fa-certificate me-1 text-warning"></i>@endif
                  {{ $p->name }}
                </div>
              </td>
              <td>{{ $p->category->name ?? '—' }}</td>

              <td class="text-end">
                @if(($p->variants_count ?? 0) > 0 && isset($min, $max) && $min !== null && $max !== null)
                      @if($min === $max)
                        ${{ number_format((float) $min, 2) }}
                      @else
                        ${{ number_format((float) $min, 2) }} – ${{ number_format((float) $max, 2) }}
                      @endif
                @else
                      ${{ number_format((float) $p->price, 2) }}
                @endif
              </td>

              <td>{{ $p->variants_count }}</td>
              <td>{{ $p->auctions->count() }}</td>

              <td>
                <span class="badge bg-{{ $p->status === 'approved' ? 'success' : ($p->status === 'rejected' ? 'danger' : 'warning') }}">
                  {{ ucfirst($p->status) }}
                </span>
              </td>

              <td>
                <span class="badge {{ $p->is_active ? 'bg-success' : 'bg-secondary' }}">
                  {{ $p->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>

              <td>
                @if($p->is_reserved)
                      <span class="badge bg-warning text-dark">Reserved</span>
                @else
                      <span class="badge bg-dark text-light">No</span>
                @endif
              </td>

              <td class="text-end">
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary text-nowrap" wire:click="openEdit({{ $p->id }})">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                  </button>
                  <a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('vendor.auctions.index', ['product' => $p->id]) }}">
                    <i class="fa-solid fa-gavel"></i> Auctions
                  </a>
                  <button class="btn btn-sm btn-outline-{{ $p->is_active ? 'warning' : 'success' }} text-nowrap"
                          wire:click="toggleActive({{ $p->id }})">
                    <i class="fa-solid {{ $p->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                    {{ $p->is_active ? 'Deactivate' : 'Activate' }}
                  </button>
                  <button class="btn btn-sm btn-outline-danger text-nowrap" wire:click="confirmDelete({{ $p->id }})">
                    <i class="fa-solid fa-trash-can"></i> Delete
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="text-center text-muted py-5">No products found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="text-muted small">
        @if($products->total())
              Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }}
        @else
              No results
        @endif
      </div>
      <div class="d-flex align-items-center gap-2 w-100" style="max-width:none;">
        <div class="me-auto">
          {{ $products->onEachSide(1)->links() }}
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted">Per page</span>
          <select class="form-select form-select-sm" wire:model.live="perPage" style="width:auto;">
            @foreach([10, 20, 30, 50] as $pp)
                  <option value="{{ $pp }}">{{ $pp }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
  </div>

  {{-- Create/Edit Modal (Two-step Wizard) --}}
  <div wire:ignore.self class="modal fade" id="productModal" tabindex="-1" style="color:black;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-md-down">
      <div class="modal-content" wire:key="product-modal-{{ $modalKey }}">
        <div class="modal-header">
          <h5 class="modal-title d-flex align-items-center gap-2">
            <i class="fa-solid fa-box-open"></i>
            {{ $editingId ? 'Edit Product' : 'New Product' }}
            @if($editingId)
                  <span class="badge bg-{{ $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning') }}">
                    {{ ucfirst($status) }}
                  </span>
            @endif
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        {{-- Progress --}}
        <div class="px-3 pt-3">
          <div class="d-flex align-items-center gap-2">
            <div class="flex-fill">
              <div class="progress" style="height: 6px;">
                <div class="progress-bar" role="progressbar"
                     style="width: {{ $step === 1 ? 50 : 100 }}%;"></div>
              </div>
            </div>
            <div class="small text-muted">
              Step {{ $step }} of 2
            </div>
          </div>
        </div>

        <form wire:submit.prevent="save">
          <div class="modal-body">
            @if($step === 1)
                  {{-- ========== STEP 1: Identity & Pricing Decision ========== --}}
                  <div class="row g-3">
                    {{-- Name --}}
                    <div class="col-md-7">
                      <label class="form-label">Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" wire:model.live="name" autocomplete="off">
                      @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Category (Command Palette / WithCategoryPicker) --}}
                    <div class="col-md-5 position-relative">
                      <label class="form-label">Category <span class="text-danger">*</span></label>

                      @if($category_id)
                        @php $sel = collect($flatCategories)->firstWhere('id', (int) $category_id); @endphp
                        <div class="d-flex align-items-center gap-2 mb-1">
                          <span class="badge bg-dark text-dark border">
                            <i class="fa-solid fa-folder-tree me-1"></i>
                            {{ $sel['full_path'] ?? 'Selected' }}
                          </span>
                          <button type="button" class="btn btn-sm btn-outline-primary"
                                  wire:click="clearCategory">Clear</button>
                        </div>
                      @endif

                      <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input
                          type="text"
                          class="form-control"
                          placeholder="Type to search (e.g. Electronics / Phones)"
                          wire:model.live.debounce.250ms="categoryQuery"
                          wire:focus="openCategoryPicker"
                          wire:click="openCategoryPicker"
                          wire:keydown.arrow-down.prevent="moveCategoryHighlight(1)"
                          wire:keydown.arrow-up.prevent="moveCategoryHighlight(-1)"
                          wire:keydown.enter.prevent="chooseHighlightedCategory"
                          wire:keydown.escape.prevent="closeCategoryPicker"
                          autocomplete="off"
                        >
                        <button type="button" class="btn btn-outline-primary" wire:click="openCategoryPicker">
                          Browse
                        </button>
                      </div>
                      @error('category_id') <small class="text-danger">{{ $message }}</small> @enderror

                      @if($showCategoryMenu)
                        <div class="position-absolute w-100 mt-1 border rounded bg-white shadow"
                             style="z-index: 1056; max-height: 320px; overflow:auto;">
                          @php $results = $this->categoryResults; @endphp
                          @if(empty($results))
                            <div class="px-3 py-2 text-muted small">No matches. Try a different search.</div>
                          @else
                            @foreach($results as $i => $c)
                                  <button type="button"
                                          wire:click="selectCategory({{ $c['id'] }})"
                                          class="w-100 text-start px-3 py-2 border-0 bg-transparent {{ $i === $categoryHighlight ? 'bg-primary' : '' }}"
                                          style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <span style="padding-left: {{ $c['depth'] * 14 }}px;">
                                      @if($c['depth'] > 0)
                                        <span class="text-muted me-1">{{ str_repeat('• ', min(6, $c['depth'])) }}</span>
                                      @endif
                                      <i class="fa-regular fa-folder-open me-1 text-secondary"></i>
                                      {{ $c['full_path'] }}
                                      @unless($c['is_active'])
                                        <span class="badge text-bg-secondary ms-2">Inactive</span>
                                      @endunless
                                    </span>
                                  </button>
                            @endforeach
                          @endif
                        </div>
                      @endif
                    </div>

                    {{-- Toggles --}}
                    <div class="col-12">
                      <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="useVariantsSwitch"
                                   wire:model.live="use_variants" @disabled($is_reserved)>
                            <label class="form-check-label" for="useVariantsSwitch">
                              Use variants for pricing
                            </label>
                          </div>
                          <small class="text-muted d-block">
                            @if($is_reserved)
                                  Variant pricing is disabled for reserved (auction) products.
                            @else
                                  When enabled, base product price is hidden; each variant requires a price.
                            @endif
                          </small>
                        </div>

                        <div class="col-md-3">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="signedCheck"
                                   wire:model.live="is_signed">
                            <label class="form-check-label" for="signedCheck">
                              <i class="fa-solid fa-certificate me-1"></i> Signed item
                            </label>
                          </div>
                        </div>

                        <div class="col-md-3">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="reservedCheck"
                                   wire:model.live="is_reserved">
                            <label class="form-check-label" for="reservedCheck">
                              Reserved (for auctions)
                            </label>
                          </div>
                        </div>

                        <div class="col-md-2">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activeCheck"
                                   wire:model.live="is_active">
                            <label class="form-check-label" for="activeCheck">Active</label>
                          </div>
                        </div>
                      </div>
                    </div>

                    {{-- Pricing (single) --}}
                    @if(!$use_variants && !$is_reserved)
                          <div class="col-md-4">
                            <label class="form-label">Price ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" wire:model.live="price">
                            @error('price') <small class="text-danger">{{ $message }}</small> @enderror
                          </div>
                    @endif

                    {{-- Auction (only when reserved) --}}
                    @if($is_reserved)
                          <div class="col-12">
                            <hr>
                            <h5 class="mb-2">Auction Pricing</h5>
                            <small class="text-muted d-block mb-2">
                              Base price and variant pricing are disabled for reserved products. Use these fields instead.
                            </small>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">Reserve Price ($)</label>
                            <input type="number" step="0.01" class="form-control" wire:model.live="reserve_price">
                            @error('reserve_price') <small class="text-danger">{{ $message }}</small> @enderror
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">Minimum Bid Increment ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" wire:model.live="min_increment">
                            @error('min_increment') <small class="text-danger">{{ $message }}</small> @enderror
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">Buy Now Price ($)</label>
                            <input type="number" step="0.01" class="form-control" wire:model.live="buy_now_price">
                            @error('buy_now_price') <small class="text-danger">{{ $message }}</small> @enderror
                          </div>
                    @endif
                  </div>
            @else
                  {{-- ========== STEP 2: Assets, Description, Variants, Certificate ========== --}}
                  <div class="row g-3">
                    {{-- YouTube --}}
                    <div class="col-md-8">
                      <label class="form-label">YouTube URL</label>
                      <div class="input-group">
                        <input type="url" class="form-control" wire:model.live="video_url"
                               placeholder="https://www.youtube.com/watch?v=...">
                        @if($videoId)
                              <a href="https://www.youtube.com/watch?v={{ $videoId }}" class="btn btn-outline-secondary" target="_blank">
                                <i class="fa-regular fa-circle-play me-1"></i> Open
                              </a>
                        @endif
                      </div>
                      @error('video_url') <small class="text-danger">{{ $message }}</small> @enderror
                      @if($videoId)
                        <div class="mt-2">
                          <img src="https://i.ytimg.com/vi/{{ $videoId }}/hqdefault.jpg"
                               class="rounded border" alt="YouTube thumbnail" style="max-height:140px;">
                        </div>
                      @endif
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                      <label class="form-label">Description</label>
                      <textarea class="form-control" rows="4" wire:model.live="description"
                                placeholder="Tell buyers everything important about this product (materials, condition, dimensions, etc)."></textarea>
                      @error('description') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Images (new) --}}
                    <div class="col-md-6">
                      <label class="form-label">
                        Add Images (JPG/PNG/WebP, up to 4MB each)
                        <span class="text-muted small">(1–8 images total)</span>
                      </label>
                      <input type="file" class="form-control" wire:model="newImages" multiple accept="image/*">
                      @error('newImages') <small class="text-danger d-block">{{ $message }}</small> @enderror
                      @error('newImages.*') <small class="text-danger d-block">{{ $message }}</small> @enderror

                      <div class="small text-muted mt-1">Drag to reorder the previews below.</div>
                      <div id="new-images" class="d-flex flex-wrap gap-2 mt-2" data-role="sortable-new">
                        @foreach($newImages as $idx => $img)
                              <div class="position-relative" data-idx="{{ $idx }}" draggable="true">
                                <img src="{{ $img->temporaryUrl() }}" class="rounded border"
                                     style="width:80px;height:80px;object-fit:cover;">
                                <span class="badge text-bg-secondary position-absolute top-0 start-0"
                                      style="transform: translate(-20%,-40%);">New</span>
                              </div>
                        @endforeach
                      </div>
                    </div>

                    {{-- Images (existing) --}}
                    <div class="col-md-6">
                      <label class="form-label">Existing Images</label>
                      <div id="existing-images" class="d-flex flex-wrap gap-2" data-role="sortable-existing">
                        @foreach($existingImages as $key => $path)
                              <div class="position-relative" data-key="{{ $key }}" draggable="true">
                                <img src="{{ asset('storage/' . $path) }}" class="rounded border"
                                     style="width:80px;height:80px;object-fit:cover;">
                                <button type="button"
                                        class="btn btn-sm btn-danger position-absolute top-0 end-0"
                                        style="transform: translate(30%,-30%);"
                                        wire:click="removeExistingImage({{ $key }})" title="Remove">
                                  &times;
                                </button>
                              </div>
                        @endforeach
                      </div>
                      @if(count($toRemove))
                        <div class="small text-danger mt-2">
                          {{ count($toRemove) }} image(s) will be removed on save.
                        </div>
                      @endif
                    </div>

                    {{-- Certificate (signed items) --}}
                    @if($is_signed)
                          <div class="col-md-6">
                            <label class="form-label">Upload Certificate (PDF) <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" wire:model="certificateFile" accept="application/pdf">
                            @error('certificateFile') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="text-muted d-block mt-1">
                              Certificates are submitted as <strong>Pending</strong> for admin review.
                              @if($editingId && $hasCertificate)
                                A certificate already exists; uploading again will add a new one for review.
                              @endif
                            </small>
                          </div>
                    @endif

                    {{-- Variants (only if using variants and not reserved) --}}
                    @if($use_variants && !$is_reserved)
                          <div class="col-12">
                            <hr>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                              <h5 class="mb-0">Variants</h5>
                              <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addVariant">
                                <i class="fa-solid fa-plus me-1"></i> Add Variant
                              </button>
                            </div>

                            @if(empty($variants))
                                  <div class="text-muted">No variants yet. Add one to set per-variant price/stock and attributes.</div>
                            @endif

                            @foreach($variants as $i => $v)
                                  <div class="border rounded p-3 mb-2" wire:key="variant-row-{{ $v['id'] ?? ('new-' . $i) }}">
                                    <div class="row g-2">
                                      <div class="col-md-3">
                                        <label class="form-label">SKU</label>
                                        <input type="text" class="form-control" wire:model.live="variants.{{ $i }}.sku" placeholder="Auto if blank">
                                        @error("variants.$i.sku") <small class="text-danger">{{ $message }}</small> @enderror
                                      </div>
                                      <div class="col-md-3">
                                        <label class="form-label">Price ($) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control" wire:model.live="variants.{{ $i }}.price">
                                        @error("variants.$i.price") <small class="text-danger">{{ $message }}</small> @enderror
                                      </div>
                                      <div class="col-md-3">
                                        <label class="form-label">Stock</label>
                                        <input type="number" class="form-control" wire:model.live="variants.{{ $i }}.stock">
                                        @error("variants.$i.stock") <small class="text-danger">{{ $message }}</small> @enderror
                                      </div>
                                      <div class="col-md-3 d-flex align-items-end justify-content-end">
                                        <button type="button" class="btn btn-outline-danger" wire:click="removeVariant({{ $i }})">
                                          <i class="fa-solid fa-trash-can me-1"></i> Remove
                                        </button>
                                      </div>

                                      {{-- Attribute Values --}}
                                      <div class="col-12">
                                        <div class="row g-2">
                                          @foreach($attributesIndex as $attrId => $attrName)
                                            <div class="col-md-2">
                                              <label class="form-label">{{ $attrName }}</label>
                                              <select class="form-select" multiple
                                                      wire:model.live="variants.{{ $i }}.value_ids_by_attr.{{ $attrId }}">
                                                @foreach($valuesByAttribute[$attrId] ?? [] as $opt)
                                                      <option value="{{ $opt['id'] }}">{{ $opt['value'] }}</option>
                                                @endforeach
                                              </select>
                                            </div>
                                          @endforeach
                                        </div>
                                        @if($errors->has("variants.$i.value_ids_by_attr"))
                                              <small class="text-danger d-block">
                                                {{ $errors->first("variants.$i.value_ids_by_attr") }}
                                              </small>
                                        @endif
                                      </div>
                                    </div>
                                  </div>
                            @endforeach
                          </div>
                    @endif

                    {{-- Status banner --}}
                    <div class="col-12">
                      <div class="alert alert-warning d-flex align-items-center mb-0">
                        <i class="fa-solid fa-shield-halved me-2"></i>
                        <div>
                          Any changes you save will set this product’s status to <strong>Pending</strong> for admin approval.
                        </div>
                      </div>
                    </div>
                  </div>
            @endif
          </div>

          <div class="modal-footer justify-content-between">
            <div>
              @if($step === 2)
                <button type="button" class="btn btn-outline-secondary" wire:click="prevStep">
                  <i class="fa-solid fa-arrow-left-long me-1"></i> Back
                </button>
              @endif
            </div>

            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>

              @if($step === 1)
                <button type="button" class="btn btn-primary"
                        wire:click="nextStep" wire:loading.attr="disabled">
                  <span wire:loading.remove wire:target="nextStep">
                    Continue <i class="fa-solid fa-arrow-right-long ms-1"></i>
                  </span>
                  <span wire:loading wire:target="nextStep">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Checking...
                  </span>
                </button>
              @else
                <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">
                  <span wire:loading.remove wire:target="save">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save
                  </span>
                  <span wire:loading wire:target="save">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...
                  </span>
                </button>
              @endif
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Delete Modal (single) --}}
  <div wire:ignore.self class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-danger">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Delete Product
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">Are you sure you want to delete this product? This action cannot be undone.</div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" wire:click="deleteConfirmed" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="deleteConfirmed">
              <i class="fa-solid fa-trash-can me-1"></i> Delete
            </span>
            <span wire:loading wire:target="deleteConfirmed">
              <i class="fa-solid fa-spinner fa-spin me-1"></i> Deleting...
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Bulk Modal --}}
  <div wire:ignore.self class="modal fade" id="bulkModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fa-solid fa-layer-group me-1"></i> Bulk Action
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @if($pendingBulkAction === 'activate')
            Activate selected products?
          @elseif($pendingBulkAction === 'deactivate')
            Deactivate selected products?
          @elseif($pendingBulkAction === 'delete')
            <span class="text-danger">Delete selected products? This cannot be undone.</span>
          @else
            Select an action.
          @endif
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" wire:click="runBulkAction" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="runBulkAction">Confirm</span>
            <span wire:loading wire:target="runBulkAction">
              <i class="fa-solid fa-spinner fa-spin me-1"></i> Working...
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>

</div>

@push('scripts')
    <script>
      // Sortable: native HTML5 drag & drop (no deps)
      function makeSortable(containerSelector, attr, callback) {
        const container = document.querySelector(containerSelector);
        if (!container) return;
        let dragEl = null;

        container.addEventListener('dragstart', (e) => {
          const target = e.target.closest('[draggable="true"]');
          if (!target) return;
          dragEl = target;
          e.dataTransfer.effectAllowed = 'move';
          target.classList.add('opacity-50');
        });

        container.addEventListener('dragend', (e) => {
          const target = e.target.closest('[draggable="true"]');
          if (target) target.classList.remove('opacity-50');
          dragEl = null;
          const order = Array.from(container.children).map(ch => ch.getAttribute(attr));
          callback(order.filter(v => v !== null));
        });

        container.addEventListener('dragover', (e) => {
          e.preventDefault();
          const target = e.target.closest('[draggable="true"]');
          if (!dragEl || !target || dragEl === target) return;
          const rect = target.getBoundingClientRect();
          const before = (e.clientX - rect.left) / rect.width < 0.5;
          container.insertBefore(dragEl, before ? target : target.nextSibling);
        });
      }

      // Modal events
      window.addEventListener('show-product-modal', () => {
        const el = document.getElementById('productModal');
        const modal = new bootstrap.Modal(el);
        modal.show();

        setTimeout(() => {
          makeSortable('#existing-images', 'data-key', (order) => {
            Livewire.find(@this.__instance.id).call('reorderExisting', order);
          });
          makeSortable('#new-images', 'data-idx', (order) => {
            Livewire.find(@this.__instance.id).call('reorderNew', order);
          });
        }, 150);
      });

      window.addEventListener('hide-product-modal', () => {
        const el = document.getElementById('productModal');
        const modal = bootstrap.Modal.getInstance(el);
        modal && modal.hide();
      });

      window.addEventListener('show-delete-modal', () => {
        const el = document.getElementById('deleteModal');
        const modal = new bootstrap.Modal(el);
        modal.show();
      });

      window.addEventListener('hide-delete-modal', () => {
        const el = document.getElementById('deleteModal');
        const modal = bootstrap.Modal.getInstance(el);
        modal && modal.hide();
      });

      window.addEventListener('show-bulk-modal', () => {
        const el = document.getElementById('bulkModal');
        const modal = new bootstrap.Modal(el);
        modal.show();
      });

      window.addEventListener('hide-bulk-modal', () => {
        const el = document.getElementById('bulkModal');
        const modal = bootstrap.Modal.getInstance(el);
        modal && modal.hide();
      });
    </script>
@endpush
