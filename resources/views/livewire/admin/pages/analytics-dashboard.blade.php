<div class="container-fluid py-4">

  {{-- Sales Summary --}}
  <div class="row mb-4">
    @foreach([
      'totalRevenue'   => ['label'=>'Total Revenue',   'icon'=>'fa-dollar-sign',   'class'=>'primary'],
      'totalOrders'    => ['label'=>'Total Orders',    'icon'=>'fa-shopping-cart', 'class'=>'info'],
      'totalCustomers' => ['label'=>'Total Customers', 'icon'=>'fa-users',         'class'=>'success'],
      'totalVendors'   => ['label'=>'Total Vendors',   'icon'=>'fa-store',         'class'=>'warning'],
    ] as $key => $meta)
      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <i class="fas {{ $meta['icon'] }} fa-2x text-{{ $meta['class'] }}"></i>
            <h6 class="mt-2 text-uppercase">{{ $meta['label'] }}</h6>
            <h3 class="fw-bold">
              @if($key==='totalRevenue') $ @endif
              {{ number_format($salesSummary[$key], $key==='totalRevenue'?2:0) }}
            </h3>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  {{-- Monthly Revenue Chart --}}
  <div 
    class="card mb-4" 
    wire:ignore 
    x-data='{
      labels: {{ json_encode($revenueLabels) }},
      series: {{ json_encode($revenueData) }},
      init() {
        this.chart = new ApexCharts(this.$refs.chart, {
          chart: { type: "line", height: 300 },
          series: [{ name: "Revenue", data: this.series }],
          xaxis: { categories: this.labels },
          yaxis: { labels: { formatter: val => "$" + val.toFixed(2) } },
          stroke: { curve: "smooth" },
          tooltip: { y: val => "$" + val.toFixed(2) }
        });
        this.chart.render();
      }
    }'
    x-init="init()"
  >
    <div class="card-header d-flex justify-content-between">
      <span>Monthly Revenue</span>
      <button wire:click="exportRevenueCsv" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-file-csv me-1"></i> CSV
      </button>
    </div>
    <div class="card-body">
      <div x-ref="chart"></div>

      {{-- Fallback Table --}}
      
    </div>
  </div>

  {{-- Top Products --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
      <span>Top Products by Revenue</span>
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
          @foreach($topProducts as $p)
            <tr>
              <td>{{ $p->name }}</td>
              <td class="text-end">{{ $p->qty_sold }}</td>
              <td class="text-end">${{ number_format($p->revenue,2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Top Vendors --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
      <span>Top Vendors by Revenue</span>
      <button wire:click="exportTopVendorsCsv" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-file-csv me-1"></i> CSV
      </button>
    </div>
    <div class="card-body p-0">
      <table class="table table-striped mb-0">
        <thead class="table-light">
          <tr>
            <th>Vendor</th>
            <th class="text-end">Revenue</th>
          </tr>
        </thead>
        <tbody>
          @foreach($topVendors as $v)
            <tr>
              <td>{{ $v->name }}</td>
              <td class="text-end">${{ number_format($v->revenue,2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Top Customers --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
      <span>Top Customers by Spend</span>
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
          @foreach($topCustomers as $c)
            <tr>
              <td>{{ $c->name }}</td>
              <td class="text-end">${{ number_format($c->spent,2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

</div>
