<div class="container-fluid py-4">

   {{-- Toolbar: Date Range --}}
   <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
         <div class="row g-2 align-items-end">
            <div class="col-md-3">
               <label class="form-label small text-muted">Date Range</label>
               <select class="form-select" wire:model.live="range">
                  <option value="last_7">Last 7 days</option>
                  <option value="last_30">Last 30 days</option>
                  <option value="last_90">Last 90 days</option>
                  <option value="ytd">Year to date</option>
                  <option value="last_12_months">Last 12 months</option>
                  <option value="custom">Custom</option>
               </select>
            </div>

            @if($range === 'custom')
            <div class="col-md-3">
               <label class="form-label small text-muted">From</label>
               <input type="date" class="form-control" wire:model.defer="fromDate">
            </div>
            <div class="col-md-3">
               <label class="form-label small text-muted">To</label>
               <input type="date" class="form-control" wire:model.defer="toDate">
            </div>
            <div class="col-md-3">
               <button class="btn btn-primary w-100" wire:click="applyCustom">
                 <i class="fa-solid fa-filter me-1"></i> Apply
               </button>
            </div>
         @endif
         </div>
      </div>
   </div>

   {{-- KPI Cards --}}
   <div class="row mb-4 g-3">
      @php
      $cards = [
         ['key' => 'revenue', 'label' => 'Revenue', 'icon' => 'fa-sack-dollar', 'class' => 'primary', 'money' => true, 'delta_key' => 'revenue_delta'],
         ['key' => 'sales', 'label' => 'Items Sold', 'icon' => 'fa-cart-shopping', 'class' => 'info', 'money' => false, 'delta_key' => 'sales_delta'],
         ['key' => 'orders', 'label' => 'Orders', 'icon' => 'fa-bag-shopping', 'class' => 'secondary', 'money' => false, 'delta_key' => null],
         ['key' => 'active_products', 'label' => 'Active Products', 'icon' => 'fa-boxes-stacked', 'class' => 'warning', 'money' => false, 'delta_key' => null],
         ['key' => 'active_auctions', 'label' => 'Active Auctions', 'icon' => 'fa-gavel', 'class' => 'success', 'money' => false, 'delta_key' => null],
         ['key' => 'bids', 'label' => 'Bids (in period)', 'icon' => 'fa-bolt', 'class' => 'danger', 'money' => false, 'delta_key' => null],
      ];
   @endphp

      @foreach($cards as $c)
        <div class="col-md-4 col-xl-2">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
               <i class="fas {{ $c['icon'] }} fa-2x text-{{ $c['class'] }}"></i>
               <h6 class="mt-2 text-uppercase">{{ $c['label'] }}</h6>
               <h3 class="fw-bold mb-1">
                 @if($c['money']) ₦ @endif
                 {{ number_format($kpis[$c['key']] ?? 0, $c['money'] ? 2 : 0) }}
               </h3>
               @if($c['delta_key'])
               @php $delta = (float) ($kpis[$c['delta_key']] ?? 0); @endphp
               <span class="badge {{ $delta >= 0 ? 'bg-success' : 'bg-danger' }}">
                {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 2) }}%
               </span>
            @endif
            </div>
          </div>
        </div>
     @endforeach

      {{-- Rating card spans full width on small screens for readability --}}
      <div class="col-md-12 col-xl-4">
         <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
               <div class="d-flex align-items-center gap-3">
                  <i class="fas fa-star fa-2x text-warning"></i>
                  <div>
                     <h6 class="text-uppercase mb-0">Average Rating</h6>
                     <div class="fs-4 fw-bold">
                        {{ number_format($kpis['rating_avg'] ?? 0, 2) }}
                        <small class="text-muted">({{ number_format($kpis['rating_count'] ?? 0) }} reviews)</small>
                     </div>
                  </div>
               </div>
               <div>
                  <a href="" class="btn btn-sm btn-outline-primary">
                     Manage Reviews
                  </a>
               </div>
            </div>
         </div>
      </div>
   </div>

   {{-- Monthly Revenue Chart --}}
   <div class="card mb-4" wire:key="vendor-revenue-chart" x-data="{
      chart: null,
      labels: @js($revenueLabels),
      series: @js($revenueData),
      init() {
        this.chart = new ApexCharts(this.$refs.chart, {
          chart: { type: 'line', height: 300 },
          series: [{ name: 'Revenue', data: this.series }],
          xaxis: { categories: this.labels },
          yaxis: { labels: { formatter: val => '₦' + Number(val).toFixed(2) } },
          stroke: { curve: 'smooth' },
          tooltip: { y: { formatter: val => '₦' + Number(val).toFixed(2) } }
        });
        this.chart.render();

        window.addEventListener('vendor-analytics-updated', (e) => {
          const { labels, series } = e.detail;
          this.chart.updateOptions({ xaxis: { categories: labels } });
          this.chart.updateSeries([{ name: 'Revenue', data: series }], true);
        });
      }
    }" x-init="init()">
      <div class="card-header d-flex justify-content-between align-items-center">
         <span>Revenue by Month</span>
         <button wire:click="exportRevenueCsv" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-csv me-1"></i> CSV
         </button>
      </div>
      <div class="card-body">
         <div x-ref="chart"></div>
      </div>
   </div>

   {{-- Top Products --}}
   <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
         <span>Top Products by Revenue (Delivered)</span>
         <button wire:click="exportTopProductsCsv" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-csv me-1"></i> CSV
         </button>
      </div>
      <div class="card-body p-0">
         <table class="table table-striped mb-0">
            <thead class="table-light">
               <tr>
                  <th>Product</th>
                  <th class="text-end">Qty Sold</th>
                  <th class="text-end">Revenue</th>
               </tr>
            </thead>
            <tbody>
               @forelse($topProducts as $p)
               <tr>
                 <td>{{ $p->name }}</td>
                 <td class="text-end">{{ number_format($p->qty_sold) }}</td>
                 <td class="text-end">₦{{ number_format($p->revenue, 2) }}</td>
               </tr>
            @empty
               <tr>
                 <td colspan="3" class="text-center text-muted py-4">No data for selected period</td>
               </tr>
            @endforelse
            </tbody>
         </table>
      </div>
   </div>

   {{-- Top Customers --}}
   <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
         <span>Top Customers by Spend (Delivered)</span>
         <button wire:click="exportTopCustomersCsv" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-csv me-1"></i> CSV
         </button>
      </div>
      <div class="card-body p-0">
         <table class="table table-striped mb-0">
            <thead class="table-light">
               <tr>
                  <th>Customer</th>
                  <th class="text-end">Spent</th>
               </tr>
            </thead>
            <tbody>
               @forelse($topCustomers as $c)
               <tr>
                 <td>{{ $c->name }}</td>
                 <td class="text-end">₦{{ number_format($c->spent, 2) }}</td>
               </tr>
            @empty
               <tr>
                 <td colspan="2" class="text-center text-muted py-4">No data for selected period</td>
               </tr>
            @endforelse
            </tbody>
         </table>
      </div>
   </div>

</div>