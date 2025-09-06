<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        /* DomPDF-safe CSS (no external assets) */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #1f1f1f;
            margin: 20px;
        }

        h1,
        h2,
        h3,
        h4,
        h5 {
            margin: 0;
            padding: 0;
        }

        .muted {
            color: #666;
        }

        .small {
            font-size: 11px;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .mt-6 {
            margin-top: 6px;
        }

        .mt-8 {
            margin-top: 8px;
        }

        .mt-12 {
            margin-top: 12px;
        }

        .mt-16 {
            margin-top: 16px;
        }

        .mt-20 {
            margin-top: 20px;
        }

        .mb-4 {
            margin-bottom: 4px;
        }

        .mb-8 {
            margin-bottom: 8px;
        }

        .mb-12 {
            margin-bottom: 12px;
        }

        .mb-16 {
            margin-bottom: 16px;
        }

        .hr {
            border-top: 1px solid #e5e5e5;
            height: 0;
            margin: 10px 0 0;
        }

        .border {
            border: 1px solid #e5e5e5;
        }

        .rounded {
            border-radius: 4px;
        }

        .p-10 {
            padding: 10px;
        }

        .p-12 {
            padding: 12px;
        }

        .p-16 {
            padding: 16px;
        }

        /* Layout helpers via tables for reliable rendering */
        table {
            border-collapse: collapse;
            width: 100%;
        }

        .header-table td {
            vertical-align: middle;
        }

        .brand {
            font-size: 20px;
            letter-spacing: 1px;
            font-weight: 700;
            color: #111;
            padding: 0 0 4px 0;
        }

        .doc-title {
            text-align: right;
        }

        .doc-title .title {
            font-size: 18px;
            font-weight: 700;
        }

        .box {
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 12px;
        }

        .two-col {
            width: 100%;
        }

        .two-col td {
            vertical-align: top;
            width: 50%;
        }

        .cell-pad {
            padding: 0 6px;
        }

        .table-items th,
        .table-items td {
            border: 1px solid #e5e5e5;
            padding: 8px;
        }

        .table-items thead th {
            background: #f6f6f6;
            text-align: left;
            font-weight: 600;
        }

        .totals {
            width: 50%;
            float: right;
            margin-top: 10px;
        }

        .totals td {
            padding: 6px 8px;
            border: 1px solid #e5e5e5;
        }

        .totals tr td:first-child {
            font-weight: 600;
            width: 50%;
            background: #fafafa;
        }
    </style>
</head>

<body>

    @php
        $currency = $order->currency ?? 'USD';
        $sym = $currency === 'USD' ? '$' : ($currency === 'NGN' ? '₦' : $currency . ' ');
        $addr = $order->shipping_address ?? [];
        $customerName = optional($order->customer)->name ?: trim(($addr['first_name'] ?? '') . ' ' . ($addr['last_name'] ?? ''));
        $lines = [
            $addr['street'] ?? $addr['street_address'] ?? null,
            $addr['city'] ?? null,
            $addr['state'] ?? null,
            $addr['email'] ?? ($order->customer->email ?? null),
            $addr['phone'] ?? null,
        ];
        $lines = array_values(array_filter($lines, fn($v) => !empty($v)));
        $vendorName = ($order->items->pluck('vendor.name')->filter()->unique()->implode(', ')) ?: 'Vendor';
      @endphp

    <!-- Header (Brand + Invoice meta) -->
    <table class="header-table">
        <tr>
            <td>
                <div class="brand">GRAY MART</div>
                <div class="small muted">Marketplace Platform</div>
            </td>
            <td class="doc-title">
                <div class="title">INVOICE</div>
                <div class="small muted">#{{ $order->id }} • {{ $issuedAt->format('Y-m-d H:i') }}</div>
            </td>
        </tr>
    </table>
    <div class="hr"></div>

    <!-- “Sold by” line (no images) -->
    <div class="mt-12">
        <span class="small muted">Sold by:</span>
        <span class="fw-bold">{{ $vendorName }}</span>
    </div>

    <!-- Bill To & Order Info on the same row -->
    <table class="two-col mt-12">
        <tr>
            <td class="cell-pad">
                <div class="box">
                    <div class="fw-bold mb-4">Bill To</div>
                    <div>{{ $customerName ?: '—' }}</div>
                    @foreach($lines as $ln)
                        <div class="small muted">{{ $ln }}</div>
                    @endforeach
                </div>
            </td>
            <td class="cell-pad">
                <div class="box">
                    <div class="fw-bold mb-4">Order Info</div>
                    <div class="small muted">Placed: {{ $order->created_at->format('Y-m-d H:i') }}</div>
                    @if($order->payment_method)
                        <div class="small muted">Payment: {{ ucfirst($order->payment_method) }}</div>
                    @endif
                    @if($order->payment_status)
                        <div class="small muted">Payment Status: {{ ucfirst($order->payment_status) }}</div>
                    @endif
                    @if($order->external_payment_id)
                        <div class="small muted">Txn/Ref: {{ $order->external_payment_id }}</div>
                    @endif
                    <div class="small muted">Currency: {{ $currency }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Line items -->
    <div class="mt-16">
        <table class="table-items">
            <thead>
                <tr>
                    <th style="width:48%">Item</th>
                    <th style="width:12%">Qty</th>
                    <th style="width:20%">Unit Price</th>
                    <th style="width:20%">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $it)
                    @php
                        $name = $it->product_name ?: optional($it->product)->name ?: 'Item';
                        $label = $it->meta['variant_label'] ?? null;
                        $qty = (int) $it->quantity;
                        $unit = (float) $it->unit_price;
                        $line = (float) $it->total_price;
                      @endphp
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $name }}</div>
                            @if($label)
                                <div class="small muted">{{ $label }}</div>
                            @endif
                        </td>
                        <td>{{ $qty }}</td>
                        <td>{{ $sym }}{{ number_format($unit, 2) }}</td>
                        <td>{{ $sym }}{{ number_format($line, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Totals (right column) -->
    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">{{ $sym }}{{ number_format((float) ($order->subtotal_amount ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td>Shipping</td>
            <td class="text-right">{{ $sym }}{{ number_format((float) ($order->shipping_amount ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="text-right">- {{ $sym }}{{ number_format((float) ($order->discount_total ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td>Grand Total</td>
            <td class="text-right fw-bold">{{ $sym }}{{ number_format((float) ($order->total_amount ?? 0), 2) }}</td>
        </tr>
    </table>

    <!-- Footer note -->
    <div class="mt-20 small muted">
        Thank you for shopping with Gray Mart.
    </div>

</body>

</html>