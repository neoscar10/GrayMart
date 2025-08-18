<div>
  <div class="pt-4">
    <h2>Sizes & Variants</h2>
  </div>
  <div class="row pt-4 gx-4">
    @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
    {{-- Left Pane: Attributes --}}
    <div class="col-md-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Variant Attributes</h5>
        <button class="btn btn-sm btn-primary" wire:click="openCreateAttributeModal">
          <i class="fas fa-plus me-1"></i>Add
        </button>
      </div>

      {{-- @if(session()->has('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif --}}

      <table class="table table-sm mb-3">
        <thead>
          <tr>
            <th>Name</th>
            <th width="100">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($attributes as $attr)
        <tr wire:click="selectAttribute({{ $attr->id }})"
        class="cursor-pointer {{ $attributeId === $attr->id ? 'table-primary' : '' }}">
        <td>{{ $attr->name }}</td>
        <td>
          <button class="btn btn-sm btn-warning me-1" wire:click.stop="openEditAttributeModal({{ $attr->id }})">
          <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-sm btn-danger" wire:click.stop="confirmDeleteAttribute({{ $attr->id }})">
          <i class="fas fa-trash"></i>
          </button>
        </td>
        </tr>
      @endforeach
        </tbody>
      </table>

      {{ $attributes->links() }}
    </div>

    {{-- Right Pane: Values --}}
    <div class="col-md-8">
      @if(!$attributeId)
      <div class="alert alert-info">
      Select an attribute to manage its values.
      </div>
    @else
      <div class="d-flex justify-content-between align-items-center mb-3">
      <h5>
        Values for: {{ \App\Models\VariantAttribute::find($attributeId)->name }}
      </h5>
      <button class="btn btn-sm btn-primary" wire:click="openCreateValueModal">
        <i class="fas fa-plus me-1"></i>Add Value
      </button>
      </div>

      <table class="table table-sm mb-3">
      <thead>
        <tr>
        <th>Value</th>
        <th width="100">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($values as $val)
      <tr>
      <td>{{ $val->value }}</td>
      <td>
        <button class="btn btn-sm btn-warning me-1" wire:click="openEditValueModal({{ $val->id }})">
        <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-sm btn-danger" wire:click="confirmDeleteValue({{ $val->id }})">
        <i class="fas fa-trash"></i>
        </button>
      </td>
      </tr>
      @empty
      <tr>
      <td colspan="2" class="text-center">No values found.</td>
      </tr>
      @endforelse
      </tbody>
      </table>
    @endif
    </div>
  </div>

  <!-- Attribute Modal -->
  <div wire:ignore.self class="modal fade" id="attributeModal" tabindex="-1">
    <div class="modal-dialog">
      <form wire:submit.prevent="saveAttribute" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ $attributeId ? 'Edit' : 'New' }} Attribute
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Name</label>
          <input type="text" wire:model.defer="attributeName" class="form-control">
          @error('attributeName') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>{{ $attributeId ? 'Update' : 'Save' }}
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Value Modal -->
  <div wire:ignore.self class="modal fade" id="valueModal" tabindex="-1">
    <div class="modal-dialog">
      <form wire:submit.prevent="saveValue" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ $valueId ? 'Edit' : 'New' }} Value
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Value</label>
          <input type="text" wire:model.defer="valueName" class="form-control">
          @error('valueName') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>{{ $valueId ? 'Update' : 'Save' }}
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Confirm Modal -->
  <div wire:ignore.self class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-danger">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">
            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete this {{ $confirmType }}?
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button wire:click="deleteConfirmed" class="btn btn-danger">
            <i class="fas fa-trash me-1"></i>Delete
          </button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
    window.addEventListener('show-modal', e => {
      new bootstrap.Modal(document.getElementById(e.detail)).show();
    });
    window.addEventListener('hide-modal', e => {
      bootstrap.Modal.getInstance(
      document.getElementById(e.detail)
      ).hide();
    });
    </script>
  @endpush
  
</div>