<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        try {
            $request->authenticate();

            $request->session()->regenerate();

            return response()->json([
                'message' => 'Login success',
                'data' => [
                    'token' => $request->session()->get('_token'),
                    'user' => auth()->user(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], $e->getCode());
        }
    }

    public function register(RegisterRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = User::create(array_merge($request->only('email', 'name'), [
                'password' => Hash::make($request->password),
                'role' => 'customer',
            ]));

            $customer = Customer::create([
                'user_id' => $user->id,
                'customer_name' => $request->name,
            ]);

            $customer_address = CustomerAddress::create([
                'customer_id' => $customer->id,
                'address' => $request->address
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Register success',
                'data' => null,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], $e->getCode());
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Logout success',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], $e->getCode());
        }
    }
}
