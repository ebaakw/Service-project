<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentCompletionController extends Controller
{
    public function complete(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('complete', $payment);

        if (
            $payment->payment_type === PaymentType::FinalInvoice
            && $payment->order?->quote_accepted_at === null
        ) {
            abort(422, 'Accept the invoice before paying.');
        }

        try {
            app(PaymentProcessingService::class)->markPaymentPaid($payment, [
                'source' => 'fake-gateway-test',
                'user_id' => $request->user()?->getKey(),
            ]);
        } catch (RuntimeException $e) {
            abort(422, $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }
}
