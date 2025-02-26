<?php

namespace App\Http\Controllers;

use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;
use App\Exceptions\OtpRequestTooSoonException;
use App\Exceptions\OtpSendingFailedException;
use Illuminate\Http\Request;
use App\Services\OtpService;
use App\Models\User;
use App\ValueObjects\PhoneNumber;
use App\ValueObjects\OtpCode;
use App\Services\JwtService;

class AuthController extends Controller
{
    protected $otpService;
    protected $jwtService;

    public function __construct(OtpService $otpService , JwtService $jwtService)
    {
        $this->otpService = $otpService;
        $this->jwtService = $jwtService;
    }

    public function sendOtp(Request $request)
    {
        try {
            $phone = new PhoneNumber($request->phone);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            // دریافت JWT از سرویس OTP
            $this->otpService->sendOtp($phone->getPhone());

            $jwt = $this->jwtService->generateToken($phone->getPhone());


            return response()->json([
                'message' => 'کد تأیید ارسال شد.',
                'token' => $jwt  // **ارسال JWT به کلاینت**
            ], 200);
        } catch (OtpRequestTooSoonException $e) {
            return response()->json(['message' => 'لطفاً دو دقیقه صبر کنید و سپس دوباره درخواست دهید.'], 429);
        } catch (OtpSendingFailedException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function verifyOtp(Request $request)
    {

        $request->validate([
            'token' => 'required|string',
            'code' => 'required|string|min:4|max:6',
        ]);

        try {
            $otpCodeObj = new OtpCode($request->input('code'));

            // **استخراج شماره موبایل از JWT**
            $phone = $this->jwtService->validateToken($request->input('token'));

            if (!$phone) {
                return response()->json(['message' => 'توکن نامعتبر یا منقضی شده است.'], 401);
            }

            // بررسی صحت کد OTP
            $this->otpService->verifyOtp($phone, $otpCodeObj->getCode());

            // یافتن یا ایجاد کاربر بر اساس شماره موبایل استخراج‌شده
            $user = User::firstOrCreate(['phone' => $phone]);

            // **ایجاد توکن احراز هویت کاربر (Sanctum یا هر روش دیگر)**
            $authToken = $user->createToken('AUTH')->plainTextToken;

            return response()->json(['token' => $authToken,'message' => 'با موفقیت وارد شدید']);
        } catch (OtpExpiredException $e) {
            return response()->json(['message' => 'کد تأیید منقضی شده است.'], 422);
        } catch (OtpInvalidException $e) {
            return response()->json(['message' => 'کد تأیید نامعتبر است.'], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'شما خارج شدید.']);
    }
}
