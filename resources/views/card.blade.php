<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán bằng thẻ Credit Card</title>
    <script src="https://multipay.komoju.com/fields.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f3f4f6; }
        .container { width: 100%; max-width: 960px; margin: 32px; }
        .card { border: 1px solid #ddd; border-radius: 12px; padding: 40px; box-shadow: 0 4px 12px rgba(0,0,0,.05); background: #fff; }
        .field { margin-bottom: 22px; }
        .field label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 15px; }
        .field input, .field select, .field textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
        .komoju-input { width: 100%; padding: 14px; border: 1px solid #ccc; border-radius: 6px; height: 24px; box-sizing: border-box; font-size: 16px; }
        .actions { text-align: right; }
        .btn { background: #2563eb; color: #fff; border: none; padding: 14px 28px; border-radius: 6px; cursor: pointer; font-size: 16px; width: 100%; }
        .btn:hover { background: #1d4ed8; }
        .btn:disabled { background: #93c5fd; cursor: not-allowed; }
        .error { color: #b91c1c; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Nhập thông tin thẻ của bạn</h1>

            @if ($errors->any())
                <div class="error">
                    <strong>Vui lòng sửa lỗi sau:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="card-errors" class="error"></div>

            <form id="payment-form" action="{{ route('payment.card') }}" method="POST">
                @csrf
                <input type="hidden" name="komoju_token" id="komoju-token">

                <div class="field">
                    <label for="amount">Số tiền</label>
                    <input id="amount" name="amount" type="number" min="1" step="0.01" value="{{ old('amount', 1000) }}" required>
                </div>
                <div class="field">
                    <label for="currency">Loại tiền</label>
                    <input id="currency" name="currency" type="text" value="{{ old('currency', 'JPY') }}" required>
                </div>
                <div class="field">
                    <label>Số thẻ (Card Number)</label>
                    <div id="card-number" class="komoju-input"></div>
                </div>

                <div class="field">
                    <label>Ngày hết hạn (Expiry)</label>
                    <div id="card-expiry" class="komoju-input"></div>
                </div>

                <div class="field">
                    <label>Mã bảo mật (CVC)</label>
                    <div id="card-cvc" class="komoju-input"></div>
                </div>

                <div class="actions">
                    <button class="btn" type="submit" id="submit-btn">Thanh toán ngay</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 2. Khởi tạo Komoju Fields bằng Publishable Key của bạn
        const komoju = Komoju.fields('pk_test_2b0rt0wao5gtduihon01hhza');

        // 3. Tạo và gắn các ô nhập liệu vào Form HTML
        const cardNumber = komoju.create('cardNumber');
        cardNumber.mount('#card-number');

        const cardExpiry = komoju.create('cardExpiry');
        cardExpiry.mount('#card-expiry');

        const cardCvc = komoju.create('cardCvc');
        cardCvc.mount('#card-cvc');

        // 4. Xử lý khi Submit Form
        const form = document.getElementById('payment-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Ngăn chặn form reload ngay lập tức
            
            document.getElementById('submit-btn').disabled = true;

            // Gọi Komoju để đổi thông tin thẻ lấy Token
            komoju.createToken().then(function(result) {
                if (result.error) {
                    // Hiển thị lỗi nếu thông tin thẻ sai/thiếu
                    document.getElementById('card-errors').textContent = result.error.message;
                    document.getElementById('submit-btn').disabled = false;
                } else {
                    // Điền Token nhận được vào input hidden và submit form lên Laravel
                    document.getElementById('komoju-token').value = result.token;
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>