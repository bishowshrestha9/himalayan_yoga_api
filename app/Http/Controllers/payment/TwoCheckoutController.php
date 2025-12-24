<?php

namespace App\Http\Controllers\payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="2Checkout Payment",
 *     description="2Checkout (Verifone) payment integration endpoints"
 * )
 */
class TwoCheckoutController extends Controller
{
    private $merchantCode;
    private $secretKey;
    private $apiUrl;
    private $buyLinkSecretWord;
    private $publishableKey;
    private $privateKey;

    public function __construct()
    {
        $this->merchantCode = config('services.twocheckout.merchant_code');
        $this->secretKey = config('services.twocheckout.secret_key');
        $this->buyLinkSecretWord = config('services.twocheckout.buy_link_secret');
        $this->publishableKey = config('services.twocheckout.publishable_key');
        $this->privateKey = config('services.twocheckout.private_key');
        $this->apiUrl = config('services.twocheckout.sandbox')
            ? 'https://api.avangate.com/rest/6.0/'
            : 'https://api.avangate.com/rest/6.0/';
    }

    /**
     * @OA\Post(
     *     path="/payment/2checkout/create-order",
     *     tags={"2Checkout Payment"},
     *     summary="Create a 2Checkout order",
     *     description="Creates a 2Checkout order and returns order details for frontend checkout",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"fullName","email","phoneNumber","description","amount","products"},
     *             @OA\Property(property="fullName", type="string", example="John Doe", description="Customer full name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Customer email address"),
     *             @OA\Property(property="phoneNumber", type="string", example="+61400000000", description="Customer phone number"),
     *             @OA\Property(property="description", type="string", example="Yoga class booking", description="Order description"),
     *             @OA\Property(property="amount", type="number", format="float", example=100.50, description="Total amount"),
     *             @OA\Property(property="currency", type="string", example="USD", description="Currency code (default: USD)"),
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="name", type="string", example="Yoga Class"),
     *                     @OA\Property(property="quantity", type="integer", example=1),
     *                     @OA\Property(property="price", type="number", example=100.50)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="payment_id", type="integer", example=1),
     *                 @OA\Property(property="order_reference", type="string", example="ORD-2025-001"),
     *                 @OA\Property(property="checkout_url", type="string", example="https://secure.2checkout.com/checkout/buy"),
     *                 @OA\Property(property="merchant_code", type="string", example="494BD2F3-0C35-4F9E-B8C1-F6086C3EAE06"),
     *                 @OA\Property(property="signature", type="string", example="generated_signature_hash")
     *             ),
     *             @OA\Property(property="message", type="string", example="Order created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error creating order")
     *         )
     *     )
     * )
     */
    public function createOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'fullName' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phoneNumber' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'amount' => 'required|numeric|min:1',
                'currency' => 'nullable|string|max:3',
                'products' => 'required|array|min:1',
                'products.*.name' => 'required|string',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.price' => 'required|numeric|min:0',
            ]);

            $currency = $validated['currency'] ?? 'USD';
            $orderReference = 'ORD-' . time() . '-' . rand(1000, 9999);

            // Create payment record in database
            $payment = Payment::create([
                'full_name' => $validated['fullName'],
                'email' => $validated['email'],
                'phone_number' => $validated['phoneNumber'],
                'invoice_number' => $orderReference,
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'currency' => $currency,
                'status' => Payment::STATUS_PENDING,
                'payment_intent_id' => $orderReference, // Using order reference as identifier
            ]);

            // Generate signature for 2Checkout
            $signature = $this->generateSignature([
                'merchant' => $this->merchantCode,
                'order_ref' => $orderReference,
                'amount' => $validated['amount'],
                'currency' => $currency,
            ]);

            // Prepare checkout data
            $checkoutData = [
                'payment_id' => $payment->id,
                'order_reference' => $orderReference,
                'merchant_code' => $this->merchantCode,
                'amount' => $validated['amount'],
                'currency' => $currency,
                'products' => $validated['products'],
                'customer' => [
                    'name' => $validated['fullName'],
                    'email' => $validated['email'],
                    'phone' => $validated['phoneNumber'],
                ],
                'signature' => $signature,
                'return_url' => config('app.url') . '/payment/2checkout/success',
                'cancel_url' => config('app.url') . '/payment/2checkout/cancel',
            ];

            return response()->json([
                'success' => true,
                'data' => $checkoutData,
                'message' => 'Order created successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('2Checkout order creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/payment/2checkout/ipn",
     *     tags={"2Checkout Payment"},
     *     summary="Handle 2Checkout IPN (Instant Payment Notification)",
     *     description="Webhook endpoint for 2Checkout to send payment notifications. This should be configured in your 2Checkout account.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="REFNO", type="string", example="123456789"),
     *             @OA\Property(property="ORDERNO", type="string", example="ORD-2025-001"),
     *             @OA\Property(property="ORDERSTATUS", type="string", example="COMPLETE"),
     *             @OA\Property(property="IPN_TOTALGENERAL", type="number", example=100.50),
     *             @OA\Property(property="CURRENCY", type="string", example="USD"),
     *             @OA\Property(property="HASH", type="string", example="signature_hash")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="IPN processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="IPN processed")
     *         )
     *     )
     * )
     */
    public function handleIPN(Request $request)
    {
        try {
            // Log all incoming IPN data for debugging
            Log::info('2Checkout IPN received', [
                'data' => $request->all(),
                'method' => $request->method()
            ]);

            $ipnData = $request->all();

            // Handle test parameter from 2Checkout dashboard configuration
            if (isset($ipnData['test']) && $ipnData['test'] == '1') {
                Log::info('2Checkout IPN test request (test=1 parameter)');
                return response('<EPAYMENT>' . date('YmdHis') . '|OK</EPAYMENT>')
                    ->header('Content-Type', 'text/plain');
            }

            // Handle empty test requests from 2Checkout
            if (empty($ipnData)) {
                Log::info('2Checkout IPN test request (empty data)');
                return response('<EPAYMENT>' . date('YmdHis') . '|OK</EPAYMENT>')
                    ->header('Content-Type', 'text/plain');
            }

            // Check if this is a test/verification request (missing required fields)
            if (!isset($ipnData['HASH']) || !isset($ipnData['ORDERNO'])) {
                Log::info('2Checkout IPN verification request - missing required fields');
                return response('<EPAYMENT>' . date('YmdHis') . '|OK</EPAYMENT>')
                    ->header('Content-Type', 'text/plain');
            }

            // Verify IPN signature
            if (!$this->verifyIPNSignature($ipnData)) {
                Log::warning('Invalid 2Checkout IPN signature');
                return response('<EPAYMENT>' . date('YmdHis') . '|INVALID_SIGNATURE</EPAYMENT>')
                    ->header('Content-Type', 'text/plain');
            }

            $orderReference = $ipnData['ORDERNO'] ?? null;
            $orderStatus = $ipnData['ORDERSTATUS'] ?? null;
            $refNo = $ipnData['REFNO'] ?? null;

            if (!$orderReference) {
                Log::warning('2Checkout IPN missing order reference');
                return response('<EPAYMENT>' . date('YmdHis') . '|MISSING_ORDER</EPAYMENT>')
                    ->header('Content-Type', 'text/plain');
            }

            // Find payment by order reference
            $payment = Payment::where('payment_intent_id', $orderReference)->first();

            if (!$payment) {
                Log::warning('Payment not found for order: ' . $orderReference);
                // Still return success to 2Checkout to avoid retries
                return response('<EPAYMENT>' . date('YmdHis') . '|ORDER_NOT_FOUND</EPAYMENT>')
                    ->header('Content-Type', 'text/plain');
            }

            // Update payment based on order status
            switch ($orderStatus) {
                case 'COMPLETE':
                case 'PAYMENT_RECEIVED':
                    $payment->markAsSuccessful();
                    $payment->update([
                        'payment_method' => '2checkout',
                        'client_secret' => $refNo, // Store 2Checkout reference number
                    ]);
                    Log::info('2Checkout payment completed', ['order' => $orderReference]);
                    break;

                case 'REFUND':
                case 'REVERSED':
                    $payment->update(['status' => 'refunded']);
                    Log::info('2Checkout payment refunded', ['order' => $orderReference]);
                    break;

                case 'CANCELED':
                    $payment->markAsCanceled();
                    Log::info('2Checkout payment canceled', ['order' => $orderReference]);
                    break;

                default:
                    Log::info('2Checkout IPN received with status: ' . $orderStatus);
            }

            // Send IPN response hash
            $responseHash = $this->generateIPNResponse($ipnData);

            return response($responseHash)
                ->header('Content-Type', 'text/plain');

        } catch (\Exception $e) {
            Log::error('2Checkout IPN processing failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            // Return success response to prevent 2Checkout from retrying
            return response('<EPAYMENT>' . date('YmdHis') . '|ERROR</EPAYMENT>')
                ->header('Content-Type', 'text/plain');
        }
    }

    /**
     * Generate signature for 2Checkout checkout
     */
    private function generateSignature(array $params)
    {
        // Signature format: merchant + order_ref + amount + currency + secret_key
        $signatureString = implode('', [
            $params['merchant'],
            $params['order_ref'],
            $params['amount'],
            $params['currency'],
            $this->buyLinkSecretWord
        ]);

        return hash_hmac('sha256', $signatureString, $this->buyLinkSecretWord);
    }

    /**
     * Verify IPN signature from 2Checkout
     */
    private function verifyIPNSignature(array $ipnData)
    {
        $receivedHash = $ipnData['HASH'] ?? '';
        unset($ipnData['HASH']);

        // Build signature string from IPN parameters
        $signatureString = '';
        foreach ($ipnData as $key => $value) {
            $signatureString .= strlen($value) . $value;
        }

        $calculatedHash = hash_hmac('md5', $signatureString, $this->secretKey);

        return hash_equals($calculatedHash, $receivedHash);
    }

    /**
     * Generate IPN response hash
     */
    private function generateIPNResponse(array $ipnData)
    {
        $refNo = $ipnData['REFNO'] ?? '';
        $orderNo = $ipnData['ORDERNO'] ?? '';

        $responseString = strlen($refNo) . $refNo . strlen($orderNo) . $orderNo;
        $responseHash = hash_hmac('md5', $responseString, $this->secretKey);

        return "<EPAYMENT>" . date('YmdHis') . "|" . $responseHash . "</EPAYMENT>";
    }

    /**
     * @OA\Get(
     *     path="/payment/2checkout/config",
     *     tags={"2Checkout Payment"},
     *     summary="Get 2Checkout configuration",
     *     description="Returns the publishable key and merchant code for frontend integration",
     *     @OA\Response(
     *         response=200,
     *         description="Configuration retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="merchant_code", type="string", example="494BD2F3-0C35-4F9E-B8C1-F6086C3EAE06"),
     *                 @OA\Property(property="sandbox", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function getConfig()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'merchant_code' => $this->merchantCode,
                'publishable_key' => $this->publishableKey,
                'sandbox' => config('services.twocheckout.sandbox', true),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/payment/2checkout/charge",
     *     tags={"2Checkout Payment"},
     *     summary="Charge a tokenized card",
     *     description="Processes payment using a token from 2Checkout.js. Updates payment status to succeeded on successful charge.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","fullName","email","phoneNumber","amount"},
     *             @OA\Property(property="token", type="string", example="abc123token", description="2Checkout.js token"),
     *             @OA\Property(property="fullName", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="phoneNumber", type="string", example="+61400000000"),
     *             @OA\Property(property="description", type="string", example="Yoga Class"),
     *             @OA\Property(property="amount", type="number", example=100.50),
     *             @OA\Property(property="currency", type="string", example="USD")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="payment_id", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Payment processed successfully")
     *         )
     *     )
     * )
     */
    public function chargeToken(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string',
                'fullName' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phoneNumber' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
                'amount' => 'required|numeric|min:1',
                'currency' => 'nullable|string|max:3',
            ]);

            $currency = $validated['currency'] ?? 'USD';
            $description = $validated['description'] ?? 'Payment';
            $orderReference = 'ORD-' . time() . '-' . rand(1000, 9999);

            // Create payment record with pending status
            $payment = Payment::create([
                'full_name' => $validated['fullName'],
                'email' => $validated['email'],
                'phone_number' => $validated['phoneNumber'],
                'invoice_number' => $orderReference,
                'description' => $description,
                'amount' => $validated['amount'],
                'currency' => $currency,
                'status' => Payment::STATUS_PENDING,
                'payment_intent_id' => $orderReference,
            ]);

            Log::info('2Checkout charge initiated', [
                'payment_id' => $payment->id,
                'order_reference' => $orderReference,
                'amount' => $validated['amount']
            ]);

            // Split name for billing details
            $nameParts = explode(' ', $validated['fullName'], 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Generate authentication header for 2Checkout API
            $date = gmdate('Y-m-d H:i:s');
            $authHeader = 'code="' . $this->merchantCode . '" key="' . $this->privateKey . '" date="' . $date . '"';

            // Charge the token using 2Checkout API
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Avangate-Authentication' => $authHeader,
            ])->post($this->apiUrl . 'orders/', [
                        'Currency' => $currency,
                        'Language' => 'en',
                        'Country' => 'US',
                        'CustomerIP' => $request->ip(),
                        'ExternalReference' => $orderReference,
                        'Source' => 'API',
                        'BillingDetails' => [
                            'FirstName' => $firstName,
                            'LastName' => $lastName,
                            'Email' => $validated['email'],
                            'Phone' => $validated['phoneNumber'],
                            'Country' => 'US',
                        ],
                        'Items' => [
                            [
                                'Name' => $description,
                                'Description' => $description,
                                'Quantity' => 1,
                                'IsDynamic' => true,
                                'Tangible' => false,
                                'PurchaseType' => 'PRODUCT',
                                'Price' => [
                                    'Amount' => $validated['amount'],
                                    'Type' => 'CUSTOM',
                                ],
                            ]
                        ],
                        'PaymentDetails' => [
                            'Type' => 'EES_TOKEN_PAYMENT',
                            'Currency' => $currency,
                            'CustomerIP' => $request->ip(),
                            'PaymentMethod' => [
                                'EesToken' => $validated['token'],
                                'Vendor3DSReturnURL' => config('app.url') . '/payment/2checkout/success',
                                'Vendor3DSCancelURL' => config('app.url') . '/payment/2checkout/cancel',
                            ],
                        ],
                    ]);

            Log::info('2Checkout API response', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                // Update payment as successful
                $payment->markAsSuccessful();
                $payment->update([
                    'client_secret' => $responseData['RefNo'] ?? null,
                ]);

                Log::info('2Checkout payment succeeded', [
                    'payment_id' => $payment->id,
                    'ref_no' => $responseData['RefNo'] ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'payment_id' => $payment->id,
                    'order_reference' => $orderReference,
                    'message' => 'Payment processed successfully',
                ]);
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? 'Payment processing failed';

                // Log detailed error for debugging
                Log::error('2Checkout payment failed', [
                    'payment_id' => $payment->id,
                    'error' => $errorMessage,
                    'response' => $errorData,
                    'status_code' => $response->status()
                ]);

                $payment->markAsFailed($errorMessage);

                // Return user-friendly message
                return response()->json([
                    'success' => false,
                    'message' => 'Payment could not be processed. Please check your card details and try again.',
                ], 400);
            }
        } catch (\Exception $e) {
            // Log detailed error for debugging
            Log::error('2Checkout charge exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_id' => isset($payment) ? $payment->id : null
            ]);

            if (isset($payment)) {
                $payment->markAsFailed($e->getMessage());
            }

            // Return user-friendly message
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again or contact support if the problem persists.',
            ], 500);
        }
    }
}
