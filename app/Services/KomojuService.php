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

    public function createCard(array $data): array
    {
        return $this->createPayment(
            (float) $data['amount'],
            $data['currency'],
            ['credit_card'],
            $data['komoju_token'],
            route('payment.card.view')
        );
    }

    /**
     * Thu tiền từ một customer đã lưu thẻ trước đó (không cần nhập lại thông tin thẻ).
     */
    public function chargeCustomer(
        string $komojuCustomerId,
        float $amount,
        string $currency,
        ?string $description = null,
    ): array {
        return $this->createPayment(
            $amount,
            $currency,
            ['credit_card'],
            [
                'type' => 'customer',
                'customer' => $komojuCustomerId,
            ],
        );
    }

    /**
     * Lấy thông tin thẻ từ Komoju API dựa trên token nhận được từ Komoju Fields.js.
     */
    public function getTokenInfo(string $token): array
    {
        if ($this->secretKey === '') {
            throw new \RuntimeException('Komoju secret key is not configured.');
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->get("https://komoju.com/api/v1/tokens/{$token}");

        if (! $response->successful()) {
            Log::error('Komoju token lookup failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException(
                'Failed to retrieve Komoju token info: '.($response->json('error.message') ?? $response->body())
            );
        }

        return $response->json();
    }

    /**
     * Tạo Komoju session để nhúng Hosted Fields (komoju-fields) ở phía client.
     * mode=customer dùng khi chỉ cần tokenize/lưu thẻ, không thu tiền ngay.
     */
    public function createCardSession(?string $email = null): array
    {
        if ($this->secretKey === '') {
            throw new \RuntimeException('Komoju secret key is not configured.');
        }

        $payload = [
            'mode' => 'customer',
            'email' => 'tien@gmail.com',
            'currency' => 'JPY',
            'payment_types' => ['credit_card'],
            'return_url' => 'https://chatgpt.com/',
        ];

        if ($email !== null) {
            $payload['email'] = $email;
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->post('https://komoju.com/api/v1/sessions', $payload);

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

    public function createCustomer(string $token, ?string $email = null): array
    {
        if ($this->secretKey === '') {
            throw new \RuntimeException('Komoju secret key is not configured.');
        }

        $payload = [
            'payment_details' => $token,
        ];

        if ($email !== null) {
            $payload['email'] = $email;
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->post('https://komoju.com/api/v1/customers', $payload);

        if (! $response->successful()) {
            Log::error('Komoju customer creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException(
                'Failed to create Komoju customer: '.($response->json('error.message') ?? $response->body())
            );
        }

        return $response->json();
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
        string|array|null $paymentDetails = null,
        ?string $returnUrl = null,
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
