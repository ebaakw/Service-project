<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payments\MyFatoorahPaymentSettlementService;
use App\Services\Payments\MyFatoorahWebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
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

        $payload = $request->json()->all();
        if ($payload === [] && $raw !== '') {
            $decoded = json_decode($raw, true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        if (! $this->webhookVerifier->isValid($signature, $raw, $payload)) {
            Log::warning('myfatoorah.webhook.invalid_signature', [
                'has_signature' => is_string($signature) && $signature !== '',
                'body_bytes' => strlen($raw),
                'event' => data_get($payload, 'Event.Name'),
            ]);

            return response('Invalid signature', 401);
        }

        $data = is_array($payload['Data'] ?? null) ? $payload['Data'] : $payload;
        $invoice = is_array($data['Invoice'] ?? null) ? $data['Invoice'] : [];

        $invoiceId = (string) (
            $data['InvoiceId']
            ?? $data['InvoiceID']
            ?? $invoice['Id']
            ?? ''
        );

        $customerReference = (string) (
            $data['CustomerReference']
            ?? $invoice['ExternalIdentifier']
            ?? ''
        );

        Log::info('myfatoorah.webhook.accepted', [
            'invoice_id' => $invoiceId !== '' ? $invoiceId : null,
            'customer_reference' => $customerReference !== '' ? $customerReference : null,
            'transaction_status' => data_get($data, 'Transaction.Status')
                ?? data_get($data, 'TransactionStatus'),
            'event' => data_get($payload, 'Event.Name'),
        ]);

        try {
            if ($customerReference !== '') {
                $this->settlementService->settleByPaymentUlid($customerReference);
            } elseif ($invoiceId !== '') {
                $this->settlementService->settleByInvoiceId($invoiceId);
            } else {
                Log::warning('myfatoorah.webhook.missing_identifiers', [
                    'event' => data_get($payload, 'Event.Name'),
                ]);

                return response('Missing invoice identifiers', 422);
            }
        } catch (RuntimeException $e) {
            if (str_contains(strtolower($e->getMessage()), 'not paid yet')) {
                Log::info('myfatoorah.webhook.ignored_pending', [
                    'invoice_id' => $invoiceId !== '' ? $invoiceId : null,
                    'customer_reference' => $customerReference !== '' ? $customerReference : null,
                    'message' => $e->getMessage(),
                ]);

                return response('Ignored pending', 200);
            }

            Log::warning('myfatoorah.webhook.settlement_failed', [
                'invoice_id' => $invoiceId !== '' ? $invoiceId : null,
                'customer_reference' => $customerReference !== '' ? $customerReference : null,
                'message' => $e->getMessage(),
            ]);

            return response($e->getMessage(), 422);
        } catch (Throwable $e) {
            report($e);
            Log::error('myfatoorah.webhook.settlement_error', [
                'invoice_id' => $invoiceId !== '' ? $invoiceId : null,
                'customer_reference' => $customerReference !== '' ? $customerReference : null,
                'exception' => $e::class,
            ]);

            return response('Settlement error', 500);
        }

        Log::info('myfatoorah.webhook.settled', [
            'invoice_id' => $invoiceId !== '' ? $invoiceId : null,
            'customer_reference' => $customerReference !== '' ? $customerReference : null,
        ]);

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
