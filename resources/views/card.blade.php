<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán bằng thẻ Credit Card</title>
    <script type="module" src="https://multipay.komoju.com/fields.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f3f4f6; }
        .container { width: 100%; max-width: 960px; margin: 32px; }
        .card { border: 1px solid #ddd; border-radius: 12px; padding: 40px; box-shadow: 0 4px 12px rgba(0,0,0,.05); background: #fff; }
        .field { margin-bottom: 22px; }
        .field label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 15px; }
        .field input, .field select, .field textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .actions { text-align: right; }
        .btn { background: #2563eb; color: #fff; border: none; padding: 14px 28px; border-radius: 6px; cursor: pointer; font-size: 16px; width: 100%; }
        .btn:hover { background: #1d4ed8; }
        .btn:disabled { background: #93c5fd; cursor: not-allowed; }
        .error { color: #b91c1c; margin-bottom: 16px; }
        .success { color: #15803d; margin-bottom: 16px; font-weight: 600; padding: 12px; background: #dcfce7; border-radius: 6px; }
        .saved-card { border: 1px solid #ddd; border-radius: 8px; padding: 16px; background: #f9fafb; margin-bottom: 22px; }
        .saved-card .email { color: #6b7280; font-size: 13px; }
        .switch-link { text-align: center; margin-top: 8px; }
        .switch-link a { color: #2563eb; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Nhập thông tin thẻ của bạn</h1>

            @if (session('success'))
                <div class="success">
                    {{ session('success') }}
                </div>
            @endif

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

            <form id="payment-form" action="{{ route('payment.card') }}" method="POST" style="{{ !empty($savedCustomer) ? 'display:none;' : '' }}">
                @csrf
                <input type="hidden" name="user_id" value="{{ $userId }}">

                <div class="field">
                    <label>Thông tin thẻ</label>
                    <!-- Web Component của Komoju: Sẽ tự động sinh ô nhập thẻ an toàn -->
                    <komoju-fields
                      session-id="{{ $sessionId }}"
                      publishable-key="{{ $publishableKey }}"
                    ></komoju-fields>
                </div>

                <div class="actions">
                    <button class="btn" type="submit" id="submit-btn">Lưu thẻ này</button>
                </div>
            </form>
        </div>
    </div>

    <script type="module">
    </script>
</body>
</html>
