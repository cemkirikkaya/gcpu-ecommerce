<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink([
            'email' => $request->string('email')->toString(),
        ]);

        return response()->json([
            'message' => 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.',
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => $this->resetStatusMessage($status),
            ], 422);
        }

        return response()->json([
            'message' => 'Şifreniz sıfırlandı. Yeni şifrenizle giriş yapabilirsiniz.',
        ]);
    }

    private function resetStatusMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => 'Sıfırlama bağlantısı geçersiz veya süresi dolmuş.',
            Password::INVALID_USER => 'Bu e-posta adresiyle kayıtlı bir hesap bulunamadı.',
            default => 'Şifre sıfırlanamadı. Lütfen tekrar deneyin.',
        };
    }
}
