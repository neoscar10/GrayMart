<?php

namespace App\Livewire\Admin\Pages;

use Livewire\Component;
use App\Models\Order;
use App\Models\User;
use DB;
use Carbon\Carbon;

class AnalyticsDashboard extends Component
{
    public array $revenueData = [];
    public array $revenueLabels = [];
    public array $salesSummary = [];
    public       $topProducts;
    public       $topVendors;
    public       $topCustomers;

    public function mount()
    {
        $this->prepareRevenueData();
        $this->prepareSalesSummary();
        $this->prepareTopProducts();
        $this->prepareTopVendors();
        $this->prepareTopCustomers();
    }

    protected function prepareRevenueData(): void
    {
        $labels = [];
        $data   = [];
        $now    = Carbon::now()->startOfMonth();

        for ($i = 11; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i);
            $labels[] = $m->format('M Y');
            $data[] = Order::where('status','delivered')
                ->whereYear('created_at',$m->year)
                ->whereMonth('created_at',$m->month)
                ->sum('total_amount');
        }

        $this->revenueLabels = $labels;
        $this->revenueData   = $data;
    }

    protected function prepareSalesSummary(): void
    {
        $this->salesSummary = [
            'totalRevenue'   => Order::where('status','delivered')->sum('total_amount'),
            'totalOrders'    => Order::count(),
            'totalCustomers' => User::where('role','customer')->count(),
            'totalVendors'   => User::where('role','vendor')->count(),
        ];
    }

   protected function prepareTopProducts(): void
{
    $this->topProducts = DB::table('order_items')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->join('products', 'order_items.product_id', '=', 'products.id')
        ->where('orders.status', 'delivered')
        ->select(
            'products.name',
            DB::raw('SUM(order_items.quantity) as qty_sold'),
            DB::raw('SUM(order_items.quantity * order_items.unit_price) as revenue')
            // Or SUM(order_items.total_price) if you store it
        )
        ->groupBy('products.name')
        ->orderByDesc('revenue')
        ->limit(5)
        ->get();
}


    protected function prepareTopVendors(): void
{
    $this->topVendors = DB::table('order_items')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->join('users', 'order_items.vendor_id', '=', 'users.id')
        ->where('orders.status', 'delivered')
        ->select(
            'users.name',
            DB::raw('SUM(order_items.quantity * order_items.unit_price) as revenue')
            // If you have order_items.total_price, prefer: SUM(order_items.total_price)
        )
        ->groupBy('users.name')
        ->orderByDesc('revenue')
        ->limit(5)
        ->get();
}


    protected function prepareTopCustomers(): void
    {
        $this->topCustomers = DB::table('orders')
            ->join('users','orders.user_id','=','users.id')
            ->where('orders.status','delivered')
            ->select(
                'users.name',
                DB::raw('SUM(orders.total_amount) as spent')
            )
            ->groupBy('users.name')
            ->orderByDesc('spent')
            ->limit(5)
            ->get();
    }

    public function exportRevenueCsv()
    {
        $filename = 'monthly_revenue_'.now()->format('Ymd_His').'.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        return response()->stream(function() {
            $h = fopen('php://output','w');
            fputcsv($h, ['Month','Revenue']);
            foreach (array_combine($this->revenueLabels, $this->revenueData) as $m => $r) {
                fputcsv($h, [$m, number_format($r,2)]);
            }
            fclose($h);
        }, 200, $headers);
    }

    public function exportTopProductsCsv()
    {
        $filename = 'top_products_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        return response()->stream(function() {
            $out = fopen('php://output','w');
            fputcsv($out, ['Product','Qty Sold','Revenue']);
            foreach ($this->topProducts as $row) {
                fputcsv($out, [
                    $row->name,
                    $row->qty_sold,
                    number_format($row->revenue,2),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function exportTopVendorsCsv()
    {
        $filename = 'top_vendors_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        return response()->stream(function() {
            $out = fopen('php://output','w');
            fputcsv($out, ['Vendor','Revenue']);
            foreach ($this->topVendors as $row) {
                fputcsv($out, [
                    $row->name,
                    number_format($row->revenue,2),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function exportTopCustomersCsv()
    {
        $filename = 'top_customers_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        return response()->stream(function() {
            $out = fopen('php://output','w');
            fputcsv($out, ['Customer','Spent']);
            foreach ($this->topCustomers as $row) {
                fputcsv($out, [
                    $row->name,
                    number_format($row->spent,2),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function render()
    {
        return view('livewire.admin.pages.analytics-dashboard')
            ->layout('components.layouts.admin');
    }
}
