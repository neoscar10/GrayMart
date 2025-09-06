<?php

namespace App\Livewire\Front\Pages;

use Livewire\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Order;
use App\Models\VendorProfile;

class CheckoutCancel extends Component
{
    public ?string $token = null;
    public array $vendorCards = [];
    public float $grand = 0.0;

    public function mount(Request $request): void
    {
        $this->token = $request->query('token') ?? $request->query('order_id');

        if (!$this->token) {
            // Nothing to cancel; just show a generic message
            return;
        }

        $orders = Order::where('external_payment_id', $this->token)->get();

        if ($orders->isEmpty()) {
            return;
        }

        if (Auth::check()) {
            $orders = $orders->where('user_id', Auth::id());
        }

        // If still pending/unpaid, mark as cancelled
        foreach ($orders as $o) {
            if ($o->payment_status === 'unpaid' && $o->status === 'pending') {
                $o->status = 'cancelled';
                $o->save();
            }
        }

        // Build summary for display
        $this->grand = $orders->sum('total_amount');

        $vendorIds = $orders->pluck('vendor_id')->filter()->unique()->values();
        $profiles = VendorProfile::whereIn('user_id', $vendorIds)->get()->keyBy('user_id');

        $cards = [];
        foreach ($orders as $o) {
            $vp = $profiles->get($o->vendor_id);
            $cards[] = [
                'order_id'   => $o->id,
                'store_name' => $vp?->store_name ?? 'Store',
                'logo'       => $vp?->logo_url,
                'total'      => (float) $o->total_amount,
                'status'     => $o->status,
            ];
        }
        $this->vendorCards = $cards;
    }

    public function render()
    {
        return view('livewire.front.pages.checkout-cancel');
    }
}
