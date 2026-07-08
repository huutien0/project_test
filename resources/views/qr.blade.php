<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán QR (PayPay)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; }
        .container { max-width: 480px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,.05); text-align: center; }
        .amount { font-size: 24px; font-weight: 700; margin-bottom: 20px; }
        .qr-image { width: 260px; height: 260px; margin: 0 auto 20px; }
        .link { display: block; margin-top: 16px; color: #2563eb; }
        .error { color: #b91c1c; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Thanh toán QR (PayPay)</h1>

            @if ($error ?? null)
                <p class="error">{{ $error }}</p>
            @else
                <p class="amount">{{ number_format($amount) }} {{ $currency }}</p>
{{urlencode($redirectUrl) }}
                <img
                    class="qr-image"
                    src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data={{ urlencode($redirectUrl) }}"
                    alt="QR code thanh toán PayPay"
                >

                <p>Mở app PayPay và quét mã QR để thanh toán.</p>
                <a class="link" href="{{ $redirectUrl }}" target="_blank" rel="noopener">Hoặc bấm vào đây nếu đang xem trên điện thoại</a>
            @endif
        </div>
    </div>
</body>
</html>
