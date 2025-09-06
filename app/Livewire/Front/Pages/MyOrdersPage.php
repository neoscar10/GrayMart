<?php

namespace App\Livewire\Front\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class MyOrdersPage extends Component
{
    use WithPagination;

    public string $search   = '';
    public string $status   = '';
    public string $dateFrom = '';
    public string $dateTo   = '';

    public ?Order $selectedOrder = null;

    protected $queryString = [
        'search'   => ['except' => ''],
        'status'   => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo'   => ['except' => ''],
    ];

    public function updating($field): void
    {
        if (in_array($field, ['search', 'status', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function openOrder(int $orderId): void
    {
        $uid = Auth::id();
        $this->selectedOrder = Order::with(['items.product', 'items.vendor', 'customer'])
            ->where('user_id', $uid)
            ->findOrFail($orderId);

        $this->dispatch('showOrderModal');
    }

    public function closeOrder(): void
    {
        $this->selectedOrder = null;
        $this->dispatch('hideOrderModal');
    }

    private function currencySymbol(?string $code): string
    {
        return match (strtoupper($code ?? 'USD')) {
            'NGN'   => '₦',
            'USD'   => '$',
            'EUR'   => '€',
            'GBP'   => '£',
            default => strtoupper($code ?? 'USD') . ' ',
        };
    }

    public function render()
    {
        $uid = Auth::id();

        $query = Order::with(['items.vendor'])
            ->where('user_id', $uid)
            ->when($this->search, function ($q) {
                $s = trim($this->search);
                $q->where('id', $s)
                  ->orWhereHas('items.product', fn($qq) => $qq->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('items.vendor', fn($qq) => $qq->where('name', 'like', "%{$s}%"));
            })
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at');

        $orders = $query->paginate(10);

        // Precompute display helpers
        $display = $orders->getCollection()->map(function (Order $o) {
            $sym = $this->currencySymbol($o->currency);
            return [
                'order'          => $o,
                'symbol'         => $sym,
                'vendors_string' => $o->items->pluck('vendor.name')->filter()->unique()->implode(', ')
            ];
        });

        // Replace collection so Blade can use precomputed bits via same paginator
        $orders->setCollection($display);

        return view('livewire.front.pages.my-orders-page', [
            'orders' => $orders
        ]);
    }
}
