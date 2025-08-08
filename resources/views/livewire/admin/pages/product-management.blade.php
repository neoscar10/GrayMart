<div>
  <div class="pt-4">
    <h2>Products Management</h2>
  </div>

  {{-- Filters & Search --}}
  <div class="row mb-3 pt-4 gx-2 gy-2">
    <div class="col-md-3">
      <input type="text" class="form-control" placeholder="Search products..." wire:model.live="search">
    </div>
    <div class="col-md-2">
      <select class="form-select" wire:model.live="vendorFilter">
        <option value="">All Vendors</option>
        @foreach($vendors as $v)
      <option value="{{ $v->id }}">{{ $v->name }}</option>
    @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" wire:model.live="categoryFilter">
        <option value="">All Categories</option>
        @foreach($categories as $c)
      <option value="{{ $c->id }}">{{ $c->full_name }}</option>
    @endforeach
      </select>
    </div>
    <div class="col-md-2 d-flex align-items-center pt-3">
      <div class="form-check">
        <input class="form-check-input mt-0" type="checkbox" id="reservedOnlyCheck" wire:model.live="reservedOnly">
        <label class="form-check-label ms-1" for="reservedOnlyCheck">
          <i class="fa-solid fa-tag text-info"></i> Reserved
        </label>
      </div>
    </div>
    <div class="col-md-3">
      <select class="form-select" wire:model.live="statusFilter">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
    </div>
  </div>

  {{-- Flash --}}
  @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @push('styles')
    <style>
    .table {
      table-layout: auto !important;
    }

    .no-wrap {
      white-space: nowrap;
    }

    .thumb-img {
      width: 60px;
      height: 60px;
      object-fit: cover;
    }
    </style>
  @endpush

  {{-- Products Table --}}
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th class="no-wrap">Name</th>
          <th>Image</th>
          <th>Vendor</th>
          <th>Category</th>
          <th>Price</th>
          {{-- <th>Reserved</th> --}}
          <th>Status</th>
          <th>Active</th>
          <th class="no-wrap">Certificate</th>
          <th class="no-wrap">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($products as $p)
        <tr>
          <td class="no-wrap">
          @if($p->is_reserved)<i class="fa-solid fa-tag text-info me-1"></i>@endif
          @if($p->is_signed)<i class="fa-solid fa-certificate me-1 text-warning"></i>@endif
          {{ $p->name }}
          </td>
          <td>
          <img src="{{ $p->images[0] ?? asset('images/placeholder.png') }}" class="thumb-img rounded" alt="Thumb">
          </td>
          <td class="no-wrap">{{ $p->vendor->name }}</td>
          <td>{{ $p->category?->full_name ?: '—' }}</td>
          <td class="no-wrap">
          @if($p->variants_count > 0)
          @php
          // Each variant may have its own price; if null, fall back to product's base price
          $min = $p->variants->min(fn($v) => $v->price ?? $p->price);
          $max = $p->variants->max(fn($v) => $v->price ?? $p->price);
          @endphp

          @if($min === $max)
          ${{ number_format((float) $min, 2) }}
          @else
          ${{ number_format((float) $min, 2) }} – ${{ number_format((float) $max, 2) }}
          {{-- If you’d rather show “Varies” instead of the range, swap this line for:
          <span class="text-muted">Varies</span>
          --}}
          @endif
        @else
        ${{ number_format($p->price, 2) }}
        @endif
          </td>

          {{-- <td>@if($p->is_reserved)<i class="fa-solid fa-tag text-info"></i>@endif</td> --}}
          <td>
          <span class="badge bg-{{ 
          $p->status === 'approved' ? 'success'
      : ($p->status === 'rejected' ? 'danger' : 'warning') 
          }}">
          {{ ucfirst($p->status) }}
          </span>
          </td>
          <td>
          @if($p->is_active)
        <i class="fa-solid fa-check text-success"></i>
        @else
        <i class="fa-solid fa-x text-danger"></i>
        @endif
          </td>
          <td class="no-wrap">
          @php $cert = $p->certificates->last(); @endphp
          @if(!$cert) —
        @else
          <a href="{{ Storage::url($cert->file_path) }}" target="_blank" class="me-1">
          <i class="fa-solid fa-file-pdf text-primary"></i>
          </a>
          <span class="badge bg-{{ 
          $cert->status === 'pending' ? 'warning text-dark'
        : ($cert->status === 'approved' ? 'success' : 'danger')
          }}">
          {{ ucfirst($cert->status) }}
          </span>
        @endif
          </td>
          <td class="no-wrap">
          <button wire:click="openEditModal({{ $p->id }})" class="btn btn-sm btn-outline-primary me-1">
          <i class="fa-solid fa-eye"></i>
          </button>
          @if($p->status === 'pending')
        <button wire:click="approveProduct({{ $p->id }})" class="btn btn-sm btn-success me-1">
          <i class="fa-solid fa-thumbs-up"></i>
        </button>
        <button wire:click="openRejectModal({{ $p->id }})" class="btn btn-sm btn-danger">
          <i class="fa-solid fa-thumbs-down"></i>
        </button>
        @endif
          </td>
        </tr>
    @empty
      <tr>
        <td colspan="10" class="text-center">No products found.</td>
      </tr>
    @endforelse
      </tbody>
    </table>
  </div>

  {{ $products->links() }}

