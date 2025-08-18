<?php

namespace App\Livewire\Vendor\Pages;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsDashboard extends Component
{
    // Filters
    public string $range = 'last_30'; // last_7|last_30|last_90|ytd|last_12_months|custom
    public ?string $fromDate = null;  // Y-m-d
    public ?string $toDate   = null;  // Y-m-d

    // KPI data
    public array $kpis = [
        'revenue'         => 0.0,
        'revenue_delta'   => 0.0,
        'sales'           => 0,   // items sold
        'sales_delta'     => 0.0,
        'orders'          => 0,
        'active_products' => 0,
        'active_auctions' => 0,
        'bids'            => 0,
        'rating_avg'      => 0.0,
        'rating_count'    => 0,
    ];

    // Chart
    public array $revenueLabels = [];
    public array $revenueData   = [];

    // Tables
    public $topProducts;
    public $topCustomers;

    public function mount(): void
    {
        // Default dates from range
        [$from, $to] = $this->computePeriod($this->range, $this->fromDate, $this->toDate);
        $this->fromDate = $from->toDateString();
        $this->toDate   = $to->toDateString();

        $this->refreshAnalytics();
    }

    public function updatedRange(): void
    {
        // Recompute period if user changed preset range
        [$from, $to] = $this->computePeriod($this->range, $this->fromDate, $this->toDate);
        $this->fromDate = $from->toDateString();
        $this->toDate   = $to->toDateString();

        $this->refreshAnalytics();
    }

    public function applyCustom(): void
    {
        $this->validate([
            'fromDate' => ['required','date'],
            'toDate'   => ['required','date','after_or_equal:fromDate'],
        ]);

        $this->range = 'custom';
        $this->refreshAnalytics();
    }

    /** ========= Core ========== */

    protected function refreshAnalytics(): void
    {
        $vendorId = (int) auth()->id();
        $from = Carbon::parse($this->fromDate)->startOfDay();
        $to   = Carbon::parse($this->toDate)->endOfDay();

        // Period length (days) to compute deltas
        $days = $from->diffInDays($to) + 1;
        $prevTo   = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1);

        // KPIs
        [$revenue, $orders, $itemsSold] = $this->queryVendorRevenueOrdersItems($vendorId, $from, $to);
        [$prevRevenue, $prevOrders, $prevItemsSold] = $this->queryVendorRevenueOrdersItems($vendorId, $prevFrom, $prevTo);

        $this->kpis['revenue']       = (float) $revenue;
        $this->kpis['revenue_delta'] = $this->delta($prevRevenue, $revenue);
        $this->kpis['sales']         = (int) $itemsSold;
        $this->kpis['sales_delta']   = $this->delta($prevItemsSold, $itemsSold);
        $this->kpis['orders']        = (int) $orders;

        $this->kpis['active_products'] = (int) DB::table('products')
            ->where('vendor_id', $vendorId)->where('is_active', true)->count();

        $this->kpis['active_auctions'] = (int) DB::table('auctions')
            ->where('vendor_id', $vendorId)->where('status','active')->count();

        $this->kpis['bids'] = (int) DB::table('bids as b')
            ->join('auctions as a', 'a.id', '=', 'b.auction_id')
            ->where('a.vendor_id', $vendorId)
            ->whereBetween('b.created_at', [$from, $to])
            ->count('b.id');

        // Ratings (for this vendor's products)
        $rating = DB::table('reviews as r')
            ->join('products as p', function($j) {
                $j->on('p.id', '=', 'r.rateable_id')->where('r.rateable_type', '=', \App\Models\Product::class);
            })
            ->where('p.vendor_id', $vendorId)
            ->where(function($q){
                // If you want only visible/approved, uncomment:
                // $q->where('r.visible', true)->where('r.status','approved');
            })
            ->selectRaw('COALESCE(AVG(r.rating),0) as avg_rating, COUNT(r.id) as cnt')
            ->first();

        $this->kpis['rating_avg']   = round((float) ($rating->avg_rating ?? 0), 2);
        $this->kpis['rating_count'] = (int) ($rating->cnt ?? 0);

        // Chart (monthly across selected period)
        [$labels, $series] = $this->buildMonthlyRevenueSeries($vendorId, $from, $to);
        $this->revenueLabels = $labels;
        $this->revenueData   = $series;

        // Tables
        $this->prepareTopProducts($vendorId, $from, $to);
        $this->prepareTopCustomers($vendorId, $from, $to);

