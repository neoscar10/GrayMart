<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\VendorProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OrderInvoiceController extends Controller
{
    public function download(Order $order)
    {
        $user = Auth::user();

        // --- Basic authorization ---
        // Admins: allow (assuming role column)
        $isAdmin  = $user && ($user->role ?? null) === 'admin';

        // Customer: must own the order
        $isOwner  = $user && (int)$order->user_id === (int)$user->id;

        // Vendor: must be the vendor for this order (either in orders.vendor_id
        // or via items.vendor_id if you ever aggregate)
        $isVendor = $user && (
            ((int)($order->vendor_id ?? 0) === (int)$user->id) ||
            $order->items()->where('vendor_id', $user->id)->exists()
        );

        if (!($isAdmin || $isOwner || $isVendor)) {
            abort(Response::HTTP_FORBIDDEN, 'You are not allowed to download this invoice.');
        }

        // Pull a vendor profile if present
        $vendorProfile = null;
        if (!empty($order->vendor_id)) {
            $vendorProfile = VendorProfile::where('user_id', $order->vendor_id)->first();
        }

        // Build a safe, simple data bag for the view
        $data = [
            'order'         => $order->load(['customer', 'items.vendor', 'items.product']),
            'vendorProfile' => $vendorProfile, // name only used; no images
            'issuedAt'      => now(),
        ];

        // Render with the image-free view
        $pdf = Pdf::loadView('pdf.invoice', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'invoice_order_'.$order->id.'.pdf';
        return $pdf->download($filename);
    }
}
