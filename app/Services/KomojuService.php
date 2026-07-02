<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KomojuService
{
    private string $secretKey;

    private string $webhookSecret;

    public function __construct()
    {
        $this->secretKey = config('services.stripe_komoju.secret_key') ?? '';
        $this->webhookSecret = config('services.stripe_komoju.webhook_secret') ?? '';
    }

    /**
     * Tạo Komoju session cho bank transfer.
     *
     * @return array{session_id: string, session_url: string}
     *
     * @throws \RuntimeException
     */
    public function createBankTransfer(
        float $amount,
        string $currency,
        string $email,
        string $familyName,
        string $givenName,
        string $phone,
        ?string $familyNameKana = null,
        ?string $givenNameKana = null,
    ): array {
        return $this->createPayment(
            $amount,
            $currency,
            ['bank_transfer'],
            [
                'type' => 'bank_transfer',
                'email' => $email,
                'family_name' => $familyName,
                'given_name' => $givenName,
                'family_name_kana' => $familyNameKana,
                'given_name_kana' => $givenNameKana,
                'phone' => $phone,
            ],
            null
        );
    }

    public function createPayPay($data)
    {
        return $this->createPayment(
            $data['amount'],
            $data['currency'],
            ['paypay'],
            [
                'type' => 'paypay',
                'email' => $data['customer_email'] ?? '',
                'family_name' => $data['family_name'] ?? '',
                'given_name' => $data['given_name'] ?? '',
            ],
            route('payment.paypay.view'),
        );
    }


    /**
     * Tạo Komoju session cho một hoặc nhiều payment type tùy chọn
     * (bank_transfer, konbini, paypay, credit_card, ...).
     *
     * @param  string[]  $paymentTypes
     * @return array{session_id: string, session_url: string}
     *
     * @throws \RuntimeException
     */
    public function createPayment(
        float $amount,
        string $currency,
        array $paymentTypes,
        ?array $paymentDetails = null,
        ?string $returnUrl,
    ): array {
        if ($this->secretKey === '') {
            throw new \RuntimeException('Komoju secret key is not configured.');
        }

        $payload = [
            'amount' => (int) round($amount),
            'currency' => $currency,
            "tax" => 0,
            'payment_types' => $paymentTypes,
        ];

        if ($paymentDetails !== null) {
            $payload['payment_details'] = $paymentDetails;
        }

        if ($returnUrl !== null) {
            $payload['return_url'] = $returnUrl;
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->post('https://komoju.com/api/v1/payments', $payload);

        if (! $response->successful()) {
            Log::error('Komoju session creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException(
                'Failed to create Komoju session: '.($response->json('error.message') ?? $response->body())
            );
        }

        return $response->json();
    }

    /**
     * Verify webhook signature từ Komoju.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if ($this->webhookSecret === '') {
            Log::warning('Komoju webhook secret is not configured, skipping verification.');

            return false;
        }

        $computedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($computedSignature, $signature);
    }
}
