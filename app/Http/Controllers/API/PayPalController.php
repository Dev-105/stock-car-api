<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\Command;
use App\Models\LigneCommande;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentSuccessMail;
class PayPalController extends Controller
{
    protected $clientId;
    protected $secret;
    protected $base;

    public function __construct()
    {
        $this->clientId = env('PAYPAL_CLIENT_ID');
        $this->secret = env('PAYPAL_SECRET');
        $this->base = env('PAYPAL_MODE') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    protected function getAccessToken()
    {
        $client = new Client();
        $res = $client->post("{$this->base}/v1/oauth2/token", [
            'auth' => [$this->clientId, $this->secret],
            'form_params' => ['grant_type' => 'client_credentials'],
        ]);
        $body = json_decode((string)$res->getBody(), true);
        return $body['access_token'] ?? null;
    }

    // Create a PayPal order using the server-side cart total (requires auth)
    public function createOrder(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Recalculate cart total server-side to avoid tampering
        $cartItems = CartItem::with('product')
                        ->where('user_id', $user->id)
                        ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 400);
        }

        $total = 0;
        foreach ($cartItems as $item) {
            if ($item->product->stock < $item->quantity) {
                return response()->json(['success' => false, 'message' => "Insufficient stock for {$item->product->title}"], 400);
            }
            $total += $item->product->final_price * $item->quantity;
        }

        // Optionally apply promo code (do not mark used yet)
        $promoCode = null;
        if ($request->has('promo_code')) {
            $promoCode = PromoCode::where('code', $request->promo_code)->first();
            if ($promoCode && $promoCode->is_valid) {
                $discount = $promoCode->calculateDiscount($total);
                $total -= $discount;
            } else {
                return response()->json(['success' => false, 'message' => 'Invalid or expired promo code'], 400);
            }
        }

        // Use fallback currency for PayPal if MAD is not supported by your PayPal account
        $fallbackCurrency = env('PAYPAL_FALLBACK_CURRENCY', 'USD');
        $exchangeRate = floatval(env('EXCHANGE_RATE_MAD_TO_USD', 0.10));

        $amountMad = floatval($total);
        $converted = number_format($amountMad * $exchangeRate, 2, '.', '');

        $token = $this->getAccessToken();
        if (! $token) return response()->json(['success' => false, 'message' => 'Unable to get access token'], 500);

        $client = new Client();
        $res = $client->post("{$this->base}/v2/checkout/orders", [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $fallbackCurrency,
                            'value' => $converted,
                        ],
                    ],
                ],
            ],
        ]);

        $paypalResponse = json_decode((string)$res->getBody(), true);
        // include server-calculated totals for debugging/validation on client if needed
        $paypalResponse['_server_mad_total'] = number_format($amountMad, 2, '.', '');
        $paypalResponse['_server_converted_total'] = $converted;

        return response()->json($paypalResponse);
    }

    // Capture PayPal order, validate server-side, then create the app order transactionally
    public function captureOrder(Request $request, $orderId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $token = $this->getAccessToken();
        if (! $token) return response()->json(['success' => false, 'message' => 'Unable to get access token'], 500);

        $client = new Client();
        $res = $client->post("{$this->base}/v2/checkout/orders/{$orderId}/capture", [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ],
        ]);

        $captureData = json_decode((string)$res->getBody(), true);

        // Validate capture status
        $status = $captureData['status'] ?? null;
        if (strtolower($status) !== 'completed') {
            return response()->json(['success' => false, 'message' => 'Payment not completed', 'paypal' => $captureData], 400);
        }

        // Compute server-side cart total (must match captured amount)
        $cartItems = CartItem::with('product')
                        ->where('user_id', $user->id)
                        ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 400);
        }

        $total = 0;
        $orderItems = [];
        foreach ($cartItems as $item) {
            if ($item->product->stock < $item->quantity) {
                return response()->json(['success' => false, 'message' => "Insufficient stock for {$item->product->title}"], 400);
            }
            $finalPrice = $item->product->final_price;
            $subtotal = $finalPrice * $item->quantity;
            $total += $subtotal;
            $orderItems[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $finalPrice,
                'subtotal' => $subtotal,
            ];
        }

        // If a promo code id was stored in the PayPal order's custom fields (not implemented), or passed via request, handle it
        $promoCode = null;
        if ($request->has('promo_code')) {
            $promoCode = PromoCode::where('code', $request->promo_code)->first();
            if ($promoCode && $promoCode->is_valid) {
                $discount = $promoCode->calculateDiscount($total);
                $total -= $discount;
            } else {
                return response()->json(['success' => false, 'message' => 'Invalid or expired promo code'], 400);
            }
        }

        // Extract captured amount and currency from PayPal response
        $capturedAmount = null;
        $capturedCurrency = null;
        if (!empty($captureData['purchase_units'][0]['payments']['captures'][0]['amount'])) {
            $cap = $captureData['purchase_units'][0]['payments']['captures'][0]['amount'];
            $capturedAmount = $cap['value'] ?? null;
            $capturedCurrency = $cap['currency_code'] ?? null;
        }

        // Convert server MAD total to fallback currency for comparison
        $fallbackCurrency = env('PAYPAL_FALLBACK_CURRENCY', 'USD');
        $exchangeRate = floatval(env('EXCHANGE_RATE_MAD_TO_USD', 0.10));
        $convertedTotal = number_format(floatval($total) * $exchangeRate, 2, '.', '');

        // Compare amounts allowing small rounding differences
        if ($capturedAmount === null) {
            return response()->json(['success' => false, 'message' => 'No captured amount found in PayPal response', 'paypal' => $captureData], 400);
        }

        if (strtoupper($capturedCurrency) !== strtoupper($fallbackCurrency) || abs(floatval($capturedAmount) - floatval($convertedTotal)) > 0.5) {
            return response()->json([
                'success' => false,
                'message' => 'Captured amount does not match expected converted cart total',
                'expected_converted' => $convertedTotal,
                'expected_currency' => $fallbackCurrency,
                'captured' => $capturedAmount,
                'captured_currency' => $capturedCurrency,
                'paypal' => $captureData
            ], 400);
        }

        // All checks passed: create the order server-side
        try {
            DB::beginTransaction();

            $order = Command::create([
                'user_id' => $user->id,
                'total' => $total,
                'status' => 'pending',
                'promo_code_id' => $promoCode ? $promoCode->id : null,
            ]);

            foreach ($orderItems as $item) {
                LigneCommande::create([
                    'command_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Reduce stock
                $product = Product::find($item['product_id']);
                $product->reduceStock($item['quantity']);
            }

            // Clear cart
            CartItem::where('user_id', $user->id)->delete();

            // Mark promo used if provided
            if ($promoCode) {
                $promoCode->use();
            }

            // Add fidelity points (1 point per 10 units)
            $pointsEarned = floor($total / 10);
            if ($pointsEarned > 0) {
                $user->addFidelityPoints($pointsEarned);
            }

            DB::commit();
            Mail::to($user->email)->send(new PaymentSuccessMail($order));
            return response()->json(['success' => true, 'order' => $order->load('lignes.product'), 'paypal' => $captureData]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Error creating order: ' . $e->getMessage()], 500);
        }
    }
}
