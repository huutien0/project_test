<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận thanh toán</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; }
        .container { max-width: 720px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,.05); }
        .details { margin-top: 18px; }
        .details dt { font-weight: 700; margin-top: 12px; }
        .details dd { margin: 0 0 12px 0; }
        .btn { display: inline-block; background: #2563eb; color: #fff; padding: 12px 20px; border-radius: 6px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Xác nhận thanh toán</h1>
            <p>Phương thức: <strong>{{ $details['provider'] }}</strong></p>
            <p>Số tiền: <strong>{{ number_format($amount, 2) }} {{ $currency }}</strong></p>
            <p>Mã giao dịch: <strong>{{ $transactionId }}</strong></p>

            <dl class="details">
                <dt>Chi tiết thanh toán</dt>
                <dd>{{ $description }}</dd>

                @foreach ($details as $key => $value)
                    @if (!in_array($key, ['provider', 'transaction_id', 'amount', 'currency', 'raw_response']))
                        <dt>{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                        <dd>{{ is_array($value) ? implode(', ', $value) : (is_string($value) || is_numeric($value) ? $value : json_encode($value)) }}</dd>
                    @endif
                @endforeach
            </dl>

            @if ($paymentMethod === 'bank_transfer')
                @php
                    $raw = $details['raw_response'] ?? [];
                    $paymentDetails = $raw['payment_details'] ?? [];
                    // Komoju bank transfer details structure can vary, typically includes these fields:
                    $bankName = $paymentDetails['bank_name'] ?? 'Vietcombank (Demo)';
                    $branchName = $paymentDetails['branch_name'] ?? '';
                    $accountNumber = $paymentDetails['account_number'] ?? '0123456789';
                    $accountName = $paymentDetails['account_name'] ?? 'CÔNG TY TNHH DEMO';
                    $accountType = $paymentDetails['account_type'] ?? '';
                    
                    $fullBankName = trim($bankName . ($branchName ? ' - ' . $branchName : ''));
                @endphp
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 24px; margin: 24px 0;">
                    <h2 style="margin-top: 0; color: #166534; font-size: 1.25rem; border-bottom: 2px solid #bbf7d0; padding-bottom: 12px; text-align: center;">Thông Tin Chuyển Khoản Ngân Hàng</h2>
                    
                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 1rem; margin-top: 16px;">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #bbf7d0; padding-bottom: 8px;">
                            <span style="color: #15803d;">Ngân hàng thụ hưởng:</span>
                            <strong style="color: #166534; text-align: right;">{{ $fullBankName }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #bbf7d0; padding-bottom: 8px;">
                            <span style="color: #15803d;">Số tài khoản:</span>
                            <strong style="font-size: 1.1em; color: #166534; letter-spacing: 1px;">
                                {{ $accountNumber }}
                                @if($accountType) <span style="font-size: 0.8em; color: #15803d;">({{ $accountType }})</span> @endif
                            </strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #bbf7d0; padding-bottom: 8px;">
                            <span style="color: #15803d;">Chủ tài khoản:</span>
                            <strong style="color: #166534; text-transform: uppercase; text-align: right;">{{ $accountName }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #bbf7d0; padding-bottom: 8px;">
                            <span style="color: #15803d;">Số tiền cần chuyển:</span>
                            <strong style="color: #dc2626; font-size: 1.1em;">{{ number_format($amount, 2) }} {{ $currency }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 8px;">
                            <span style="color: #15803d;">Nội dung chuyển khoản:</span>
                            <strong style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 4px; font-family: monospace; font-size: 1.2em;">{{ $transactionId }}</strong>
                        </div>
                    </div>
                    
                    <div style="margin-top: 16px; padding: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px;">
                        <p style="margin: 0; color: #991b1b; font-size: 0.9em; text-align: center;">
                            <strong>⚠️ Lưu ý quan trọng:</strong> Vui lòng nhập chính xác <strong>Nội dung chuyển khoản</strong> và <strong>Số tiền</strong> để hệ thống có thể xác nhận thanh toán tự động nhanh nhất.
                        </p>
                    </div>
                </div>
            @endif

            @if (isset($details['checkout_url']))
                <p><a class="btn" href="{{ $details['checkout_url'] }}">Mở trang thanh toán</a></p>
            @endif

            <p><a class="btn" href="{{ route('payment.form') }}">Quay lại</a></p>
        </div>
    </div>
</body>
</html>
