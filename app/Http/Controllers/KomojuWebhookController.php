<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\KomojuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KomojuWebhookController extends Controller
{
    public function handle(Request $request, KomojuService $komojuService): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Komoju-Signature', '');

        if (! $komojuService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Komoju webhook: invalid signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $data = $request->json()->all();
        $eventType = $data['type'] ?? '';

        Log::info('Komoju webhook received', [
            'type' => $eventType,
            'payment_id' => $data['data']['id'] ?? null,
        ]);

        return match ($eventType) {
            'payment.captured' => $this->handlePaymentCaptured($data),
            'payment.failed' => $this->handlePaymentFailed($data),
            'payment.expired' => $this->handlePaymentExpired($data),
            'payment.refunded' => $this->handlePaymentRefunded($data),
            default => $this->handleUnknownEvent($eventType),
        };
    }

    private function handlePaymentCaptured(array $data): JsonResponse
    {
        return $this->updatePaymentStatus($data, Payment::STATUS_CAPTURED);
    }

    private function handlePaymentFailed(array $data): JsonResponse
    {
        return $this->updatePaymentStatus($data, Payment::STATUS_FAILED);
    }

    private function handlePaymentExpired(array $data): JsonResponse
    {
        return $this->updatePaymentStatus($data, Payment::STATUS_EXPIRED);
    }

    private function handlePaymentRefunded(array $data): JsonResponse
    {
        return $this->updatePaymentStatus($data, Payment::STATUS_REFUNDED);
    }

    private function handleUnknownEvent(string $eventType): JsonResponse
    {
        Log::info('Komoju webhook: unhandled event type', ['type' => $eventType]);

        return response()->json(['status' => 'ignored']);
    }

    private function updatePaymentStatus(array $data, string $newStatus): JsonResponse
    {
        $paymentData = $data['data'] ?? [];
        $komojuPaymentId = $paymentData['id'] ?? null;

        if (!$komojuPaymentId) {
            Log::error('Komoju webhook: missing identifiers', ['data' => $paymentData]);

            return response()->json(['error' => 'Missing payment identifiers'], 422);
        }

        $payment = $this->findPaymentUpdate($komojuPaymentId);

        if (! $payment) {
            Log::warning('Komoju webhook: payment not found', [
                'komoju_payment_id' => $komojuPaymentId,
            ]);

            return response()->json(['error' => 'Payment not found'], 404);
        }

        // Idempotent: nếu đã ở trạng thái cuối thì skip
        if ($payment->status === $newStatus) {
            Log::info('Komoju webhook: payment already in target status', [
                'transaction_id' => $payment->transaction_id,
                'status' => $newStatus,
            ]);

            return response()->json(['status' => 'already_processed']);
        }

        $payment->update([
            'status' => $newStatus,
            'komoju_payment_id' => $komojuPaymentId,
            'provider_response' => $paymentData,
        ]);

        Log::info('Komoju webhook: payment status updated', [
            'transaction_id' => $payment->transaction_id,
            'old_status' => $payment->getOriginal('status'),
            'new_status' => $newStatus,
        ]);

        return response()->json(['status' => 'ok']);
    }

    private function findPaymentUpdate(?string $komojuPaymentId): ?Payment
    {
        $payment = Payment::query()->where('komoju_payment_id', $komojuPaymentId)->first();
        if ($payment) {
            $payment->update([
                'status' => Payment::STATUS_CAPTURED,
            ]);
            return $payment;
        }
        return null;
    }
}
