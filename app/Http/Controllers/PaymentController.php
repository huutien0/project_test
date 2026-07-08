<?php

namespace App\Http\Controllers;

use App\Models\KomojuCustomer;
use App\Models\Payment;
use App\Services\KomojuService;
use Carbon\Carbon;
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
        $diff = Carbon::now()->diffInDays(Carbon::parse(@$data['payment_details']['payment_deadline'])->format('Y-m-d H:i:s'));
        dd($diff,Carbon::parse(@$data['payment_details']['payment_deadline'])->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s'));
        dd(Carbon::parse($data['payment_details']['payment_deadline'])->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s'));
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
        dd($data);
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

    public function showCard(Request $request, KomojuService $komojuService)
    {
        $session = $komojuService->createCardSession();
        logger($session);
        return view('card', [
            'savedCustomer' => null,
            'userId' => null,
            'sessionId' => $session['id'],
            'publishableKey' => config('services.stripe_komoju.api_key'),
        ]);
    }

    public function cardTokenInfo(Request $request, KomojuService $komojuService)
    {
        $validated = $request->validate([
            'komoju_token' => 'required|string',
        ]);

        try {
            $tokenInfo = $komojuService->getTokenInfo($validated['komoju_token']);

            \Illuminate\Support\Facades\Log::info('Komoju card token info', $tokenInfo);

            return response()->json(['ok' => true, 'token_info' => $tokenInfo]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Komoju card token info failed', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function card(Request $request, KomojuService $komojuService)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'customer_email' => 'required|email|max:255',
            // 'komoju_token' => 'required|string',
        ]);
dd($validated);
        try {
            $customer = $komojuService->createCustomer($validated['komoju_token'], $validated['customer_email']);

            $paymentResource = $customer['payment_details'] ?? [];

            KomojuCustomer::updateOrCreate(
                ['user_id' => $validated['user_id']],
                [
                    'email' => $validated['customer_email'],
                    'komoju_customer_id' => $customer['id'],
                    'payment_resource_id' => $paymentResource['id'] ?? null,
                    'card_brand' => $paymentResource['brand'] ?? null,
                    'card_last4' => $paymentResource['last4'] ?? null,
                ]
            );

            return redirect()->route('payment.card.view', ['user_id' => $validated['user_id']])
                ->with('success', 'Lưu thông tin thẻ thành công! Lần thanh toán sau bạn không cần nhập lại thẻ.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function chargeSavedCard(Request $request, KomojuService $komojuService)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'description' => 'nullable|string|max:255',
        ]);

        $savedCustomer = KomojuCustomer::where('user_id', $validated['user_id'])->first();

        if (! $savedCustomer) {
            return back()->withErrors(['error' => 'Không tìm thấy thẻ đã lưu. Vui lòng nhập lại thông tin thẻ.']);
        }

        try {
            $currency = strtoupper($validated['currency']);
            $payment = $komojuService->chargeCustomer(
                $savedCustomer->komoju_customer_id,
                (float) $validated['amount'],
                $currency,
                $validated['description'] ?? null,
            );

            Payment::create([
                'komoju_payment_id' => $payment['id'],
                'payment_method' => 'credit_card',
                'amount' => $validated['amount'],
                'tax' => $payment['tax'] ?? 0,
                'total' => $payment['total'] ?? $validated['amount'],
                'currency' => $currency,
                'status' => $payment['status'] === 'captured' ? Payment::STATUS_CAPTURED : Payment::STATUS_PENDING,
                'description' => $validated['description'] ?? null,
                'payment_details' => $payment['payment_details'] ?? null,
            ]);

            return back()->with('success', 'Thanh toán thành công bằng thẻ đã lưu!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
