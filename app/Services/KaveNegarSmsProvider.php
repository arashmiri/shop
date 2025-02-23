<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KaveNegarSmsProvider implements SmsProviderInterface
{

    public function sendOtp(string $phone, string $otp): bool
    {
        $url = env('KAVENEGAR_API_URL') . '/' . env('KAVENEGAR_API_KEY') . '/verify/lookup.json';

        $response = Http::get($url, [
            'receptor' => $phone,
            'token'    => $otp,
            'template' => 'verify'
        ]);

        // **ثبت خطا در لاگ لاراول**
        if (!$response->successful()) {
            Log::error('KaveNegar API Error:', [
                'url'      => $url,
                'phone'    => $phone,
                'otp'      => $otp,
                'response' => $response->json()
            ]);
        }

        return $response->successful();
    }

}
