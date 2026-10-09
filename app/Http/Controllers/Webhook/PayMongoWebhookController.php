<?php

namespace App\Http\Controllers\Webhook;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Services\GatewayPaymentReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;
use RuntimeException;

class PayMongoWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $paymentGateway,
        private readonly GatewayPaymentReconciliationService $reconciliationService
    ) {}

    /**
     * Accept one authenticated PayMongo webhook request.
     *
     * Signature verification must happen against the exact raw request body
     * before the JSON payload is trusted or processed.
     */
    public function __invoke(
        Request $request
    ): JsonResponse {
        $payload = $request->getContent();

        $signatureHeader = trim(
            (string) $request->header(
                'Paymongo-Signature',
                ''
            )
        );

        if ($signatureHeader === '') {
            return response()->json(
                [
                    'message' =>
                        'Invalid webhook signature.',
                ],
                401
            );
        }

        try {
            $signatureIsValid =
                $this->paymentGateway
                    ->verifyWebhookSignature(
                        $payload,
                        $signatureHeader
                    );
        } catch (RuntimeException) {
            /*
             * Configuration failures must not leak provider secrets or
             * internal details to the caller.
             */
            return response()->json(
                [
                    'message' =>
                        'Webhook authentication unavailable.',
                ],
                503
            );
        }

        if (! $signatureIsValid) {
            return response()->json(
                [
                    'message' =>
                        'Invalid webhook signature.',
                ],
                401
            );
        }

        try {
            $event = json_decode(
                $payload,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return response()->json(
                [
                    'message' =>
                        'Invalid webhook payload.',
                ],
                400
            );
        }

        if (! is_array($event)) {
            return response()->json(
                [
                    'message' =>
                        'Invalid webhook payload.',
                ],
                400
            );
        }

        /*
         * The reconciliation service intentionally acknowledges unsupported,
         * unknown, or permanently mismatched events without mutation.
         *
         * Genuine persistence or mandatory-audit failures are allowed to
         * propagate so the provider receives a non-2xx response and can
         * retry delivery.
         */
        $this->reconciliationService->reconcile(
            $event
        );

        return response()->json([
            'received' => true,
        ]);
    }
}