{{-- Edit Product Modal --}}
<div wire:ignore.self class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form wire:submit.prevent="updateProduct">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-box-open me-1"></i>Edit Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row gy-3">

            {{-- Name (read-only) --}}
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input type="text" class="form-control" value="{{ $name }}" readonly>
            </div>

            {{-- Slug (read-only) --}}
            <div class="col-md-6">
              <label class="form-label">Slug (optional)</label>
              <input type="text" class="form-control" value="{{ $slug }}" readonly>
            </div>

            {{-- Description (read-only) --}}
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" rows="3" readonly>{{ $description }}</textarea>
            </div>

            {{-- Price (editable as before) --}}
            <div class="col-md-4">
              <label class="form-label">Price</label>
              <input type="number" step="0.01" class="form-control" wire:model.defer="price">
              @error('price')<span class="text-danger">{{ $message }}</span>@enderror
            </div>

            {{-- Category (read-only display as disabled select) --}}
            <div class="col-md-4">
              <label class="form-label">Category</label>
              <select class="form-select" disabled>
                <option value="">— None —</option>
                @foreach($categories as $c)
          <option value="{{ $c->id }}" {{ (string) $c->id === (string) $category_id ? 'selected' : '' }}>
            {{ $c->full_name }}
          </option>
        @endforeach
              </select>
            </div>

            {{-- YouTube URL (read-only + Open button) --}}
            <div class="col-md-4">
              <label class="form-label">YouTube URL</label>
              <div class="input-group">
                <input type="url" class="form-control" value="{{ $video_url }}" readonly>
                @if($video_url)
          <a href="{{ $video_url }}" target="_blank" class="btn btn-outline-primary">
            <i class="fa-brands fa-youtube me-1"></i>Open
          </a>
        @endif
              </div>
              @error('video_url')<span class="text-danger">{{ $message }}</span>@enderror
            </div>

            {{-- Reserved (read-only) --}}
            <div class="col-md-4">
              <label class="form-label d-block">Reserved</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" {{ $is_reserved ? 'checked' : '' }} disabled
                  id="reservedCheck_ro">
                <label class="form-check-label" for="reservedCheck_ro">
                  <i class="fa-solid fa-tag me-1"></i> {{ $is_reserved ? 'Yes' : 'No' }}
                </label>
              </div>
            </div>

            {{-- Signed (read-only) --}}
            <div class="col-md-4">
              <label class="form-label d-block">Signed</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" {{ $is_signed ? 'checked' : '' }} disabled
                  id="signedCheck_ro">
                <label class="form-check-label" for="signedCheck_ro">
                  <i class="fa-solid fa-certificate me-1"></i> {{ $is_signed ? 'Yes' : 'No' }}
                </label>
              </div>
            </div>

            {{-- Active (editable as before) --}}
            <div class="col-md-4">
              <label class="form-label d-block">Active</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="activeCheck" wire:model.defer="is_active">
                <label class="form-check-label" for="activeCheck">
                  <i class="fa-solid fa-toggle-on me-1"></i> Active
                </label>
              </div>
            </div>

            {{-- Certificate Upload (show only if signed) --}}
            <div id="cert-upload-wrapper" class="col-8 {{ !$is_signed ? 'd-none' : '' }}">
              <label class="form-label">Upload Certificate (PDF)</label>

              @if($currentCertificate)
          <div class="mb-2">
          <a href="{{ \Illuminate\Support\Facades\Storage::url($currentCertificate->file_path) }}" target="_blank"
            class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-file-pdf me-1"></i>
            View Certificate ({{ ucfirst($currentCertificate->status) }})
          </a>
          </div>
        @endif

              <input type="file" wire:model="certificateFile" accept="application/pdf" class="form-control w-50">
              @error('certificateFile')<span class="text-danger">{{ $message }}</span>@enderror
            </div>

            {{-- Images (unchanged) --}}
            <div class="col-12">
              <label class="form-label">Images</label>

              <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach($images as $i => $url)
          <div class="position-relative">
            <img src="{{ $url }}" class="img-thumbnail" style="width:75px;height:75px;object-fit:cover;">
            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0"
            wire:click.prevent="removeExistingImage({{ $i }})">&times;</button>
          </div>
        @endforeach
              </div>

              <input type="file" multiple wire:model="newImages" class="form-control w-50">
              @error('newImages.*')<span class="text-danger">{{ $message }}</span>@enderror

              <div wire:loading wire:target="newImages" class="mt-2">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Uploading...</span>
                </div>
                <small class="text-primary ms-2">Uploading image...</small>
              </div>

              @if(!empty($newImages))
            <div class="mt-2">
            <strong>Preview:</strong>
            <div class="d-flex flex-wrap gap-2 mt-1">
              @foreach($newImages as $img)
          <img src="{{ $img->temporaryUrl() }}" class="img-thumbnail"
          style="max-height:150px;object-fit:cover;">
          @endforeach
            </div>
            </div>
        @endif
            </div>

            {{-- Variants (Read-Only) --}}
            <div class="col-12">
              <h5 class="mb-3">Variants</h5>

              @if(empty($variants) || count($variants) === 0)
          <p class="text-muted mb-0">This product has no variants.</p>
        @else
            <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
              <thead class="table-light">
              <tr>
                <th>SKU</th>
                <th class="text-end">Price</th>
                <th class="text-end">Stock</th>
                <th>Attributes</th>
              </tr>
              </thead>
              <tbody>
              @foreach($variants as $variant)
            <tr>
              <td class="align-middle">{{ $variant['sku'] ?? '—' }}</td>
              <td class="align-middle text-end">
              ${{ number_format((float) ($variant['price'] ?? 0), 2) }}
              </td>
              <td class="align-middle text-end">{{ $variant['stock'] ?? 0 }}</td>
              <td class="align-middle">
              @if(!empty($variant['values']))
            <ul class="list-inline mb-0">
              @foreach($variant['values'] as $val)
            <li class="list-inline-item badge bg-secondary">
            {{ $val['attribute_name'] ?? '—' }}: {{ $val['value'] ?? '—' }}
            </li>
            @endforeach
            </ul>
          @else
            <span class="text-muted">—</span>
          @endif
              </td>
            </tr>
          @endforeach
              </tbody>
            </table>
            </div>
        @endif
            </div>

          </div> {{-- /.row --}}
        </div> {{-- /.modal-body --}}

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-times"></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save me-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


  {{-- Reject Reason Modal --}}
  <div wire:ignore.self class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form wire:submit.prevent="rejectProductConfirmed">
          <div class="modal-header">
            <h5 class="modal-title text-danger">
              <i class="fa-solid fa-comment-slash me-1"></i> Reject Product
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <textarea class="form-control" rows="4" cols="100" wire:model.defer="rejectionReason"></textarea>
            @error('rejectionReason')<span class="text-danger">{{ $message }}</span>@enderror
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              <i class="fa-solid fa-times me-1"></i> Cancel
            </button>
            <button type="submit" class="btn btn-danger">
              <i class="fa-solid fa-paper-plane me-1"></i> Send Rejection
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Modal event listeners --}}

    <script>
    document.addEventListener('DOMContentLoaded', () => {
      Livewire.on('showProductModal', () => new bootstrap.Modal('#productModal').show());
      Livewire.on('hideProductModal', () => bootstrap.Modal.getInstance('#productModal').hide());
      Livewire.on('showRejectModal', () => new bootstrap.Modal('#rejectModal').show());
      Livewire.on('hideRejectModal', () => bootstrap.Modal.getInstance('#rejectModal').hide());
    });

    // toggle certificate field
    document.addEventListener('DOMContentLoaded', () => {
      const signedCheckbox = document.getElementById('signedCheck');
      const certWrapper = document.getElementById('cert-upload-wrapper');
      if (signedCheckbox && certWrapper) {
      function toggleCert() {
        certWrapper.classList.toggle('d-none', !signedCheckbox.checked);
      }
      signedCheckbox.addEventListener('change', toggleCert);
      Livewire.on('showProductModal', toggleCert);
      toggleCert();
      }
    });
    </script>
  

</div>