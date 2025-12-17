
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

public function charge(Request $request)
{
    $request->validate([
        'amount' => 'required|numeric',
        'opaqueDataValue' => 'required',
        'opaqueDataDescriptor' => 'required',
    ]);

    $merchantAuth = new AnetAPI\MerchantAuthenticationType();
    $merchantAuth->setName(config('services.authorize.login_id'));
    $merchantAuth->setTransactionKey(config('services.authorize.transaction_key'));

    $opaqueData = new AnetAPI\OpaqueDataType();
    $opaqueData->setDataDescriptor($request->opaqueDataDescriptor);
    $opaqueData->setDataValue($request->opaqueDataValue);

    $paymentType = new AnetAPI\PaymentType();
    $paymentType->setOpaqueData($opaqueData);

    $transactionRequest = new AnetAPI\TransactionRequestType();
    $transactionRequest->setTransactionType("authCaptureTransaction");
    $transactionRequest->setAmount($request->amount);
    $transactionRequest->setPayment($paymentType);

    $request = new AnetAPI\CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuth);
    $request->setTransactionRequest($transactionRequest);

    $controller = new AnetController\CreateTransactionController($request);
    $response = $controller->executeWithApiResponse(
        config('services.authorize.env') === 'sandbox'
            ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
            : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
    );

    if ($response && $response->getMessages()->getResultCode() === "Ok") {
        return response()->json([
            'success' => true,
            'transaction_id' => $response->getTransactionResponse()->getTransId()
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Payment failed'
    ], 400);
}
