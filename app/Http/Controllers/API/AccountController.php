<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        return response()->json([
            'user' => new UserResource($user->fresh()),
            'message' => 'Profil bilgileriniz güncellendi.',
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasPassword() && ! Hash::check($request->string('current_password')->toString(), (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Mevcut şifreniz hatalı.',
            ]);
        }

        $user->update([
            'password' => $request->string('password')->toString(),
        ]);

        return response()->json([
            'user' => new UserResource($user->fresh()),
            'message' => $user->wasChanged('password')
                ? 'Şifreniz güncellendi.'
                : 'Şifre zaten güncel.',
        ]);
    }
}
