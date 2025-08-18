<div>

  {{-- PURE-PHP recursive renderer (no Blade directives inside). Adds stable keys + indentation. --}}
  <?php
$renderNode = function ($node, $level = 1) use (&$renderNode, $expanded, $childrenCache, $addingChildFor, $child_image, $errors) {
  $isExpanded = !empty($expanded[$node->id]);
  $childrenList = $childrenCache[$node->id] ?? null;

  if ($childrenList === null) {
    // Not loaded yet → just determine caret
    $hasChildren = \App\Models\Category::where('parent_id', $node->id)->exists();
    $childrenList = collect();
  } else {
    $hasChildren = $childrenList->count() > 0;
  }

  ob_start(); ?>
  <tr wire:key="row-<?= $node->id ?>">
    <td class="text-center">
      <button class="btn btn-link p-0 text-decoration-none" wire:click="toggleExpand(<?= $node->id ?>)"
        aria-label="Expand/Collapse">
        <?php  if ($isExpanded): ?>
        ▾
        <?php  else: ?>
        <?= $hasChildren ? '▸' : '<span class="text-muted">•</span>' ?>
        <?php  endif; ?>
      </button>
    </td>

    <td class="fw-semibold">
      <div style="padding-left: <?= $level * 20 ?>px;">
        <i class="fa-regular fa-folder-open me-1 text-secondary"></i>
        <?= e($node->name) ?>
      </div>
    </td>

    <td>
      <?php  if ($node->image): ?>
      <img src="<?= asset('storage/' . $node->image) ?>" class="rounded" style="width:34px;height:34px;object-fit:cover;">
      <?php  else: ?>
      <span class="text-muted">—</span>
      <?php  endif; ?>
    </td>

    <td>
      <span class="badge <?= $node->is_active ? 'bg-success' : 'bg-danger' ?>">
        <?= $node->is_active ? 'Active' : 'Inactive' ?>
      </span>
    </td>

    <td class="text-end">
      <div class="btn-group btn-group-sm">
        <button class="btn btn-outline-secondary" wire:click="startAddChild(<?= $node->id ?>)">
          <i class="fa-solid fa-folder-plus"></i> Subcategory
        </button>
        <button class="btn btn-outline-warning" wire:click="openEditModal(<?= $node->id ?>)">
          <i class="fa-solid fa-pen-to-square"></i> Edit
        </button>
        <button class="btn btn-outline-danger" wire:click="confirmDelete(<?= $node->id ?>)">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </td>
  </tr>

  <?php  if ($addingChildFor === $node->id): ?>
  <tr class="table-active" wire:key="add-<?= $node->id ?>">
    <td></td>
    <td colspan="4">
      <div style="padding-left: <?= ($level + 1) * 20 ?>px;">
        <div class="card border-0 shadow-sm">
          <div class="card-body py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
              <div class="fw-semibold">
                <i class="fa-solid fa-plus me-1"></i> Add Subcategory
              </div>
              <div>
                <button class="btn btn-sm btn-outline-secondary"
                  wire:click="$set('addingChildFor', null)">Cancel</button>
              </div>
            </div>

            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" wire:model.defer="child_name">
                <?php    if ($errors->has('child_name')): ?>
                <div class="text-danger small"><?= e($errors->first('child_name')) ?></div>
                <?php    endif; ?>
              </div>
              <div class="col-md-4">
                <label class="form-label">Slug (optional)</label>
                <input type="text" class="form-control" wire:model.defer="child_slug">
                <?php    if ($errors->has('child_slug')): ?>
                <div class="text-danger small"><?= e($errors->first('child_slug')) ?></div>
                <?php    endif; ?>
              </div>
              <div class="col-md-2 d-flex align-items-center">
                <div class="form-check mt-4">
                  <input class="form-check-input" type="checkbox" id="childActive<?= $node->id ?>"
                    wire:model.defer="child_is_active">
                  <label class="form-check-label" for="childActive<?= $node->id ?>">Active</label>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Image</label>
                <input type="file" class="form-control" wire:model="child_image">
                <?php    if ($errors->has('child_image')): ?>
                <div class="text-danger small"><?= e($errors->first('child_image')) ?></div>
                <?php    endif; ?>

                <div wire:loading wire:target="child_image" class="small text-primary mt-1">
                  <i class="fa-solid fa-spinner fa-spin me-1"></i>Uploading...
                </div>

                <?php    if ($child_image): ?>
                <div class="mt-2">
                  <img src="<?= $child_image->temporaryUrl() ?>" class="rounded border" style="height:64px;">
                </div>
                <?php    endif; ?>
              </div>
              <div class="col-md-6 d-flex align-items-end justify-content-end">
                <button class="btn btn-success" wire:click="createChild">
                  <i class="fa-solid fa-check me-1"></i> Add Subcategory
                </button>
              </div>
            </div>

          </div>
        </div>
      </div>
    </td>
  </tr>
  <?php  endif; ?>

  <?php  if ($isExpanded && $childrenList->count()): ?>
  <?php    foreach ($childrenList as $child): ?>
  <?= $renderNode($child, $level + 1) ?>
  <?php    endforeach; ?>
  <?php  endif; ?>

  <?php  return ob_get_clean();
};
  ?>

  <div class="pt-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h2 class="mb-0">Category Management</h2>
    <div class="d-flex align-items-center gap-2">
      <div class="input-group" style="width:260px;">
        <input type="text" class="form-control" placeholder="Search root categories..." wire:model.live="search">
      </div>
      <select class="form-select w-auto" wire:model.live="showActive">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>
      <button class="btn btn-primary" wire:click="openCreateModal">
        <i class="fa-solid fa-folder-plus me-1"></i> Add Root
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

  {{-- Tree Table --}}
  <div class="table-responsive mt-3">
    <table class="table table-sm align-middle table-hover">
      <thead class="table-light">
        <tr>
          <th style="width:48px;"></th>
          <th>Name</th>
          <th>Image</th>
          <th>Status</th>
          <th class="text-end" style="width:220px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($roots as $root)
        <tr wire:key="row-{{ $root->id }}">
          <td class="text-center">
          <button class="btn btn-link p-0 text-decoration-none" wire:click="toggleExpand({{ $root->id }})"
            aria-label="Expand/Collapse">
            @php
  $rootChildren = $childrenCache[$root->id] ?? null;
  $rootHasChildren = $rootChildren ? $rootChildren->count() > 0 : \App\Models\Category::where('parent_id', $root->id)->exists();
        @endphp
            @if(!empty($expanded[$root->id]))
          ▾
        @else
          {!! $rootHasChildren ? '▸' : '<span class="text-muted">•</span>' !!}
        @endif
          </button>
          </td>
          <td class="fw-semibold">
          <div style="padding-left: 0;">
            <i class="fa-solid fa-folder me-1 text-warning"></i>
            {{ $root->name }}
          </div>
          </td>
          <td>
          @if($root->image)
        <img src="{{ asset('storage/' . $root->image) }}" class="rounded"
          style="width:38px;height:38px;object-fit:cover;">
        @else
        <span class="text-muted">—</span>
        @endif
          </td>
          <td>
          <span class="badge {{ $root->is_active ? 'bg-success' : 'bg-danger' }}">
            {{ $root->is_active ? 'Active' : 'Inactive' }}
          </span>
          </td>
          <td class="text-end">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" wire:click="startAddChild({{ $root->id }})">
            <i class="fa-solid fa-folder-plus"></i> Subcategory
            </button>
            <button class="btn btn-outline-warning" wire:click="openEditModal({{ $root->id }})">
            <i class="fa-solid fa-pen-to-square"></i> Edit
            </button>
            <button class="btn btn-outline-danger" wire:click="confirmDelete({{ $root->id }})">
            <i class="fa-solid fa-trash"></i>
            </button>
          </div>
          </td>
        </tr>

        {{-- Inline add-child form for this root --}}
        @if($addingChildFor === $root->id)
        <tr class="table-active" wire:key="add-{{ $root->id }}">
        <td></td>
        <td colspan="4">
        <div style="padding-left: 20px;">
          <div class="card border-0 shadow-sm">
          <div class="card-body py-3">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
          <div class="fw-semibold">
            <i class="fa-solid fa-plus me-1"></i> Add Subcategory
          </div>
          <div>
            <button class="btn btn-sm btn-outline-secondary"
            wire:click="$set('addingChildFor', null)">Cancel</button>
          </div>
          </div>
          <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" wire:model.defer="child_name">
            @error('child_name')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Slug (optional)</label>
            <input type="text" class="form-control" wire:model.defer="child_slug">
            @error('child_slug')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-2 d-flex align-items-center">
            <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" id="childActiveRoot{{ $root->id }}"
            wire:model.defer="child_is_active">
            <label class="form-check-label" for="childActiveRoot{{ $root->id }}">Active</label>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Image</label>
            <input type="file" class="form-control" wire:model="child_image">
            @error('child_image')<div class="text-danger small">{{ $message }}</div>@enderror
            <div wire:loading wire:target="child_image" class="small text-primary mt-1">
            <i class="fa-solid fa-spinner fa-spin me-1"></i>Uploading...
            </div>
            @if ($child_image)
          <div class="mt-2">
          <img src="{{ $child_image->temporaryUrl() }}" class="rounded border" style="height:64px;">
          </div>
        @endif
          </div>
          <div class="col-md-6 d-flex align-items-end justify-content-end">
            <button class="btn btn-success" wire:click="createChild">
            <i class="fa-solid fa-check me-1"></i> Add Subcategory
            </button>
          </div>
          </div>
          </div>
          </div>
        </div>
        </td>
        </tr>
      @endif

        {{-- Children under this root (infinite depth via PHP recursion) --}}
        @if(!empty($expanded[$root->id]) && ($childrenCache[$root->id] ?? collect())->count())
        @foreach(($childrenCache[$root->id] ?? collect()) as $node)
        {!! $renderNode($node, 1) !!}
        @endforeach
      @endif

    @empty
      <tr>
        <td colspan="5" class="text-center text-muted py-4">No root categories yet.</td>
      </tr>
    @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-2">
    {{ $roots->links() }}
  </div>

  {{-- Create/Edit Modal --}}
  <div wire:ignore.self class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
      {{-- KEY THE CONTENT BLOCK so Livewire fully re-renders the fields --}}
      <div class="modal-content" wire:key="category-modal-{{ $modalKey }}">
        <form wire:submit.prevent="{{ $selectedCategoryId ? 'updateCategory' : 'createCategory' }}" autocomplete="off">
          {{-- avoid browser autofill confusion --}}
          <div class="modal-header">
            <h5 class="modal-title">
              {{ $selectedCategoryId ? 'Edit Category' : 'New Root Category' }}
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
              {{-- KEY THE FILE INPUT so temp state never bleeds between opens --}}
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
  
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="activeCheck" wire:model.defer="is_active">
              <label class="form-check-label" for="activeCheck">
                Active (cascades to descendants)
              </label>
            </div>
          </div>
  
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">
              {{ $selectedCategoryId ? 'Save Changes' : 'Create Root' }}
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
          <p>Delete this category (and its nested items)?</p>
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

</div>

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
    Livewire.on('showCategoryModal', () => new bootstrap.Modal('#categoryModal').show());
    Livewire.on('hideCategoryModal', () => bootstrap.Modal.getInstance('#categoryModal')?.hide());
    Livewire.on('showDeleteModal', () => new bootstrap.Modal('#deleteModal').show());
    Livewire.on('hideDeleteModal', () => bootstrap.Modal.getInstance('#deleteModal')?.hide());
    });
  </script>
@endpush