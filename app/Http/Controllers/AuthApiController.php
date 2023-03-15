<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => 'required|email|min:10|max:500|unique:users',
            'password' => 'required|string|min:8|max:20'
        ]);

        $data = $request->only(['email', 'password']);

        User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);

        if ($token = $this->guard()->attempt($data)) {
            return $this->respondWithToken($token);
        }

         return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function me(): JsonResponse
    {
        return response()->json($this->guard()->user());
    }

    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    public function guard(): Guard
    {
        return Auth::guard();
    }
}
