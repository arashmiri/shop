<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereHas('roles', function ($q) {
            $q->where('name', 'vendor');
        });

        if ($request->has('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        // دریافت تعداد موردنظر برای نمایش
        $perPage = $request->input('per_page', 10); // مقدار پیش‌فرض: 10

        $vendors = $query->paginate($perPage);

        return response()->json([
            'data'  => $vendors->items(),
            'meta'  => [
                'current_page' => $vendors->currentPage(),
                'last_page'    => $vendors->lastPage(),
                'per_page'     => $vendors->perPage(),
                'total'        => $vendors->total(),
            ],
            'links' => [
                'first' => $vendors->url(1),
                'last'  => $vendors->url($vendors->lastPage()),
                'prev'  => $vendors->previousPageUrl(),
                'next'  => $vendors->nextPageUrl(),
            ],
        ]);
    }

    /**
     * ارتقای یک کاربر به فروشنده
     */
    public function upgradeToVendor(Request $request)
    {
        // اعتبارسنجی ورودی
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255', // اصلاح از store_name به name
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // دریافت کاربر
        $user = User::find($request->user_id);

        // بررسی اینکه کاربر قبلاً فروشنده نشده باشد
        if ($user->hasRole('vendor')) {
            return response()->json(['error' => 'این کاربر قبلاً فروشنده شده است.'], 400);
        }

        // اختصاص نقش فروشنده به کاربر
        $user->assignRole('vendor');

        // ایجاد رکورد در جدول vendors
        $vendor = Vendor::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'balance' => 0.00,
            'admin_created_by' => auth()->id(), // مقدار صحیح: ID کاربر لاگین شده
        ]);


        return response()->json(['message' => 'کاربر با موفقیت به فروشنده ارتقا یافت.', 'vendor' => $vendor], 201);
    }


}
