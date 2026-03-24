<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use App\Mail\OtpMail;
use App\Mail\RegistrationWelcomeMail;
use App\Mail\ResetPasswordSuccessMail;
use App\Models\Otp;
use App\Models\PasswordResetToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $otpCode = (string) random_int(100000, 999999);

        Otp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => Hash::make($otpCode),
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Mail::to($user->email)->queue(new OtpMail($user, $otpCode));

        return response()->json([
            'message' => 'Registration successful. Please verify the OTP sent to your email.',
            'must_verify_otp' => true,
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'code' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        $otp = Otp::where('user_id', $user->id)
            ->where(function ($query) use ($validated) {
                $query->whereNull('email')
                    ->orWhere('email', $validated['email']);
            })
            ->whereNull('consumed_at')
            ->orderByDesc('created_at')
            ->first();

        if (! $otp) {
            return response()->json([
                'message' => 'OTP not found or already used.',
            ], 422);
        }

        if ($otp->expires_at->isPast()) {
            return response()->json([
                'message' => 'OTP has expired.',
            ], 422);
        }

        if (! Hash::check($validated['code'], $otp->code)) {
            return response()->json([
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        $otp->update(['consumed_at' => Carbon::now()]);

        $wasUnverified = ! $user->email_verified_at;
        if ($wasUnverified) {
            $user->forceFill(['email_verified_at' => Carbon::now()])->save();
            Mail::to($user->email)->queue(new RegistrationWelcomeMail($user));
        }

        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => (string) $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role ?? 'customer',
            ],
        ]);
    }

    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        Otp::where('user_id', $user->id)
            ->where('email', $user->email)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => Carbon::now()]);

        $otpCode = (string) random_int(100000, 999999);

        Otp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => Hash::make($otpCode),
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Mail::to($user->email)->queue(new OtpMail($user, $otpCode));

        return response()->json([
            'message' => 'A new OTP has been sent to your email address.',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'If the email exists, we have sent a password reset link.',
            ]);
        }

        // Generate a one-time reset token and store only a hash.
        $token = Str::random(64);
        $tokenHash = Hash::make($token);
        $expiresAt = Carbon::now()->addMinutes(60);

        PasswordResetToken::where('email', $user->email)->delete();
        PasswordResetToken::create([
            'email' => $user->email,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $frontendUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/');
        $resetUrl = $frontendUrl . '/reset-password?token=' . urlencode($token);

        Mail::to($user->email)->queue(new ForgotPasswordMail($user, $resetUrl));

        // Don't reveal whether the email exists.
        return response()->json([
            'message' => 'If the email exists, we have sent a password reset link.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
            ], 422);
        }

        $tokenRecord = PasswordResetToken::where('email', $user->email)
            ->orderByDesc('created_at')
            ->first();

        $isValid =
            $tokenRecord &&
            $tokenRecord->expires_at?->isFuture() &&
            Hash::check($validated['token'], $tokenRecord->token_hash);

        if (! $isValid) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        PasswordResetToken::where('email', $user->email)->delete();

        Mail::to($user->email)->queue(new ResetPasswordSuccessMail($user));

        return response()->json([
            'message' => 'Password reset successful.',
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials. Please check your email and password.',
            ], 401);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'message' => 'Email not verified. Please verify your OTP.',
                'requires_otp' => true,
            ], 403);
        }

        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => (string) $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role ?? 'customer',
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->token()) {
            $user->token()->revoke();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Logout Successful!',
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id' => (string) $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role ?? 'customer',
        ]);
    }
}


