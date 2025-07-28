<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Arr;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->pluck('id')->toArray();
        $vendors   = User::where('role', 'vendor')->pluck('id')->toArray();
        $products  = Product::all();

        // Order::truncate();
        // OrderItem::truncate();

        // create 20 sample orders
        foreach (range(1, 20) as $i) {
            $customer = Arr::random($customers);
            $vendor   = Arr::random($vendors);

            // pick 1–3 random products for this vendor
            $orderProducts = $products
                ->where('vendor_id', $vendor)
                ->random(rand(1,3))
                ->values();

            // build shipping address
            $shipping = [
                'line1'      => '123 Example St.',
                'city'       => 'Sampleville',
                'state'      => 'CA',
                'postal_code'=> '90001',
                'country'    => 'USA',
            ];

            $order = Order::create([
                'user_id'          => $customer,
                'vendor_id'        => $vendor,
                'total_amount'     => $orderProducts->sum(fn($p)=> $p->price * rand(1,3)),
                'status'           => Arr::random(['pending','processing','shipped','delivered']),
                'shipping_address' => $shipping,
            ]);

            foreach ($orderProducts as $p) {
                $qty = rand(1, 3);
                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => $p->id,
                    'quantity'    => $qty,
                    'unit_price'  => $p->price,
                    'total_price' => $p->price * $qty,
                ]);
            }
        }
    }
}
