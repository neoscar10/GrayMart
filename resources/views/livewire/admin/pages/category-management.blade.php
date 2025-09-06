<div>

  {{-- Header / Breadcrumb --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-4">
    <div class="d-flex align-items-center flex-wrap gap-2">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item">
            <a href="javascript:void(0)" wire:click="goTo(null)" class="text-decoration-none">All Categories</a>
          </li>
          {{-- @foreach ($breadcrumb as $crumb)
            <li class="breadcrumb-item">
              <a href="javascript:void(0)" wire:click="goTo({{ $crumb['id'] }})" class="text-decoration-none">{{ $crumb['name'] }}</a>
            </li>
          @endforeach --}}
        </ol>
      </nav>

      <button class="btn btn-sm btn-outline-secondary ms-1" wire:click="goUp" @disabled(is_null($currentParentId)) title="Up one level">
        <i class="fa-solid fa-level-up-alt me-1"></i> Up
      </button>

      @if($currentParent)
        <span class="badge bg-dark text-dark ms-2">
          <i class="fa-regular fa-folder-open me-1 text-warning"></i>
          In: <strong>{{ $currentParent->name }}</strong>
        </span>
      @endif
    </div>

    <div class="d-flex align-items-center gap-2">
      <div class="input-group" style="width:260px;">
        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" class="form-control" placeholder="Search this folder..." wire:model.live="search">
      </div>

      <select class="form-select w-auto" wire:model.live="statusFilter" title="Status filter">
        <option value="">All</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>

      <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
          <i class="fa-solid fa-arrow-up-wide-short me-1"></i> Sort
        </button>
        <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:240px;">
          <div class="d-flex align-items-center gap-2">
            <select class="form-select" wire:model.live="sort">
              {{-- <option value="manual">Manual (order_column)</option> --}}
              <option value="name">Name</option>
              <option value="created">Created</option>
              {{-- <option value="status">Status</option> --}}
            </select>
            <select class="form-select w-auto" wire:model.live="direction">
              <option value="asc">Asc</option>
              <option value="desc">Desc</option>
            </select>
          </div>
        </div>
      </div>

      <button class="btn btn-primary" wire:click="openCreateModal">
        <i class="fa-solid fa-folder-plus me-1"></i> New Category
      </button>
    </div>
  </div>

  {{-- Flash --}}
  @if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show mt-3">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show mt-3">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Bulk actions toolbar (visible when something is selected) --}}
  @php $hasSelection = !!count(array_filter($selected)); @endphp
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 {{ $hasSelection ? '' : 'd-none' }}">
    <div class="text-muted small">
      <i class="fa-regular fa-square-check me-1"></i>
      {{ count(array_filter($selected)) }} selected
    </div>
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-outline-secondary btn-sm" wire:click="openMoveModal">
        <i class="fa-solid fa-arrows-turn-to-dots me-1"></i> Move
      </button>
      <button class="btn btn-outline-success btn-sm" wire:click="bulkSetActive(true)">
        <i class="fa-solid fa-check me-1"></i> Activate
      </button>
      <button class="btn btn-outline-warning btn-sm" wire:click="bulkSetActive(false)">
        <i class="fa-solid fa-ban me-1"></i> Deactivate
      </button>
      <button class="btn btn-outline-danger btn-sm" wire:click="bulkDelete">
        <i class="fa-solid fa-trash me-1"></i> Delete
      </button>
    </div>
  </div>

  {{-- CONTENT --}}

    {{-- LIST VIEW --}}
    <div class="table-responsive mt-2">
      <table class="table align-middle table-hover">
        <thead class="table-light">
          <tr>
            <th style="width:48px;">
              <input class="form-check-input" type="checkbox" wire:model.live="selectAll">
            </th>
            <th>Name</th>
            <th style="width:120px;">Items</th>
            <th style="width:120px;">Status</th>
            <th style="width:200px;">Updated</th>
            <th class="text-end" style="width:160px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($categories as $cat)
            <tr wire:key="row-{{ $cat->id }}">
              <td>
                <input class="form-check-input" type="checkbox" wire:model.live="selected.{{ $cat->id }}">
              </td>
              <td>
                <a href="javascript:void(0)" class="text-decoration-none" wire:click="enter({{ $cat->id }})" title="Open">
                  <i class="fa-solid fa-folder text-warning me-1"></i> {{ $cat->name }}
                </a>
              </td>
              <td>{{ $cat->children_count }}</td>
              <td>
                <span class="badge {{ $cat->is_active ? 'bg-success' : 'bg-danger' }}">
                  {{ $cat->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td>{{ optional($cat->updated_at)->format('Y-m-d H:i') }}</td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-secondary" wire:click="openEditModal({{ $cat->id }})">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </button>
                  <button class="btn btn-outline-danger" wire:click="confirmDelete({{ $cat->id }})">
                    <i class="fa-regular fa-trash-can"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">This folder is empty.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
 

  <div class="mt-3">
    {{ $categories->links() }}
  </div>

  {{-- Create/Edit Modal --}}
  <div wire:ignore.self class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content" wire:key="category-modal-{{ $modalKey }}">
        <form wire:submit.prevent="{{ $selectedCategoryId ? 'updateCategory' : 'createCategory' }}" autocomplete="off">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ $selectedCategoryId ? 'Edit Category' : 'New Category' }}
              @if(!is_null($currentParentId))
                <span class="badge bg-dark text-dark ms-2">
                  in <i class="fa-regular fa-folder-open mx-1 text-warning"></i>
                  {{ $currentParent?->name }}
                </span>
              @endif
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label">Name</label>
              <input type="text" class="form-control" wire:model.defer="name" autocomplete="off">
              @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-2">
              <label class="form-label">Slug (optional)</label>
              <input type="text" class="form-control" wire:model.defer="slug" autocomplete="off">
              @error('slug')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-2">
              <label class="form-label">Image</label>
              <input type="file" class="form-control" wire:model="image" wire:key="image-input-{{ $modalKey }}">
              @error('image')<div class="text-danger small">{{ $message }}</div>@enderror

              <div wire:loading wire:target="image" class="small text-primary mt-1">
                <i class="fa-solid fa-spinner fa-spin me-1"></i>Uploading...
              </div>

              @if ($image)
                <div class="mt-2">
                  <img src="{{ $image->temporaryUrl() }}" class="rounded border" style="height:80px;">
                </div>
              @elseif ($image_url)
                <div class="mt-2">
                  <img src="{{ asset('storage/' . $image_url) }}" class="rounded border" style="height:80px;">
                </div>
              @endif
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="activeCheck" wire:model.defer="is_active">
              <label class="form-check-label" for="activeCheck">Active</label>
            </div>

            @if($selectedCategoryId)
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="cascadeCheck" wire:model.defer="cascadeStatus">
                <label class="form-check-label" for="cascadeCheck">
                  Also apply this status to all descendants
                </label>
              </div>
            @endif
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">
              {{ $selectedCategoryId ? 'Save Changes' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Delete Confirmation --}}
  <div wire:ignore.self class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title text-danger">
            <i class="fas fa-trash-alt me-1"></i> Confirm Deletion
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <p>Delete this category and all its subfolders?</p>
        </div>
        <div class="modal-footer justify-content-center border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button wire:click="deleteCategory" class="btn btn-danger">
            <i class="fas fa-trash me-1"></i> Delete
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Move Modal --}}
  <div wire:ignore.self class="modal fade" id="moveModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fa-solid fa-arrows-turn-to-dots me-1"></i> Move Categories
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Destination folder</label>
            <div class="input-group mb-2">
              <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
              <input type="text" class="form-control" placeholder="Search folders..."
                     wire:model.live="moveSearch">
            </div>

            <div class="form-text mb-2">
              Choose a folder or leave empty to move to <strong>root</strong>.
            </div>

            <div class="border rounded overflow-auto" style="max-height: 220px;">
              <ul class="list-group list-group-flush">
                <li class="list-group-item">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="moveDest" id="moveRoot"
                           value="" wire:model="moveDestinationId">
                    <label class="form-check-label" for="moveRoot"><i class="fa-regular fa-hard-drive me-1"></i> Root (All Categories)</label>
                  </div>
                </li>

                @foreach($moveOptions as $opt)
                  <li class="list-group-item">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="moveDest" id="move{{ $opt['id'] }}"
                             value="{{ $opt['id'] }}" wire:model="moveDestinationId">
                      <label class="form-check-label" for="move{{ $opt['id'] }}">
                        <i class="fa-regular fa-folder me-1 text-warning"></i> {{ $opt['path'] }}
                      </label>
                    </div>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>

          <div class="small text-muted">
            Safeguards: You can’t move a folder into one of its own descendants.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" wire:click="moveSelected">Move</button>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Livewire.on('showCategoryModal', () => new bootstrap.Modal('#categoryModal').show());
      Livewire.on('hideCategoryModal', () => bootstrap.Modal.getInstance('#categoryModal')?.hide());

      Livewire.on('showDeleteModal', () => new bootstrap.Modal('#deleteModal').show());
      Livewire.on('hideDeleteModal', () => bootstrap.Modal.getInstance('#deleteModal')?.hide());

      Livewire.on('showMoveModal', () => new bootstrap.Modal('#moveModal').show());
      Livewire.on('hideMoveModal', () => bootstrap.Modal.getInstance('#moveModal')?.hide());
    });
  </script>
@endpush
