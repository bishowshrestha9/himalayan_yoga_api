<?php

namespace App\Http\Controllers\payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Payment",
 *     description="Stripe payment integration endpoints"
 * )
 */
class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * @OA\Post(
     *     path="/payment/create-payment-intent",
     *     tags={"Payment"},
     *     summary="Create a payment intent for embedded checkout",
     *     description="Creates a Stripe payment intent and saves payment record to database. Returns client secret for frontend payment confirmation.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"fullName","email","phoneNumber","invoiceNumber","description","amount"},
     *             @OA\Property(property="fullName", type="string", example="John Doe", description="Customer full name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Customer email address"),
     *             @OA\Property(property="phoneNumber", type="string", example="+61400000000", description="Customer phone number"),
     *             @OA\Property(property="invoiceNumber", type="string", example="INV-2025-001", description="Unique invoice number"),
     *             @OA\Property(property="description", type="string", example="Payment for premium service", description="Payment description"),
     *             @OA\Property(property="amount", type="number", format="float", example=100.50, description="Payment amount in AUD")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment intent created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="payment_id", type="integer", example=1),
     *                 @OA\Property(property="payment_intent", type="string", example="pi_xxxxxxxxxxxxx"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="amount", type="number", example=100.50),
     *                 @OA\Property(property="client_secret", type="string", example="pi_xxxxx_secret_xxxxx")
     *             ),
     *             @OA\Property(property="message", type="string", example="Payment intent created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or duplicate invoice number",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invoice number already exists")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error message")
     *         )
     *     )
     * )
     */
    public function createPaymentIntent(Request $request)
    {
        try {
            $request->validate([
                'fullName' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phoneNumber' => 'required|string|max:255',
                'invoiceNumber' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'amount' => 'required|numeric|min:1',
            ]);

            $stripe = new StripeClient(config('services.stripe.secret'));

            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $request->amount * 100,
                'currency' => 'aud',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'fullName' => $request->fullName,
                    'email' => $request->email,
                    'phoneNumber' => $request->phoneNumber,
                    'invoiceNumber' => $request->invoiceNumber,
                    'description' => $request->description,
                ],
            ]);

            // Create payment record in database
            $payment = Payment::create([
                'full_name' => $request->fullName,
                'email' => $request->email,
                'phone_number' => $request->phoneNumber,
                'invoice_number' => $request->invoiceNumber,
                'description' => $request->description,
                'amount' => $request->amount,
                'currency' => 'aud',
                'status' => Payment::STATUS_PENDING,
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_intent' => $paymentIntent->id,
                    'email' => $request->email,
                    'amount' => $request->amount,
                    'client_secret' => $paymentIntent->client_secret,
                ],
                'message' => 'Payment intent created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Stripe Webhook
     * 
     * This endpoint is ONLY called by Stripe servers - NOT from your frontend!
     * Configure in Stripe Dashboard: https://dashboard.stripe.com/webhooks
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentSuccess($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentFailed($paymentIntent);
                break;
            case 'payment_intent.canceled':
                $paymentIntent = $event->data->object;
                $this->handlePaymentCanceled($paymentIntent->id);
                break;

           

            default:
                \Log::info('Unhandled Stripe event type: ' . $event->type);
        }
        


        return response()->json(['success' => true]);
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSuccess($paymentIntent)
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();
        
        if ($payment) {
            $payment->markAsSuccessful();
            $payment->update([
                'payment_method' => $paymentIntent->payment_method ?? null,
            ]);
            
            \Log::info('Payment succeeded', [
                'payment_id' => $payment->id,
                'payment_intent' => $paymentIntent->id
            ]);
            
            // TODO: Send confirmation email here
        }
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed($paymentIntent)
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();
        
        if ($payment) {
            $payment->markAsFailed($paymentIntent->last_payment_error->message ?? 'Payment failed');
            
            \Log::info('Payment failed', [
                'payment_id' => $payment->id,
                'payment_intent' => $paymentIntent->id
            ]);
        }
    }


    public function handlePaymentCanceled($id)
    {
        try{
        $payment = Payment::where('payment_intent_id', $id)->first();
        if ($payment) {
            $payment->markAsCanceled();
        }
        return response()->json(['success' => true,
                                'message'=>'Payment canceled successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

    }

   
    public function getPublishableKey()
    {
        return response()->json([
            'publishable_key' => config('services.stripe.key'),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/payments",
     *     tags={"Payment"},
     *     summary="Get all payments (Admin/SuperAdmin only)",
     *     description="Retrieves all payment records from the database. Only accessible by authenticated admins.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Payments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="phone_number", type="string", example="+61400000000"),
     *                     @OA\Property(property="invoice_number", type="string", example="INV-2025-001"),
     *                     @OA\Property(property="description", type="string", example="Payment for premium service"),
     *                     @OA\Property(property="amount", type="number", example=100.50),
     *                     @OA\Property(property="currency", type="string", example="aud"),
     *                     @OA\Property(property="status", type="string", example="succeeded"),
     *                    
     *                     @OA\Property(property="paid_at", type="string", format="date-time", example="2025-10-14T10:30:00.000000Z"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-14T10:25:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-14T10:30:00.000000Z")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Payments retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function getAllPayments(){
        $payments = Payment::all()->makeHidden(['payment_intent_id', 'client_secret']);

        return response()->json([
            'success' => true,
            'data' => $payments,
            'message' => 'Payments retrieved successfully'
        ]);
    }


   
    


}