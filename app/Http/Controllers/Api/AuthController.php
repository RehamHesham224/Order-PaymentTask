<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends ApiController
{
    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());

        $token = JWTAuth::fromUser($user);

        return self::apiBody([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ])
            ->apiMessage(__('app.messages.registration_successful'))
            ->apiCode(201)
            ->apiResponse();
    }

    public function login(LoginRequest $request)
    {
        if (! $token = Auth::guard('api')->attempt($request->only('email', 'password'))) {
            return self::apiMessage(__('app.messages.invalid_credentials'))
                ->apiCode(401)
                ->apiResponse();
        }

        return self::apiBody([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ])
            ->apiMessage(__('app.messages.login_successful'))
            ->apiResponse();
    }

    public function me()
    {
        $user = Auth::guard('api')->user();

        return self::apiBody([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ])->apiResponse();
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return self::apiMessage(__('app.messages.logout_successful'))->apiResponse();
    }
}
