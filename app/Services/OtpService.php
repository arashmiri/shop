<?php

namespace App\Services;

use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;
use App\Exceptions\OtpSendingFailedException;
use App\Models\OtpCode as OtpCodeModel;
use Illuminate\Support\Facades\Session;
use App\Exceptions\OtpRequestTooSoonException;

class OtpService
{
    protected $smsProvider;

    public function __construct(SmsProviderInterface $smsProvider)
    {
        $this->smsProvider = $smsProvider;
    }

    public function sendOtp(string $phone): void
    {
        $existingOtp = OtpCodeModel::where('phone', $phone)->first();

        if ($existingOtp && $existingOtp->expires_at->diffInSeconds(now()) < 120) {
            throw new OtpRequestTooSoonException();
        }

        $otp = rand(1000, 9999);

        OtpCodeModel::updateOrCreate(
            ['phone' => $phone], // شرط برای یافتن رکورد
            [
                'code' => $otp, // اطمینان از تغییر مقدار
                'expires_at' => now()->addMinutes(5)
            ]
        );

        Session::put('phone', $phone);

        if (!$this->smsProvider->sendOtp($phone, $otp)) {
            throw new OtpSendingFailedException();
        }
    }



    public function verifyOtp(string $phone, string $otp): void
    {
        $otpRecord = OtpCodeModel::where('phone', $phone)
            ->where('code', $otp)
            ->first();

        if (!$otpRecord) {
            throw new OtpInvalidException('کد تأیید نامعتبر است.');
        }

        if ($otpRecord->expires_at->isPast()) {
            throw new OtpExpiredException('کد تأیید منقضی شده است.');
        }

        $otpRecord->delete();
    }

}
