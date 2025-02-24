<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * دریافت لیست کاربران عادی
     */
    public function index(Request $request)
    {
        $query = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'vendor');
        });

        if ($request->has('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        $perPage = $request->input('per_page', 10);
        $users = $query->paginate($perPage);

        return response()->json([
            'data'  => $users->items(),
            'meta'  => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last'  => $users->url($users->lastPage()),
                'prev'  => $users->previousPageUrl(),
                'next'  => $users->nextPageUrl(),
            ],
        ]);
    }
}
