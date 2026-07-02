<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; }
        .container { max-width: 720px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,.05); }
        .field { margin-bottom: 18px; }
        .field label { display: block; margin-bottom: 8px; font-weight: 600; }
        .field input, .field select, .field textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
        .actions { text-align: right; }
        .btn { background: #2563eb; color: #fff; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
        .error { color: #b91c1c; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Thanh toán</h1>
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

            <form method="POST" action="{{ route('payment.paypay') }}">
                @csrf
                <div class="field">
                    <label for="amount">Số tiền</label>
                    <input id="amount" name="amount" type="number" min="1" step="0.01" value="{{ old('amount', 1000) }}" required>
                </div>
                <div class="field">
                    <label for="currency">Loại tiền</label>
                    <input id="currency" name="currency" type="text" value="{{ old('currency', 'JPY') }}" required>
                </div>
                    <div class="field">
                    <label for="customer_email">Email khách hàng</label>
                    <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}">
                </div>
                 <div class="field">
                    <label for="family_name">family_name</label>
                    <input id="family_name" name="family_name" type="text" value="{{ old('family_name') }}">
                </div>
                <div class="field">
                    <label for="given_name">given_name</label>
                    <input id="given_name" name="given_name" type="text" value="{{ old('given_name') }}">
                </div>
                <div class="actions">
                    <button class="btn" type="submit">Tiến hành thanh toán</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
