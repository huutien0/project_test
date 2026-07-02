<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message</title>
</head>
<body>
    <h1>Gửi tin nhắn</h1>

    @if (session('status'))
        <div style="color: green; margin-bottom: 1rem;">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="color: red; margin-bottom: 1rem;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
n            </ul>
        </div>
    @endif

    <form action="{{ route('send-message.submit') }}" method="POST">
        @csrf

        <div>
            <label for="message">Tin nhắn</label><br>
            <textarea id="message" name="message" rows="5" cols="50">{{ old('message') }}</textarea>
        </div>

        <div style="margin-top: 1rem;">
            <button type="submit">Gửi</button>
        </div>
    </form>
</body>
</html>
