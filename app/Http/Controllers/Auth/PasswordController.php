<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(6)],
        ]);

        // 改密后下线其他设备，当前会话保持登录（须在更新密码前校验旧密码）
        \Illuminate\Support\Facades\Auth::logoutOtherDevices($validated['current_password']);

        $request->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', '密码更新成功');
    }
}