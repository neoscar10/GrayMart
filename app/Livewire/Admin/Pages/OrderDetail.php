<?php

namespace App\Livewire\Admin\Pages;

use Livewire\Component;
use App\Models\Order;

class OrderDetail extends Component
{
    public Order $order;
    public $orderId; // you can keep this if you still need the raw ID
    
    public $status;
    public $tracking_number;
    public $admin_notes;

    protected $rules = [
        'status'          => 'required|in:pending,processing,shipped,delivered,cancelled',
        'tracking_number' => 'nullable|string|max:255',
        'admin_notes'     => 'nullable|string|max:1000',
    ];

    public function mount(Order $order)
    {
        $this->order   = $order;
        $this->orderId = $order->id;

        $this->status          = $order->status;
        $this->tracking_number = $order->tracking_number;
        $this->admin_notes     = $order->admin_notes;
    }

    public function updateOrder()
    {
        $this->validate();

        $this->order->update([
            'status'          => $this->status,
            'tracking_number' => $this->tracking_number,
            'admin_notes'     => $this->admin_notes,
        ]);

        session()->flash('success','Order updated successfully.');
    }

    public function render()
    {
        return view('livewire.admin.pages.order-detail')
            ->layout('components.layouts.admin');
    }
}
