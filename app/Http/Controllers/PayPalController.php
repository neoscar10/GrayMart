<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Models\Order;

class PayPalController extends Controller
{
    public function success(Request $request)
    {
        $token = $request->get('token'); // PayPal returns ?token={PAYPAL_ORDER_ID}
        if (!$token) {
            return redirect()->route('checkout')->with('error', 'Missing PayPal token.');
        }

        try {
            $paypal = new PayPalClient();
            $paypal->setApiCredentials(config('paypal'));
            $paypal->getAccessToken();

            // Capture payment
            $result = $paypal->capturePaymentOrder($token);

            // Mark all related orders as paid
            $orders = Order::where('external_payment_id', $token)->get();
            if ($orders->isEmpty()) {
                Log::warning('PayPal success: no orders found for token', ['token' => $token]);
                return redirect()->route('checkout')->with('error', 'No related order found.');
            }

            $isPaid = false;
            if (isset($result['status']) && in_array($result['status'], ['COMPLETED', 'APPROVED'])) {
                $isPaid = true;
            }

            foreach ($orders as $o) {
                $o->update([
                    'payment_status'           => $isPaid ? 'paid' : 'failed',
                    'status'                   => $isPaid ? 'processing' : 'new',
                    'external_payment_payload' => $result, // store capture payload
                ]);
            }

            if ($isPaid) {
                return redirect()->route('store.orders.thankyou') // <- create a pretty Thank You page
                    ->with('success', 'Payment complete. Thank you!');
            }

            return redirect()->route('checkout')->with('error', 'Payment not completed.');

        } catch (\Throwable $e) {
            Log::error('PayPal success handler error', ['e' => $e]);
            return redirect()->route('checkout')->with('error', 'Payment capture failed.');
        }
    }

    public function cancel()
    {
        // Buyer cancelled on PayPal
        return redirect()->route('checkout')->with('error', 'Payment cancelled.');
    }
}