        // Notify frontend to update chart without full reload
        $this->dispatch('vendor-analytics-updated', [
            'labels' => $this->revenueLabels,
            'series' => $this->revenueData,
        ]);
    }

    protected function queryVendorRevenueOrdersItems(int $vendorId, Carbon $from, Carbon $to): array
    {
        $base = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('oi.vendor_id', $vendorId)
            ->where('o.status', 'delivered')
            ->whereBetween('o.created_at', [$from, $to]);

        $revenue = (float) (clone $base)->sum('oi.total_price');
        $orders  = (int) (clone $base)->distinct('oi.order_id')->count('oi.order_id');
        $items   = (int) (clone $base)->sum('oi.quantity');

        return [$revenue, $orders, $items];
    }

    protected function buildMonthlyRevenueSeries(int $vendorId, Carbon $from, Carbon $to): array
    {
        // Build list of months between $from and $to inclusive
        $cursor = $from->copy()->startOfMonth();
        $end    = $to->copy()->startOfMonth();

        $labels = [];
        $values = [];

        while ($cursor <= $end) {
            $label = $cursor->format('M Y');
            $labels[] = $label;

            $sum = DB::table('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->where('oi.vendor_id', $vendorId)
                ->where('o.status', 'delivered')
                ->whereYear('o.created_at', $cursor->year)
                ->whereMonth('o.created_at', $cursor->month)
                ->sum('oi.total_price');

            $values[] = (float) $sum;
            $cursor->addMonth();
        }

        return [$labels, $values];
    }

    protected function prepareTopProducts(int $vendorId, Carbon $from, Carbon $to): void
    {
        $this->topProducts = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->where('oi.vendor_id', $vendorId)
            ->where('o.status', 'delivered')
            ->whereBetween('o.created_at', [$from, $to])
            ->select(
                'p.name',
                DB::raw('SUM(oi.quantity) as qty_sold'),
                DB::raw('SUM(oi.total_price) as revenue')
            )
            ->groupBy('p.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();
    }

    protected function prepareTopCustomers(int $vendorId, Carbon $from, Carbon $to): void
    {
        $this->topCustomers = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->where('oi.vendor_id', $vendorId)
            ->where('o.status', 'delivered')
            ->whereBetween('o.created_at', [$from, $to])
            ->select(
                'u.name',
                DB::raw('SUM(oi.total_price) as spent')
            )
            ->groupBy('u.name')
            ->orderByDesc('spent')
            ->limit(5)
            ->get();
    }

    /** ========= Helpers ========== */

    protected function computePeriod(string $range, ?string $from, ?string $to): array
    {
        $today = Carbon::today();

        return match ($range) {
            'last_7'  => [ $today->copy()->subDays(6), $today ],
            'last_30' => [ $today->copy()->subDays(29), $today ],
            'last_90' => [ $today->copy()->subDays(89), $today ],
            'ytd'     => [ Carbon::create($today->year, 1, 1), $today ],
            'last_12_months' => [ $today->copy()->startOfMonth()->subMonths(11), $today ],
            'custom'  => [ Carbon::parse($from ?? $today->toDateString()), Carbon::parse($to ?? $today->toDateString()) ],
            default   => [ $today->copy()->subDays(29), $today ],
        };
    }

    protected function delta(float|int $prev, float|int $curr): float
    {
        if ((float)$prev == 0.0) {
            return $curr > 0 ? 100.0 : 0.0;
        }
        return round((($curr - $prev) / $prev) * 100, 2);
    }

    /** ========= Exports ========= */

    public function exportRevenueCsv()
    {
        $filename = 'vendor_monthly_revenue_'.now()->format('Ymd_His').'.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        return response()->stream(function() {
            $h = fopen('php://output','w');
            fputcsv($h, ['Month','Revenue']);
            foreach (array_combine($this->revenueLabels, $this->revenueData) as $m => $r) {
                fputcsv($h, [$m, number_format((float)$r, 2, '.', '')]);
            }
            fclose($h);
        }, 200, $headers);
    }

    public function exportTopProductsCsv()
    {
        $filename = 'vendor_top_products_'.now()->format('Ymd_His').'.csv';
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
                    (int) $row->qty_sold,
                    number_format((float)$row->revenue, 2, '.', ''),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function exportTopCustomersCsv()
    {
        $filename = 'vendor_top_customers_'.now()->format('Ymd_His').'.csv';
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
                    number_format((float)$row->spent, 2, '.', ''),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function render()
    {
        return view('livewire.vendor.pages.analytics-dashboard')
            ->layout('components.layouts.vendor');
    }
}
