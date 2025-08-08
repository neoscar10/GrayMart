<div>
    <div class="p-4">
        <h2>Vendors</h2>
    </div>

    {{-- <div class="d-flex justify-content-between mb-3 flex-wrap pt-4">
        <input type="text" class="form-control w-25 mb-2" placeholder="Search..." wire:model.live="search">

        <div class="d-flex gap-2">
            
            <select class="form-select w-auto" wire:model.live="approval">
                <option value="">All Approval</option>
                <option value="1">Approved</option>
                <option value="0">Pending</option>
            </select>

            <select class="form-select w-auto" wire:model.live="status">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Blocked</option>
            </select>
        </div>
    </div> --}}

    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th style="width:56px">#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Approved</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vendors as $vendor)
                <tr>
                    <td>{{ $vendor->id }}</td>
                    <td class="fw-semibold">{{ $vendor->name }}</td>
                    <td>{{ $vendor->email }}</td>

                    <td>
                        <button wire:click="toggleApproval({{ $vendor->id }})"
                            class="btn btn-sm {{ $vendor->is_approved ? 'btn-success' : 'btn-warning' }}"
                            title="Toggle approval">
                            {{ $vendor->is_approved ? 'Approved' : 'Pending' }}
                        </button>
                    </td>

                    <td>
                        <button wire:click="toggleActive({{ $vendor->id }})"
                            class="btn btn-sm {{ $vendor->is_active ? 'btn-success' : 'btn-danger' }}"
                            title="Toggle account status">
                            {{ $vendor->is_active ? 'Active' : 'Blocked' }}
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        No vendors found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $vendors->links() }}
</div>