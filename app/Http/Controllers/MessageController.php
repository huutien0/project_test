<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Clients\MessagingApi\ApiException;

class MessageController extends Controller
{
    protected MessagingApiApi $bot;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration();
        $config->setAccessToken(config('services.line.channel_access_token'));

        $this->bot = new MessagingApiApi(new Client(), $config);
    }

    public function showForm()
    {
        return view('send-message');
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = $request->input('message');
        $to = config('services.line.to');

        if (empty($to)) {
            return back()->withErrors(['message' => 'Chưa cấu hình LINE recipient. Thêm LINE_TO vào .env với userId hợp lệ.']);
        }

        $textMessage = new TextMessage([
            'text' => $message,
        ]);

        $pushRequest = new PushMessageRequest([
            'to' => $to,
            'messages' => [$textMessage],
        ]);

        try {
            $this->bot->pushMessage($pushRequest);
        } catch (ApiException $exception) {
            Log::error('LINE Push Failed: ' . $exception->getMessage(), [
                'response_body' => $exception->getResponseBody(),
                'code' => $exception->getCode(),
                'to' => $to,
            ]);

            return back()->withErrors(['message' => 'Không gửi được LINE message. Kiểm tra lại LINE_TO và token.']);
        }

        return back()->with('status', 'Message đã gửi: ' . $message);
    }
}
