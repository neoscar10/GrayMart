<?php

namespace App\Livewire\Vendor\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorOrderManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $perPage = 10;

    public ?Order $selectedOrder = null;
    public ?string $selectedStatus = null;

    protected array $allowedStatuses = ['pending','processing','shipped','delivered','cancelled'];

    protected $queryString = [
        'search','statusFilter','dateFrom','dateTo'
    ];

    public function updating($field): void
    {
        if (in_array($field, ['search','statusFilter','dateFrom','dateTo'])) {
            $this->resetPage();
        }
    }

    /** List orders that belong to this vendor (order.vendor_id) OR that contain this vendor's items. */
    private function baseQuery()
    {
        $vendorId = (int) Auth::id();

        return Order::with(['customer','items.product'])
            ->where(function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId)
                  ->orWhereHas('items', fn($qq) => $qq->where('vendor_id', $vendorId));
            });
    }

    /** Quick inline status change from the table row. */
    public function changeStatus(int $orderId, string $newStatus): void
    {
        $newStatus = strtolower(trim($newStatus));
        if (!in_array($newStatus, $this->allowedStatuses, true)) {
            session()->flash('error', 'Invalid status.');
            return;
        }

        $order = $this->baseQuery()->find($orderId);
        if (!$order) {
            session()->flash('error', 'Order not found or not authorized.');
            return;
        }

        $order->update(['status' => $newStatus]);
        session()->flash('success', "Order #{$order->id} status updated to {$newStatus}.");
    }

    /** Open detail modal (with address + items) */
    public function openOrderModal(int $orderId): void
    {
        $order = $this->baseQuery()->with(['items.vendor'])->findOrFail($orderId);
        $this->selectedOrder = $order;
        $this->selectedStatus = $order->status;
        $this->dispatch('showOrderModal');
    }

    public function saveStatusFromModal(): void
    {
        if (!$this->selectedOrder) return;

        $newStatus = (string) $this->selectedStatus;
        if (!in_array($newStatus, $this->allowedStatuses, true)) {
            session()->flash('error', 'Invalid status.');
            return;
        }

        // Re-authorize order for safety
        $order = $this->baseQuery()->find($this->selectedOrder->id);
        if (!$order) {
            session()->flash('error', 'Order not found or not authorized.');
            return;
        }

        $order->update(['status' => $newStatus]);
        $this->selectedOrder->refresh();
        $this->dispatch('hideOrderModal');
        session()->flash('success', "Order #{$order->id} status updated.");
    }

    public function closeOrderModal(): void
    {
        $this->dispatch('hideOrderModal');
        $this->reset(['selectedOrder','selectedStatus']);
    }

    /** CSV export of this vendor's filtered orders */
    public function exportCsv()
    {
        $vendorId = (int) Auth::id();

        $orders = $this->baseQuery()
            ->when($this->search, function ($q) {
                $s = trim($this->search);
                $q->where('id', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($qq) => $qq->where('name', 'like', "%{$s}%")
                                                         ->orWhere('email', 'like', "%{$s}%"));
            })
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', Carbon::parse($this->dateFrom)))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', Carbon::parse($this->dateTo)))
            ->orderByDesc('created_at')
            ->get();

        $filename = 'vendor_orders_'.$vendorId.'_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        return response()->stream(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order ID','Customer','Email','Total','Payment','Status','Placed At']);

            foreach ($orders as $o) {
                fputcsv($out, [
                    $o->id,
                    optional($o->customer)->name,
                    optional($o->customer)->email,
                    number_format((float) $o->total_amount, 2),
                    ucfirst($o->payment_status ?? 'unpaid'),
                    ucfirst($o->status ?? 'pending'),
                    $o->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function render()
    {
        $query = $this->baseQuery()
            ->when($this->search, function ($q) {
                $s = trim($this->search);
                $q->where('id', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($qq) => $qq->where('name', 'like', "%{$s}%")
                                                         ->orWhere('email', 'like', "%{$s}%"));
            })
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', Carbon::parse($this->dateFrom)))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', Carbon::parse($this->dateTo)))
            ->orderByDesc('created_at');

        return view('livewire.vendor.pages.vendor-order-management', [
            'orders' => $query->paginate($this->perPage),
            // Optional: customers listing if you want a dropdown later
            'customers' => User::where('role','customer')->get(['id','name']),
        ])->layout('components.layouts.vendor'); // adjust layout if different
    }
}
