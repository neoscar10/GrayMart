<div class="container-fluid py-4">
  @if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Admin · Products</h4>

    <div class="d-flex flex-wrap gap-2 align-items-center">
      <input type="text" class="form-control" style="max-width:220px" placeholder="Search..."
        wire:model.live.debounce.400ms="search">

      <select class="form-select" style="max-width:200px" wire:model.live="vendorFilter">
        <option value="">All Vendors</option>
        @foreach($vendors as $v)
          <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->email }})</option>
        @endforeach
      </select>

      <select class="form-select" style="max-width:240px" wire:model.live="categoryFilter">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
          <option value="{{ $cat->id }}">{{ $cat->full_name }}</option>
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
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:64px;"></th>
            <th>Name</th>
            <th>Vendor</th>
            <th>Category</th>
            <th class="text-end">Price</th>
            <th>Variants</th>
            <th>Status</th>
            <th>Active</th>
            <th style="width:260px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($products as $p)
            @php
              $thumb = (is_array($p->images) && count($p->images)) ? asset('storage/' . $p->images[0]) : null;
            @endphp
            <tr>
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
                <div class="small text-muted">#{{ $p->id }}</div>
              </td>
              <td>{{ $p->vendor->name ?? '—' }}</td>
              <td>{{ $p->category->name ?? '—' }}</td>
              <td class="text-end">${{ number_format((float) ($p->price ?? 0), 2) }}</td>
              <td>{{ $p->variants_count }}</td>
              <td>
                <span
                  class="badge bg-{{ $p->status === 'approved' ? 'success' : ($p->status === 'rejected' ? 'danger' : 'warning') }}">
                  {{ ucfirst($p->status) }}
                </span>
              </td>
              <td>
                <span class="badge {{ $p->is_active ? 'bg-success' : 'bg-secondary' }}">
                  {{ $p->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="text-end">
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" wire:click="openEditModal({{ $p->id }})">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                  </button>

                  @if($p->status !== 'approved')
                    <button class="btn btn-sm btn-outline-success" wire:click="approveProduct({{ $p->id }})">
                      <i class="fa-solid fa-check"></i> Approve
                    </button>
                  @endif

                  @if($p->status !== 'rejected')
                    <button class="btn btn-sm btn-outline-danger" wire:click="openRejectModal({{ $p->id }})">
                      <i class="fa-solid fa-xmark"></i> Reject
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-5">No products found.</td>
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
      <div class="ms-auto">
        {{ $products->onEachSide(1)->links() }}
      </div>
    </div>
  </div>

  {{-- Edit Modal --}}
  <div wire:ignore.self class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-md-down">
      <div class="modal-content">
        <form wire:submit.prevent="updateProduct">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fa-solid fa-box-open me-1"></i> Edit Product
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-7">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" wire:model.live="name">
                @error('name') <small class="text-danger">{{ $message }}</small> @enderror>
              </div>
              <div class="col-md-5">
                <label class="form-label">Slug</label>
                <input type="text" class="form-control" wire:model.live="slug">
                @error('slug') <small class="text-danger">{{ $message }}</small> @enderror>
              </div>

              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" rows="3" wire:model.live="description"></textarea>
                @error('description') <small class="text-danger">{{ $message }}</small> @enderror>
              </div>

              <div class="col-md-4">
                <label class="form-label">Price ($)</label>
                <input type="number" step="0.01" class="form-control" wire:model.live="price">
                @error('price') <small class="text-danger">{{ $message }}</small> @enderror>
              </div>

              <div class="col-md-4">
                <label class="form-label">Category</label>
                <select class="form-select" wire:model.live="category_id">
                  <option value="">—</option>
                  @foreach($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->full_name }}</option>
                  @endforeach
                </select>
                @error('category_id') <small class="text-danger">{{ $message }}</small> @enderror>
              </div>

              <div class="col-md-4">
                <label class="form-label">YouTube URL</label>
                <input type="url" class="form-control" wire:model.live="video_url">
                @error('video_url') <small class="text-danger">{{ $message }}</small> @enderror>
              </div>

              <div class="col-12">
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label d-block">Reserved</label>
                    <input type="checkbox" class="form-check-input" wire:model.live="is_reserved">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label d-block">Signed</label>
                    <input type="checkbox" class="form-check-input" wire:model.live="is_signed">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label d-block">Active</label>
                    <input type="checkbox" class="form-check-input" wire:model.live="is_active">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model.live="status">
                      <option value="pending">Pending</option>
                      <option value="approved">Approved</option>
                      <option value="rejected">Rejected</option>
                    </select>
                    @error('status') <small class="text-danger">{{ $message }}</small> @enderror>
                  </div>
                </div>
              </div>

              {{-- Variants (read/write) --}}
              <div class="col-12">
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="mb-0">Variants</h6>
                  <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addVariant">
                    <i class="fa-solid fa-plus me-1"></i> Add Variant
                  </button>
                </div>

                @foreach($variants as $i => $v)
                  <div class="border rounded p-3 mb-2" wire:key="var-{{ $v['id'] ?? ('new-' . $i) }}">
                    <div class="row g-2">
                      <div class="col-md-3">
                        <label class="form-label">SKU</label>
                        <input type="text" class="form-control" wire:model.live="variants.{{ $i }}.sku">
                        @error("variants.$i.sku") <small class="text-danger">{{ $message }}</small> @enderror>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">Price ($)</label>
                        <input type="number" step="0.01" class="form-control" wire:model.live="variants.{{ $i }}.price">
                        @error("variants.$i.price") <small class="text-danger">{{ $message }}</small> @enderror>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">Stock</label>
                        <input type="number" class="form-control" wire:model.live="variants.{{ $i }}.stock">
                        @error("variants.$i.stock") <small class="text-danger">{{ $message }}</small> @enderror>
                      </div>
                      <div class="col-md-3 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-outline-danger" wire:click="removeVariant({{ $i }})">
                          <i class="fa-solid fa-trash-can me-1"></i> Remove
                        </button>
                      </div>
                      @if(!empty($v['values']))
                        <div class="col-12 small text-muted">
                          @foreach($v['values'] as $pair)
                            <span class="badge text-bg-light me-1 mb-1">{{ $pair['attribute_name'] }}:
                              {{ $pair['value'] }}</span>
                          @endforeach
                        </div>
                      @endif
                    </div>
                  </div>
                @endforeach
              </div>

              {{-- Rejection reason (only if rejecting) --}}
              @if($status === 'rejected')
                <div class="col-12">
                  <label class="form-label">Rejection Reason</label>
                  <textarea class="form-control" rows="3" wire:model.live="rejectionReason"></textarea>
                  @error('rejectionReason') <small class="text-danger">{{ $message }}</small> @enderror>
                </div>
              @endif

              {{-- Certificate upload (optional) --}}
              <div class="col-12">
                <label class="form-label">Attach Certificate (PDF)</label>
                <input type="file" class="form-control" wire:model="certificateFile" accept="application/pdf">
                @error('certificateFile') <small class="text-danger">{{ $message }}</small> @enderror>
                @if($currentCertificate)
                  <small class="text-muted d-block mt-1">
                    Current: <a href="{{ asset('storage/' . $currentCertificate->file_path) }}" target="_blank">View last
                      uploaded</a>
                  </small>
                @endif
              </div>

              <div class="col-12">
                <div class="alert alert-warning d-flex align-items-center mb-0">
                  <i class="fa-solid fa-shield-halved me-2"></i>
                  <div>Edits here directly change product records (admin override).</div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">
              <span wire:loading.remove wire:target="updateProduct">
                <i class="fa-solid fa-floppy-disk me-1"></i> Save
              </span>
              <span wire:loading wire:target="updateProduct">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...
              </span>
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>

  {{-- Reject Modal --}}
  <div wire:ignore.self class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Reject Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Reason</label>
          <textarea class="form-control" rows="4" wire:model.live="rejectionReason"></textarea>
          @error('rejectionReason') <small class="text-danger">{{ $message }}</small> @enderror>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" wire:click="rejectProductConfirmed" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="rejectProductConfirmed">Reject</span>
            <span wire:loading wire:target="rejectProductConfirmed">
              <i class="fa-solid fa-spinner fa-spin me-1"></i> Working...
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>


  <script>
    window.addEventListener('showProductModal', () => {
      const el = document.getElementById('productModal');
      new bootstrap.Modal(el).show();
    });
    window.addEventListener('hideProductModal', () => {
      const el = document.getElementById('productModal');
      const m = bootstrap.Modal.getInstance(el);
      m && m.hide();
    });
    window.addEventListener('showRejectModal', () => {
      const el = document.getElementById('rejectModal');
      new bootstrap.Modal(el).show();
    });
    window.addEventListener('hideRejectModal', () => {
      const el = document.getElementById('rejectModal');
      const m = bootstrap.Modal.getInstance(el);
      m && m.hide();
    });
  </script>
{{-- end --}}