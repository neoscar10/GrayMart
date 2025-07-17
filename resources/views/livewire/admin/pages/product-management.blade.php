
<div>
    <div class="pt-4">
        <h2>Products Management</h2>
    </div>
    {{-- Filters & Search --}}
    <div class="row mb-3 pt-4 gx-2 gy-2">
        <div class="col-md-4">
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
                <input class="form-check-input mt-0" type="checkbox" id="auctionOnlyCheck" wire:model.live="auctionOnly">
                <label class="form-check-label ms-1" for="auctionOnlyCheck">
                    <i class="fa-solid fa-certificate text-warning"></i> Auction Only
                </label>
            </div>
        </div>          
        <div class="col-md-2">
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

    {{-- Table --}}
    @push('styles')
        <style>
            /* Let the table auto size columns instead of enforcing fixed layout */
            .table {
                table-layout: auto !important;
            }

            /* Prevent wrapping in name & actions columns */
            .no-wrap {
                white-space: nowrap;
            }
        </style>
    @endpush
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    
                    <th class="no-wrap">Name</th>
                    <th>Image</th>
                    <th>Vendor</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Active</th>
                    <th class="no-wrap">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                                                                <tr>
                                                                    <td class="no-wrap">
                                                                        @if($p->is_reserved)
                                                                            <i class="fa-solid fa-certificate text-warning me-1"></i>
                                                                        @endif
                                                                        {{ $p->name }}
                                                                    </td>
                                                                    <td>
                                                                        <img src="{{ $p->images[0] ?? asset('images/placeholder.png') }}" class="thumb-img rounded"
                                                                            alt="Thumb">
                                                                    </td>
                                                                    <td class="no-wrap">{{ $p->vendor->name }}</td>
                                                                    <td>{{ $p->category?->full_name ?: '—' }}</td>
                                                                    <td>${{ number_format($p->price, 2) }}</td>
                                                                    <td>
                                                                        <span class="badge bg-{{ 
                                                                    $p->status == 'approved' ? 'success'
                    : ($p->status == 'rejected' ? 'danger' : 'warning')
                                                                  }}">
                                                                            {{ ucfirst($p->status) }}
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <i class="fa{{ $p->is_active ? 's' : 'r' }} 
                                                                             fa-toggle-{{ $p->is_active ? 'on' : 'off' }} fa-lg"></i>
                                                                    </td>
                                                                    <td class="no-wrap">
                                                                        <button wire:click="openEditModal({{ $p->id }})" class="btn btn-sm btn-outline-primary me-1">
                                                                            <i class="fa-solid fa-eye"></i>
                                                                        </button>
                                                                        @if($p->status == 'pending')
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
                        <td colspan="8" class="text-center">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    
    <div class="py-2">
        {{ $products->links() }}
    </div>
    
    {{-- Edit Modal --}}
    <div wire:ignore.self class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form wire:submit.prevent="updateProduct">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-box-open me-1"></i>
                            Edit Product
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
    
                    <div class="modal-body">
                        {{-- Images Management --}}
                        
    
                        {{-- Other Fields --}}
                        <div class="row gy-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" wire:model.defer="name" readonly>
                                @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
    
                            <div class="col-md-6">
                                <label class="form-label">Slug (optional)</label>
                                <input type="text" class="form-control" wire:model.defer="slug" readonly>
                                @error('slug')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
    
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" rows="3" wire:model.defer="description" readonly></textarea>
                            </div>

                            
    
                            <div class="col-md-4">
                                <label class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control" wire:model.defer="price" readonly>
                                @error('price')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
    
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select class="form-select" wire:model.defer="category_id" disabled>
                                    <option value="">— None —</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
    
                            <div class="col-md-4">
                                <label class="form-label" readonly>YouTube URL</label>
                                <input type="url" class="form-control" wire:model.defer="video_url" readonly>
                                @error('video_url')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
    
                            <div class="col-md-4 form-check">
                                <input class="form-check-input" type="checkbox" id="reservedCheck"
                                    wire:model.defer="is_reserved" disabled>
                                <label class="form-check-label" for="reservedCheck">
                                    <i class="fa-solid fa-certificate me-1"></i> Reserved
                                </label>
                            </div>
    
                            <div class="col-md-4 form-check">
                                <input class="form-check-input" type="checkbox" id="activeCheck"
                                    wire:model.defer="is_active" disabled>
                                <label class="form-check-label" for="activeCheck">
                                    <i class="fa-solid fa-toggle-on me-1"></i> Active
                                </label>
                            </div>
    
                            <div class="col-md-4">
                                <label class="form-label">Status (Changeable) </label>
                                <select class="form-select" wire:model.defer="status">
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            {{-- images --}}
                            <div class="mb-4">
                                <label class="form-label">Images (to be changed disabled)</label>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    @foreach($images as $i => $url)
                                        <div class="position-relative">
                                            <img src="{{ $url }}" class="img-thumbnail" style="width:75px; height:75px; object-fit:cover;">
                                            
                                        </div>
                                    @endforeach
                                </div>
                                <input type="file" multiple wire:model="newImages" class="form-control w-50" readonly>
                                @error('newImages.*')<span class="text-danger">{{ $message }}</span>@enderror
                                {{-- Loader when uploading --}}
                                <div wire:loading wire:target="newImages" class="mt-2">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Uploading...</span>
                                    </div>
                                    <small class="text-primary ms-2">Uploading image...</small>
                                </div>
                            
                                {{-- Preview new image --}}
                                @if(!empty($newImages))
                                    <div class="mt-2">
                                        <strong>Preview:</strong><br>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($newImages as $img)
                                                <img src="{{ $img->temporaryUrl() }}" class="img-thumbnail" style="max-height:150px; object-fit:cover;">
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            
                                </div>
                        </div>
                    </div>
    
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
                        <div class="mb-3">
                            <label>Reason for Rejection</label>
                            <textarea class="form-control" rows="6" cols="100" wire:model.defer="rejectionReason"></textarea>
                            @error('rejectionReason')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
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
    
    {{-- Modal JS --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Livewire.on('showProductModal', () => {
                new bootstrap.Modal('#productModal').show();
            });
            Livewire.on('hideProductModal', () => {
                bootstrap.Modal.getInstance('#productModal').hide();
            });

            Livewire.on('showRejectModal', () => {
                new bootstrap.Modal('#rejectModal').show();
            });
            Livewire.on('hideRejectModal', () => {
                bootstrap.Modal.getInstance('#rejectModal').hide();
            });
        });
    </script>
    </div>