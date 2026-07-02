<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trạng thái thanh toán</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; background: #f3f4f6; }
        .container { max-width: 600px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,.05); text-align: center; }
        .status-icon { font-size: 48px; margin-bottom: 16px; }
        .status-success { color: #10b981; }
        .status-pending { color: #f59e0b; }
        .status-failed { color: #ef4444; }
        .btn { display: inline-block; background: #2563eb; color: #fff; padding: 12px 20px; border-radius: 6px; text-decoration: none; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            @if($payment->isCaptured())
                <div class="status-icon status-success">✓</div>
                <h1>Thanh toán thành công</h1>
                <p>Cảm ơn bạn, giao dịch <strong>{{ $payment->transaction_id }}</strong> đã hoàn tất.</p>
            @elseif($payment->isFailed())
                <div class="status-icon status-failed">✗</div>
                <h1>Thanh toán thất bại</h1>
                <p>Rất tiếc, giao dịch <strong>{{ $payment->transaction_id }}</strong> đã thất bại hoặc hết hạn.</p>
            @else
                <div class="status-icon status-pending">⌛</div>
                <h1>Đang chờ xử lý</h1>
                <p>Giao dịch <strong>{{ $payment->transaction_id }}</strong> đang chờ xử lý.</p>
                <p>Nếu bạn đã chuyển khoản, hệ thống sẽ tự động xác nhận trong ít phút.</p>
            @endif

            <p><a class="btn" href="{{ route('payment.form') }}">Quay lại trang chủ</a></p>
        </div>
    </div>
</body>
</html>
