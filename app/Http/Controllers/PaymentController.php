<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Endpoint para procesar pagos con EasyMoney.
     */
    public function payEasyMoney(Request $request)
    {
        return response()->json(
            $this->paymentService->processEasyMoney($request->amount, $request->currency)
        );
    }

    /**
     * Endpoint para procesar pagos con SuperWalletz.
     */
    public function paySuperWalletz(Request $request)
    {
        return response()->json(
            $this->paymentService->processSuperWalletz($request->amount, $request->currency, $request->description ?? '')
        );
    }

    /**
     * Endpoint para manejar webhooks de confirmación de SuperWalletz.
     */
    public function superWalletzCallback(Request $request)
    {
        return response()->json(
            $this->paymentService->handleWebhook($request->transaction_id, $request->status)
        );
    }
}
