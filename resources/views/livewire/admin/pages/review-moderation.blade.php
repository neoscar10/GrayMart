{{-- resources/views/livewire/admin/pages/review-moderation.blade.php --}}
<div>
    {{-- Filters --}}
    <div class="row mb-3">
        <div class="col"><input class="form-control" wire:model.live="search" placeholder="Search comments…"></div>
        <div class="col-auto">
            <select class="form-select" wire:model.live="status">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        <div class="col-auto form-check">
            <input type="checkbox" id="rep" class="form-check-input" wire:model.live="reportedOnly">
            <label for="rep" class="form-check-label">Reported only</label>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table">
            <thead class="table-light">
                <tr>
                    <th>Reviewer</th>
                    <th>Target</th>
                    <th>★</th>
                    <th>Comment</th>
                    <th>Reported</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $r)
                            <tr>
                                <td>{{ $r->user->name }}</td>
                                <td>{{ class_basename($r->rateable_type) }} #{{ $r->rateable_id }}</td>
                                <td>{{ $r->rating }}</td>
                                <td title="{{ $r->comment }}">{{ Str::limit($r->comment, 30) }}</td>
                                <td>
                                    @if($r->reported)
                                        <span class="badge bg-danger">Yes</span>
                                        <button wire:click="openReportsModal({{ $r->id }})" class="btn btn-sm btn-outline-info ms-1"
                                            title="View report messages">
                                            View
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ 
                            $r->status === 'approved' ? 'success'
        : ($r->status === 'rejected' ? 'danger'
            : 'warning text-dark')
                          }}">
                                        {{ ucfirst($r->status) }}
                                    </span>
                                </td>
                                <td class="no-wrap">
                                    <button wire:click="approveReview({{ $r->id }})" class="btn btn-sm btn-success">✔</button>
                                    <button wire:click="openRejectModal({{ $r->id }})" class="btn btn-sm btn-danger">✖</button>
                                    <button wire:click="toggleVisibility({{ $r->id }})" class="btn btn-sm btn-outline-secondary">
                                        {{ $r->visible ? 'Hide' : 'Show' }}
                                    </button>
                                </td>
                            </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No reviews</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $reviews->links() }}
    </div>

    {{-- Reject Modal --}}
    <div wire:ignore.self class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form wire:submit.prevent="rejectConfirmed">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Reject Review</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <textarea class="form-control" rows="4" wire:model.defer="rejectionReason"
                            placeholder="Reason…"></textarea>
                        @error('rejectionReason')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button class="btn btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reports Modal --}}
    <div wire:ignore.self class="modal fade" id="reportsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Report Messages</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if(count($reportMessages) === 0)
                        <p class="text-center text-muted">No reports.</p>
                    @else
                        <ul class="list-group">
                            @foreach($reportMessages as $rep)
                                <li class="list-group-item">
                                    <strong>{{ $rep->reporter->name }}:</strong> {{ $rep->message }}
                                    <br>
                                    <small class="text-muted">{{ $rep->created_at->diffForHumans() }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    document.addEventListener('DOMContentLoaded', () => {
        Livewire.on('showRejectModal', () =>
            new bootstrap.Modal('#rejectModal').show()
        );
        Livewire.on('hideRejectModal', () =>
            bootstrap.Modal.getInstance('#rejectModal').hide()
        );
        Livewire.on('showReportsModal', () =>
            new bootstrap.Modal('#reportsModal').show()
        );
        Livewire.on('hideReportsModal', () =>
            bootstrap.Modal.getInstance('#reportsModal').hide()
        );
    });
</script>
