<div>
    {{-- Tabs --}}
    <ul class="nav nav-tabs pt-4">
      @foreach(['certificates' => 'Certificates', 'upcoming' => 'Upcoming', 'live' => 'Live', 'closed' => 'Closed'] as $key => $label)
        <li class="nav-item">
          <a href="#"
             wire:click.prevent="setTab('{{ $key }}')"
             class="nav-link @if($tab === $key) active @endif">
            {{ $label }}
          </a>
        </li>
      @endforeach
    </ul>
  
    {{-- Search --}}
    <div class="mt-3 mb-2">
      <input type="text"
             class="form-control w-25"
             placeholder="Search by product…"
             wire:model.live="search">
    </div>
  
    {{-- Table --}}
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            @if($tab === 'certificates')
              <th>Product</th>
              <th>Vendor</th>
              <th>Uploaded At</th>
              <th>Status</th>
              <th>Actions</th>
            @else
              <th>Product</th>
              <th>Vendor</th>
              <th>Start</th>
              <th>End</th>
              <th>Status</th>
              <th>Actions</th>
            @endif
          </tr>
        </thead>
        <tbody>
          @forelse($items as $item)
          <tr>
          @if($tab === 'certificates')
          <td>{{ $item->product->name }}</td>
          <td>{{ $item->product->vendor->name }}</td>
          <td>{{ $item->created_at->format('Y-m-d H:i') }}</td>
          <td>
          <span class="badge bg-{{ 
          $item->status === 'approved' ? 'success'
        : ($item->status === 'rejected' ? 'danger' : 'warning') 
          }}">
          {{ ucfirst($item->status) }}
          </span>
          </td>
          <td>
          <a href="{{ Storage::url($item->file_path) }}" target="_blank"
           class="btn btn-sm btn-outline-secondary me-1">
          <i class="fa-solid fa-file-pdf"></i>
          </a>
          @if($item->status === 'pending')
          <button wire:click="openApproveCertificateModal({{ $item->id }})"
          class="btn btn-sm btn-success me-1">
          <i class="fa-solid fa-thumbs-up"></i> Approve
          </button>
          <button wire:click="openRejectCertificateModal({{ $item->id }})"
          class="btn btn-sm btn-danger">
          <i class="fa-solid fa-thumbs-down"></i> Reject
          </button>
          @endif

          @if($item->status === 'approved')
          <button wire:click="openRejectCertificateModal({{ $item->id }})" class="btn btn-sm btn-danger">
          <i class="fa-solid fa-ban"></i>
          </button>
          @endif

          @if($item->status === 'rejected')
        <button wire:click="openApproveCertificateModal({{ $item->id }})" class="btn btn-sm btn-success me-1">
        <i class="fa-solid fa-thumbs-up"></i> Approve
        </button>
        @endif
          </td>
      @else
          <td>{{ $item->product->name }}</td>
          <td>{{ $item->product->vendor->name }}</td>
          <td>{{ $item->starts_at->format('Y-m-d H:i') }}</td>
          <td>{{ $item->ends_at->format('Y-m-d H:i') }}</td>
          <td>
          <span class="badge bg-{{ 
          $item->status === 'live' ? 'info'
        : ($item->status === 'scheduled' ? 'secondary' : 'dark')
          }}">
          {{ ucfirst($item->status) }}
          </span>
          </td>
          <td>
          @if($tab === 'upcoming')
          <button wire:click="forceStartAuction({{ $item->id }})"
            class="btn btn-sm btn-primary">
          <i class="fa-solid fa-play"></i>
          </button>
          @elseif($tab === 'live')
          <button wire:click="openBidsModal({{ $item->id }})"
            class="btn btn-sm btn-outline-info me-1">
          <i class="fa-solid fa-list"></i>
          </button>
          <button wire:click="forceCloseAuction({{ $item->id }})"
            class="btn btn-sm btn-danger">
          <i class="fa-solid fa-stop"></i>
          </button>
          @endif
          </td>
          @endif
          </tr>
      @empty
            <tr>
              <td colspan="{{ $tab === 'certificates' ? 5 : 6 }}" class="text-center">
                No items found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
  
      {{ $items->links() }}
    </div>
  
{{-- Approve Certificate Confirmation Modal --}}
<div wire:ignore.self class="modal fade" id="approveCertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Confirm Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-4">Have you reviewed this certificate?</p>
            </div>
            <div class="modal-footer justify-content-center gap-2 border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button wire:click="approveCertificateConfirmed" class="btn btn-success">
                    <i class="fa-solid fa-thumbs-up"></i> Approve
                </button>
                <button wire:click="openRejectCertificateModal({{ $selectedCertificate->id ?? '0' }})"
                    class="btn btn-danger">
                    <i class="fa-solid fa-thumbs-down"></i> Reject
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Reject Certificate Modal --}}
<div wire:ignore.self class="modal fade" id="rejectCertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form wire:submit.prevent="rejectCertificateConfirmed">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger">
                        <i class="fa-solid fa-comment-slash me-1"></i> Reject Certificate
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" rows="4" cols="100" wire:model.defer="rejectionReason"></textarea>
                        @error('rejectionReason')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer justify-content-center gap-2 border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-ban me-1"></i> Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
  
  
    {{-- Bids Modal (unchanged) --}}
    <div wire:ignore.self class="modal fade" id="bidsModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Bid History</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            @php $bids = optional($selectedAuction)->bids ?? collect(); @endphp
  
            @if($bids->isEmpty())
              <p class="text-center text-muted">No bids have been placed yet.</p>
            @else
              <table class="table">
                <thead>
                  <tr><th>Bidder</th><th>Amount</th><th>Time</th></tr>
                </thead>
                <tbody>
                  @foreach($bids as $bid)
                    <tr>
                      <td>
                        {{ optional($selectedAuction)->bidder_anonymous
      ? 'Anonymous'
      : $bid->user->name }}
                      </td>
                      <td>${{ number_format($bid->amount, 2) }}</td>
                      <td>{{ $bid->created_at->format('H:i:s d-m-Y') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
  
  {{-- Modal event listeners --}}
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Livewire.on('showApproveCertModal', () => new bootstrap.Modal('#approveCertModal').show());
      Livewire.on('hideApproveCertModal', () => bootstrap.Modal.getInstance('#approveCertModal').hide());
      Livewire.on('showRejectCertModal', () => new bootstrap.Modal('#rejectCertModal').show());
      Livewire.on('hideRejectCertModal', () => bootstrap.Modal.getInstance('#rejectCertModal').hide());
      Livewire.on('showBidsModal',         () => new bootstrap.Modal('#bidsModal').show());
    });
  </script>
  