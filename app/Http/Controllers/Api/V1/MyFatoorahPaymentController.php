<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payments\MyFatoorahPaymentSettlementService;
use App\Services\Payments\MyFatoorahWebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use Throwable;

class MyFatoorahPaymentController extends Controller
{
    public function __construct(
        private readonly MyFatoorahPaymentSettlementService $settlementService,
        private readonly MyFatoorahWebhookVerifier $webhookVerifier,
    ) {}

    public function webhook(Request $request): Response
    {
        $raw = $request->getContent();
        $signature = $request->header('MyFatoorah-Signature')
            ?? $request->header('myfatoorah-signature');

        if (! $this->webhookVerifier->isValid($signature, $raw)) {
            return response('Invalid signature', 401);
        }

        $payload = $request->json()->all();
        $data = is_array($payload['Data'] ?? null) ? $payload['Data'] : $payload;

        $invoiceId = (string) (
            $data['InvoiceId']
            ?? $data['InvoiceID']
            ?? ''
        );

        $customerReference = (string) (
            $data['CustomerReference']
            ?? ''
        );

        try {
            if ($customerReference !== '') {
                $this->settlementService->settleByPaymentUlid($customerReference);
            } elseif ($invoiceId !== '') {
                $this->settlementService->settleByInvoiceId($invoiceId);
            } else {
                return response('Missing invoice identifiers', 422);
            }
        } catch (RuntimeException $e) {
            if (str_contains(strtolower($e->getMessage()), 'not paid yet')) {
                return response('Ignored pending', 200);
            }

            return response($e->getMessage(), 422);
        } catch (Throwable $e) {
            report($e);

            return response('Settlement error', 500);
        }

        return response('OK', 200);
    }

    public function callback(Request $request): RedirectResponse|JsonResponse
    {
        return $this->handleBrowserReturn($request, intendedSuccess: true);
    }

    public function error(Request $request): RedirectResponse|JsonResponse
    {
        return $this->handleBrowserReturn($request, intendedSuccess: false);
    }

    private function handleBrowserReturn(Request $request, bool $intendedSuccess): RedirectResponse|JsonResponse
    {
        $paymentUlid = (string) $request->query('payment_ulid', '');
        $paymentId = (string) ($request->query('paymentId') ?? $request->query('PaymentId') ?? '');

        $settled = false;
        $status = $intendedSuccess ? 'unknown' : 'failed';
        $orderId = null;

        try {
            if ($paymentUlid !== '') {
                $result = $this->settlementService->settleByPaymentUlid($paymentUlid);
                $settled = true;
                $status = 'paid';
                $orderId = $result['payment']->order_id;
            } elseif ($paymentId !== '') {
                $result = $this->settlementService->settleByMyFatoorahPaymentId($paymentId);
                $settled = true;
                $status = 'paid';
                $orderId = $result['payment']->order_id;
                $paymentUlid = (string) $result['payment']->ulid;
            }
        } catch (Throwable $e) {
            report($e);
            $status = $intendedSuccess ? 'pending_or_failed' : 'failed';
        }

        $redirect = $intendedSuccess
            ? (string) config('myfatoorah.success_redirect_url')
            : (string) config('myfatoorah.error_redirect_url');

        if ($redirect !== '') {
            $separator = str_contains($redirect, '?') ? '&' : '?';
            $url = $redirect.$separator.http_build_query(array_filter([
                'payment_ulid' => $paymentUlid !== '' ? $paymentUlid : null,
                'order_id' => $orderId,
                'status' => $status,
                'settled' => $settled ? '1' : '0',
            ], static fn ($v) => $v !== null && $v !== ''));

            return redirect()->away($url);
        }

        return response()->json([
            'ok' => $settled,
            'status' => $status,
            'payment_ulid' => $paymentUlid !== '' ? $paymentUlid : null,
            'order_id' => $orderId,
        ]);
    }
}
