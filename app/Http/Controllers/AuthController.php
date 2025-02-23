<?php

namespace App\Http\Controllers;

use App\Exceptions\OtpRequestTooSoonException;
use App\Exceptions\OtpSendingFailedException;
use Illuminate\Http\Request;
use App\Services\OtpService;
use App\Models\User;
use App\ValueObjects\PhoneNumber;
use App\ValueObjects\OtpCode;
use Illuminate\Support\Facades\Session;
use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;

class AuthController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function sendOtp(Request $request)
    {
        try {
            $phone = new PhoneNumber($request->phone);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            $this->otpService->sendOtp($phone->getPhone());
            return response()->json(['message' => 'کد تأیید ارسال شد.'], 200);
        } catch (OtpRequestTooSoonException $e) {
            return response()->json(['message' => 'لطفاً دو دقیقه صبر کنید و سپس دوباره درخواست دهید.'], 429);
        } catch (OtpSendingFailedException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function verifyOtp(Request $request)
    {
        try {

            $phoneObj = new PhoneNumber(Session::get('phone'));

            $otpCodeObj = new OtpCode($request->input('code'));

            $this->otpService->verifyOtp($phoneObj->getPhone(), $otpCodeObj->getCode());

            $user = User::firstOrCreate(['phone' => $phoneObj->getPhone()]);

            $token = $user->createToken('AUTH')->plainTextToken;

            return response()->json(['token' => $token], 200);

        } catch (OtpExpiredException $e) {
            return response()->json(['message' => 'کد تأیید منقضی شده است.'], 422);
        } catch (OtpInvalidException $e) {
            return response()->json(['message' => 'کد تأیید نامعتبر است.'], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => 'فرمت شماره موبایل یا کد تأیید نامعتبر است.'], 422);
        }
    }


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'شما خارج شدید.']);
    }
}
