<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalDiagnosticsController extends Controller
{
    public function ping(Request $request)
    {
        $cfg   = config('paypal');
        $mode  = $cfg['mode'] ?? 'sandbox';
        $bucket= $cfg[$mode] ?? [];

        $mask = function ($s) { return $s ? substr($s, 0, 4).'…'.substr($s, -4) : null; };

        $out = [
            'mode'               => $mode,
            'currency'           => config('paypal.currency', 'USD'),
            'validate_ssl'       => (bool)($cfg['validate_ssl'] ?? true),
            'client_id_present'  => !empty($bucket['client_id']),
            'client_id_sample'   => $mask($bucket['client_id'] ?? ''),
            'secret_present'     => !empty($bucket['client_secret']),
        ];

        try {
            $provider = new PayPalClient();
            $provider->setApiCredentials($cfg);

            $tokenResult = $provider->getAccessToken();

            // Accept both array (['access_token' => ...]) and plain string tokens
            $accessToken = null;
            if (is_string($tokenResult) && strlen($tokenResult) > 20) {
                $accessToken = $tokenResult;
            } elseif (is_array($tokenResult) && !empty($tokenResult['access_token'])) {
                $accessToken = $tokenResult['access_token'];
            }

            if (!$accessToken) {
                $out['oauth_ok']      = false;
                $out['token_payload'] = $tokenResult; // safe to inspect on dev
                return response()->json($out, 500);
            }

            // Set it back (harmless on versions that don't require)
            try { $provider->setAccessToken($accessToken); } catch (\Throwable $e) {}

            $out['oauth_ok']      = true;
            $out['token_prefix']  = substr($accessToken, 0, 12);

            // Probe a $1 order to ensure endpoints are reachable
            $resp = $provider->createOrder([
                'intent' => 'CAPTURE',
                'application_context' => [
                    'return_url'  => url('/checkout/success'),
                    'cancel_url'  => url('/checkout/cancel'),
                    'user_action' => 'PAY_NOW',
                ],
                'purchase_units' => [[
                    'amount' => ['currency_code' => $out['currency'], 'value' => '1.00'],
                ]],
            ]);

            $out['create_order_status']     = $resp['status'] ?? null;
            $out['create_order_has_links']  = !empty($resp['links']);
            return response()->json($out);

        } catch (\Throwable $e) {
            $out['exception'] = $e->getMessage();
            Log::error('PayPal diagnostics exception', ['e' => $e]);
            return response()->json($out, 500);
        }
    }
}
