<div>
    <div class="pt-4">
        <h2>User Management</h2>
    </div>
    <div class="d-flex justify-content-between mb-3 flex-wrap pt-4">
        <input type="text" class="form-control w-25 mb-2" placeholder="Search..." wire:model.live="search">

        <div class="d-flex gap-2">
            <select class="form-select w-auto" wire:model.live="role">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="vendor">Vendor</option>
                <option value="customer">Customer</option>
            </select>

            <select class="form-select w-auto" wire:model.live="status">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Blocked</option>
            </select>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Approved</th>
                <th>Status</th>
                <th width="120">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ ucfirst($user->role) }}</td>
                    <td>
                        <button wire:click="toggleApproval({{ $user->id }})"
                            class="btn btn-sm {{ $user->is_approved ? 'btn-success' : 'btn-warning' }}">
                            {{ $user->is_approved ? 'Approved' : 'Pending' }}
                        </button>
                    </td>
                    <td>
                        <button wire:click="toggleActive({{ $user->id }})"
                            class="btn btn-sm {{ $user->is_active ? 'btn-success' : 'btn-danger' }}">
                            {{ $user->is_active ? 'Active' : 'Blocked' }}
                        </button>
                    </td>
                    <td>
                        <button wire:click="openEditModal({{ $user->id }})" class="btn btn-sm btn-primary">
                            Edit
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No users found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $users->links() }}

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form wire:submit.prevent="updateUser">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" class="form-control" wire:model.defer="editName">
                            @error('editName') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label>Role</label>
                            <select class="form-select" wire:model.defer="editRole">
                                <option value="admin">Admin</option>
                                <option value="vendor">Vendor</option>
                                <option value="customer">Customer</option>
                            </select>
                            @error('editRole') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>