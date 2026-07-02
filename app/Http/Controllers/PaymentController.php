<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\KomojuService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function showForm()
    {
        return view('payment');
    }

    public function checkout(Request $request, KomojuService $komojuService)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'description' => 'nullable|string|max:255',
            'customer_email' => 'required_if:payment_method,bank_transfer|nullable|email|max:255',
            'customer_phone' => 'required_if:payment_method,bank_transfer|nullable|string|max:32',
            'family_name' => 'required_if:payment_method,bank_transfer|nullable|string|max:255',
            'given_name' => 'required_if:payment_method,bank_transfer|nullable|string|max:255',
            'family_name_kana' => 'required_if:payment_method,bank_transfer|nullable|string|max:255',
            'given_name_kana' => 'required_if:payment_method,bank_transfer|nullable|string|max:255',
        ]);

        $amount = (float) $validated['amount'];
        $currency = strtoupper($validated['currency']);
        $description = $validated['description'] ?? 'Thanh toán dịch vụ';
        $customerEmail = $validated['customer_email'] ?? null;
        $customerPhone = $validated['customer_phone'] ?? null;
        $familyName = $validated['family_name'] ?? null;
        $givenName = $validated['given_name'] ?? null;
        $familyNameKana = $validated['family_name_kana'] ?? null;
        $givenNameKana = $validated['given_name_kana'] ?? null;
        $data = $this->buildBankTransferDetails(
            $amount,
            $currency,
            $description, 
            $customerEmail, 
            $customerPhone, 
            $komojuService,
            $familyName,
            $givenName,
            $familyNameKana,
            $givenNameKana,
        );
        if(empty($data['checkout_url'])) {
            return view('payment');
        }
        return redirect()->to($data['checkout_url']);
    }

    protected function buildBankTransferDetails(
        float $amount,
        string $currency,
        string $description,
        ?string $customerEmail,
        ?string $customerPhone,
        KomojuService $komojuService,
        ?string $familyName,
        ?string $givenName,
        ?string $familyNameKana,
        ?string $givenNameKana,
    ): array {
        try {
            $tranferbank = $komojuService->createBankTransfer(
                $amount,
                $currency,
                $customerEmail,
                $familyName,
                $givenName,
                (string) $customerPhone,
                $familyNameKana,
                $givenNameKana,
            );
            Payment::create([
                'komoju_payment_id' => $tranferbank['id'],
                'payment_method' => 'bank_transfer',
                'amount' => $amount,
                'tax' => $tranferbank['tax'],
                'total' => $tranferbank['total'],
                'currency' => $currency,
                'status' => Payment::STATUS_PENDING,
                'description' => $description,
                'payment_details' => $tranferbank['payment_details'],
            ]);

            return [
                'provider' => 'Bank Transfer',
                'status' => 'pending',
                'checkout_url' => $tranferbank['payment_details']['instructions_url'],
                'amount' => $amount,
                'currency' => $currency,
                'tax' => $tranferbank['tax'],
                'total' => $tranferbank['total'],
                'payment_details' => $tranferbank['payment_details'],
            ];
        } catch (\Exception $e) {
            dd($e->getMessage());
            return [
                'provider' => 'Bank Transfer',
                'status' => 'failed',
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency,
            ];
        }
    }

    public function showPayPay()
    {
        return view('paypay');
    }

    public function paypay(Request $request, KomojuService $komojuService)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'customer_email' => 'nullable|email|max:255',
            'family_name' => 'nullable|string|max:255',
            'given_name' => 'nullable|string|max:255',
        ]);
        $data = $this->createPayPay($validated, $komojuService);
        if(empty($data['checkout_url'])) {
            return view('paypay');
        }
        return redirect()->to($data['checkout_url']);
    }

    public function createPayPay($data, KomojuService $komojuService)
    {
        try{
            $data['amount'] = (float) $data['amount'];
            $data['currency'] = strtoupper($data['currency']);
            $tranferbank = $komojuService->createPayPay($data);
            Payment::create([
                'komoju_payment_id' => $tranferbank['id'],
                'payment_method' => 'paypay',
                'amount' => $tranferbank['amount'],
                'tax' => $tranferbank['tax'],
                'total' => $tranferbank['total'],
                'currency' => $tranferbank['currency'],
                'status' => Payment::STATUS_PENDING,
                'payment_details' => $tranferbank['payment_details'],
            ]);
            return [
                'provider' => 'PayPay',
                'status' => 'pending',
                'checkout_url' => $tranferbank['payment_details']['redirect_url'],
                'amount' => $tranferbank['amount'],
                'currency' => $tranferbank['currency'],
                'tax' => $tranferbank['tax'],
                'total' => $tranferbank['total'],
                'payment_details' => $tranferbank['payment_details'],
            ];
        }
        catch (\Exception $e) {
            dd($e->getMessage());
            return [
                'provider' => 'PayPay',
                'status' => 'failed',
                'error' => $e->getMessage(),
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ];
        }
    }

    public function showCard()
    {
        return view('card');
    }

    public function card(Request $request, KomojuService $komojuService)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'customer_email' => 'nullable|email|max:255',
            'family_name' => 'nullable|string|max:255',
            'given_name' => 'nullable|string|max:255',
            'komoju_token' => 'required|string'
        ]);
        $data = $this->createCard($validated, $komojuService);
        if(empty($data['checkout_url'])) {
            return view('card');
        }
        return redirect()->to($data['checkout_url']);
    }

    public function createCard($data, KomojuService $komojuService)
    {
        try{
            $data['amount'] = (float) $data['amount'];
            $data['currency'] = strtoupper($data['currency']);
            $tranferbank = $komojuService->createCard($data);
            Payment::create([
                'komoju_payment_id' => $tranferbank['id'],
                'payment_method' => 'card',
                'amount' => $tranferbank['amount'],
                'tax' => $tranferbank['tax'],
                'total' => $tranferbank['total'],
                'currency' => $tranferbank['currency'],
                'status' => Payment::STATUS_PENDING,
                'payment_details' => $tranferbank['payment_details'],
            ]);
            return [
                'provider' => 'Card',
                'status' => 'pending',
                'checkout_url' => $tranferbank['payment_details']['redirect_url'],
                'amount' => $tranferbank['amount'],
                'currency' => $tranferbank['currency'],
                'tax' => $tranferbank['tax'],
                'total' => $tranferbank['total'],
                'payment_details' => $tranferbank['payment_details'],
            ];
        }
        catch (\Exception $e) {
            dd($e->getMessage());
            return [
                'provider' => 'Card',
                'status' => 'failed',
                'error' => $e->getMessage(),
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ];
        }
    }   
}
