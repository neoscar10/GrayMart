<div>

  <div class="pt-4">
    <h2>Category Management</h2>
  </div>

    {{-- Search / New / Filters --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap pt-4 mb-3">
    
        <!-- Search input on the left -->
        <div class="input-group mb-2" style="width: 250px;">
            <input type="text" class="form-control" placeholder="Search categories..." wire:model.live="search">
        </div>
    
        <!-- Buttons and selects on the right -->
        <div class="d-flex gap-2 mb-2 flex-wrap">
            <button class="btn btn-primary flex-shrink-0" style="white-space: nowrap;" wire:click="openCreateModal">
                <i class="fa-solid fa-plus me-1"></i> New Category
            </button>
    
            <select class="form-select w-auto" wire:model.live="parentFilter">
                <option value="">All Parents</option>
                @foreach($allCategories as $p)
                    <option value="{{ $p->id }}">{{ $p->full_name }}</option>
                @endforeach
            </select>
    
            <select class="form-select w-auto" wire:model.live="showActive">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
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
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>Name</th>
                <th>Image</th>
                <th>Hierarchy</th>
                <th>Status</th>
                <th width="150">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $c)
                                                    @php
  // inherited inactive if parent chain includes inactive
  $inherited = false;
  $p = $c->parent;
  while ($p) {
    if (!$p->is_active) {
      $inherited = true;
      break;
    }
    $p = $p->parent;
  }
            @endphp
                <tr>
                    <td>{{ $c->name }}</td>

                    <td>
                        @if($c->image)
                            <img src="{{ asset('storage/' . $c->image) }}" class="img-thumbnail" style="max-height: 50px;">
                        @else
                            <small class="text-muted">No image</small>
                        @endif
                    </td>

                    <td>{{ $c->full_name }}</td>
                    <td>
                        <span class="badge bg-{{ $c->is_active ? 'success' : 'danger' }}">
                            {{ $c->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        {{-- @if($inherited)
                            <small class="text-muted ms-1">(via parent)</small>
                        @endif --}}
                    </td>
                    <td>
                        <button wire:click="openEditModal({{ $c->id }})" class="btn btn-sm btn-warning"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                        <button wire:click="confirmDelete({{ $c->id }})" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No categories</td>
                </tr>
            @endforelse
        </tbody>
        </table>
  
    <div class="my-2">
        {{ $categories->links() }}
    </div>
  
    {{-- Create/Edit Modal --}}
    <div wire:ignore.self class="modal fade" id="categoryModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <form wire:submit.prevent="{{ $selectedCategoryId ? 'updateCategory' : 'createCategory' }}">
            <div class="modal-header">
              <h5 class="modal-title">
                {{ $selectedCategoryId ? 'Edit' : 'New' }} Category
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              {{-- Name --}}
              <div class="mb-3">
                <label>Name</label>
                <input type="text" class="form-control" wire:model.defer="name">
                @error('name')<span class="text-danger">{{ $message }}</span>@enderror
              </div>
              {{-- Slug --}}
              <div class="mb-3">
                <label>Slug (optional)</label>
                <input type="text" class="form-control" wire:model.defer="slug">
                @error('slug')<span class="text-danger">{{ $message }}</span>@enderror
              </div>
            {{-- Image Upload --}}
            <div class="mb-3">
                <label>Image</label>
                <input type="file" class="form-control" wire:model="image">
                @error('image')<span class="text-danger">{{ $message }}</span>@enderror
            
                {{-- Loader when uploading --}}
                <div wire:loading wire:target="image" class="mt-2">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Uploading...</span>
                    </div>
                    <small class="text-primary ms-2">Uploading image...</small>
                </div>
            
                {{-- Preview new image --}}
                @if ($image)
                    <div class="mt-2">
                        <strong>Preview:</strong><br>
                        <img src="{{ $image->temporaryUrl() }}" class="img-thumbnail" style="max-height: 150px;">
                    </div>
                @elseif ($image_url)
                    <div class="mt-2">
                        <strong>Current:</strong><br>
                        <img src="{{ asset('storage/' . $image_url) }}" class="img-thumbnail" style="max-height: 150px;">
                    </div>
                @endif
            </div>


              {{-- Parent --}}
              <div class="mb-3">
                <label>Parent</label>
                <select class="form-select" wire:model.defer="parent_id">
                  <option value="">— None —</option>
                  @foreach($allCategories as $p)
                    <option value="{{ $p->id }}">{{ $p->full_name }}</option>
                  @endforeach
                </select>
                @error('parent_id')<span class="text-danger">{{ $message }}</span>@enderror
              </div>
              {{-- Active --}}
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="activeCheck"
                       wire:model.defer="is_active">
                <label class="form-check-label" for="activeCheck">Active</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary"
                      data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">
                {{ $selectedCategoryId ? 'Save Changes' : 'Create' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  
    {{-- Delete Confirmation --}}
    <div wire:ignore.self class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
    
                {{-- Header with icon --}}
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-trash-alt me-1"></i>
                        Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
    
                {{-- Body --}}
                <div class="modal-body text-center">
                    <p>Are you sure you want to delete this category?</p>
                </div>
    
                {{-- Footer with inline buttons --}}
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button wire:click="deleteCategory" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </div>
    
            </div>
        </div>
    </div>
      
  </div>
  

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Livewire.on('showCategoryModal', () => {
        new bootstrap.Modal('#categoryModal').show();
      });
      Livewire.on('hideCategoryModal', () => {
        bootstrap.Modal.getInstance('#categoryModal').hide();
      });
      Livewire.on('showDeleteModal', () => {
        new bootstrap.Modal('#deleteModal').show();
      });
      Livewire.on('hideDeleteModal', () => {
        bootstrap.Modal.getInstance('#deleteModal').hide();
      });
    });
  </script>
  
  