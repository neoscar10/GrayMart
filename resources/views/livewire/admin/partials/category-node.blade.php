{{-- Single node row --}}
<tr>
    <td class="text-center">
        <button class="btn btn-link text-decoration-none expand-btn" wire:click="toggleExpand" aria-label="Expand/Collapse">
            @if($expanded)
                ▾
            @else
                {!! $hasChildren ? '▸' : '<span class="text-muted">•</span>' !!}
            @endif
        </button>

    </td>

    <td class="fw-semibold">
        <div style="padding-left: {{ $level * 20 }}px;">
            <i class="fa-regular fa-folder-open me-1 text-secondary"></i>
            {{ $node->name }}
        </div>
    </td>

    <td>
        @if($node->image)
            <img src="{{ asset('storage/' . $node->image) }}" class="rounded"
                style="width:34px;height:34px;object-fit:cover;">
        @else
            <span class="text-muted">—</span>
        @endif
    </td>

    <td>
        <span class="badge {{ $node->is_active ? 'bg-success' : 'bg-danger' }}">
            {{ $node->is_active ? 'Active' : 'Inactive' }}
        </span>
    </td>

    <td class="text-end">
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" wire:click="startAddChild">
                <i class="fa-solid fa-folder-plus"></i> Subcategory
            </button>
            <button class="btn btn-outline-warning" wire:click="askEdit">
                <i class="fa-solid fa-pen-to-square"></i> Edit
            </button>
            <button class="btn btn-outline-danger" wire:click="askDelete">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    </td>
</tr>

{{-- Inline add-child form --}}
@if($addingChildFor === $node->id)
    <tr class="table-active">
        <td></td>
        <td colspan="4">
            <div style="padding-left: {{ ($level + 1) * 20 }}px;">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div class="fw-semibold">
                                <i class="fa-solid fa-plus me-1"></i> Add Subcategory
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-secondary" wire:click="cancelAddChild">Cancel</button>
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
                                    <input class="form-check-input" type="checkbox" id="childActive{{ $node->id }}"
                                        wire:model.defer="child_is_active">
                                    <label class="form-check-label" for="childActive{{ $node->id }}">Active</label>
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
                                        <img src="{{ $child_image->temporaryUrl() }}" class="rounded border"
                                            style="height:64px;">
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

{{-- Recursive children --}}
@if($expanded)
    @foreach($children as $child)
        <livewire:admin.partials.category-node :node="$child" :level="$level + 1" :key="'node-' . $child->id" />
    @endforeach
@endif