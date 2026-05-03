<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentCompletionController extends Controller
{
    public function complete(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('complete', $payment);

        app(PaymentProcessingService::class)->markPaymentPaid($payment, [
            'source' => 'fake-gateway-test',
            'user_id' => $request->user()?->getKey(),
        ]);

        return response()->json(['ok' => true]);
    }
}
