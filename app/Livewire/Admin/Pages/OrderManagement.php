<?php

namespace App\Livewire\Admin\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class OrderManagement extends Component
{
    use WithPagination;

    public $search         = '';
    public $vendorFilter   = '';
    public $customerFilter = '';
    public $statusFilter   = '';
    public $dateFrom       = '';
    public $dateTo         = '';

    public $selectedOrder;
    public $adminNote;

    protected $queryString = [
        'search','vendorFilter','customerFilter',
        'statusFilter','dateFrom','dateTo',
    ];

    public function updating($field)
    {
        if (in_array($field, [
            'search','vendorFilter','customerFilter',
            'statusFilter','dateFrom','dateTo',
        ])) {
            $this->resetPage();
        }
    }

    public function exportCsv()
    {
        $orders = Order::with(['customer','items.vendor']) // <-- vendor via items
            ->when($this->search, function ($q) {
                $q->where('id','like',"%{$this->search}%")
                  ->orWhereHas('customer', fn($qq)=> $qq->where('name','like',"%{$this->search}%"))
                  ->orWhereHas('items.vendor', fn($qq)=> $qq->where('name','like',"%{$this->search}%"));
            })
            ->when($this->vendorFilter, fn($q)=>
                $q->whereHas('items', fn($qq)=> $qq->where('vendor_id', $this->vendorFilter)) // <-- no orders.vendor_id
            )
            ->when($this->customerFilter, fn($q)=> $q->where('user_id', $this->customerFilter))
            ->when($this->statusFilter, fn($q)=> $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q)=> $q->whereDate('created_at','>=',Carbon::parse($this->dateFrom)))
            ->when($this->dateTo, fn($q)=> $q->whereDate('created_at','<=',Carbon::parse($this->dateTo)))
            ->orderByDesc('created_at')
            ->get();

        $filename = 'orders_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        return response()->stream(function() use ($orders) {
            $handle = fopen('php://output','w');
            fputcsv($handle, ['Order ID','Customer','Email','Vendor(s)','Total','Status','Placed At']);
            foreach ($orders as $o) {
                // if each order truly has one vendor, this will still return one name
                $vendors = $o->items->pluck('vendor.name')->filter()->unique()->implode(', ');

                fputcsv($handle, [
                    $o->id,
                    optional($o->customer)->name,
                    optional($o->customer)->email,
                    $vendors,
                    number_format($o->total_amount,2),
                    ucfirst($o->status),
                    $o->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    public function openOrderModal(int $orderId)
{
    $this->selectedOrder = Order::with([
            'customer',
            'items.product',
            'items.vendor',
            'vendors',              
        ])->findOrFail($orderId);

    $this->adminNote = $this->selectedOrder->admin_note;
    $this->dispatch('showOrderModal');
}


    public function saveAdminNote()
    {
        $this->validate([
            'adminNote' => 'nullable|string|max:1000',
        ]);

        $this->selectedOrder->update([
            'admin_note' => $this->adminNote,
        ]);

        $this->selectedOrder->refresh();
        $this->dispatch('hideOrderModal');
        session()->flash('success','Admin note saved.');
    }

    public function closeOrderModal()
    {
        $this->dispatch('hideOrderModal');
        $this->reset(['selectedOrder','adminNote']);
    }

    public function render()
    {
        $query = Order::with(['customer','items.vendor']) // <-- vendor via items
            ->when($this->search, function ($q) {
                $q->where('id','like',"%{$this->search}%")
                  ->orWhereHas('customer', fn($qq)=> $qq->where('name','like',"%{$this->search}%"))
                  ->orWhereHas('items.vendor', fn($qq)=> $qq->where('name','like',"%{$this->search}%")); // search vendor name too
            })
            ->when($this->vendorFilter, fn($q)=>
                $q->whereHas('items', fn($qq)=> $qq->where('vendor_id', $this->vendorFilter)) // <-- filter by item.vendor_id
            )
            ->when($this->customerFilter, fn($q)=> $q->where('user_id', $this->customerFilter))
            ->when($this->statusFilter, fn($q)=> $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q)=> $q->whereDate('created_at','>=',Carbon::parse($this->dateFrom)))
            ->when($this->dateTo, fn($q)=> $q->whereDate('created_at','<=',Carbon::parse($this->dateTo)))
            ->orderByDesc('created_at');

        return view('livewire.admin.pages.order-management', [
            'orders'    => $query->paginate(10),
            'vendors'   => User::where('role','vendor')->get(),
            'customers' => User::where('role','customer')->get(),
        ])->layout('components.layouts.admin');
    }
}
