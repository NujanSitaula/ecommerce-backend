<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Http\Resources\UserResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return new UserResource($user);
    }

    public function updatePhone(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $user->update(['phone' => $data['phone']]);

        return new UserResource($user);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('users', 'pending_email')->ignore($user->id),
            ],
        ]);

        $emailChanged = strcasecmp((string) $data['email'], (string) $user->email) !== 0;

        $user->name = $data['name'];
        $user->phone = $data['phone'] ?? null;

        if ($emailChanged) {
            $user->pending_email = $data['email'];
            $user->pending_email_verified_at = null;
            $this->sendEmailChangeOtp($user, $data['email']);
        }

        $user->save();

        return response()->json([
            'message' => $emailChanged
                ? 'Profile updated. Verify OTP sent to your new email to complete email change.'
                : 'Profile updated successfully.',
            'requires_email_verification' => $emailChanged,
            'pending_email' => $user->pending_email,
            'data' => (new UserResource($user))->toArray($request),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'password_confirmation' => ['required', 'string'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => [
                    'current_password' => ['Current password is incorrect.'],
                ],
            ], 422);
        }

        $user->password = $data['password'];
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    public function verifyEmailChangeOtp(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! $user->pending_email) {
            return response()->json([
                'message' => 'No pending email change request found.',
            ], 422);
        }

        $otp = Otp::where('user_id', $user->id)
            ->where('email', $user->pending_email)
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

        if (! Hash::check($data['code'], $otp->code)) {
            return response()->json([
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        $otp->update(['consumed_at' => Carbon::now()]);

        $user->email = $user->pending_email;
        $user->pending_email = null;
        $user->pending_email_verified_at = Carbon::now();
        $user->email_verified_at = Carbon::now();
        $user->save();

        return response()->json([
            'message' => 'Email address verified and updated successfully.',
            'data' => (new UserResource($user))->toArray($request),
        ]);
    }

    public function resendEmailChangeOtp(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (! $user->pending_email) {
            return response()->json([
                'message' => 'No pending email change request found.',
            ], 422);
        }

        $this->sendEmailChangeOtp($user, $user->pending_email);

        return response()->json([
            'message' => 'A new OTP has been sent to your pending email address.',
            'pending_email' => $user->pending_email,
        ]);
    }

    public function cancelEmailChange(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (! $user->pending_email) {
            return response()->json([
                'message' => 'No pending email change request found.',
            ], 422);
        }

        Otp::where('user_id', $user->id)
            ->where('email', $user->pending_email)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => Carbon::now()]);

        $user->pending_email = null;
        $user->pending_email_verified_at = null;
        $user->save();

        return response()->json([
            'message' => 'Pending email change has been cancelled.',
            'data' => (new UserResource($user))->toArray($request),
        ]);
    }

    private function sendEmailChangeOtp($user, string $email): void
    {
        Otp::where('user_id', $user->id)
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => Carbon::now()]);

        $otpCode = (string) random_int(100000, 999999);

        Otp::create([
            'user_id' => $user->id,
            'email' => $email,
            'code' => Hash::make($otpCode),
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Mail::to($email)->queue(new OtpMail($user, $otpCode));
    }
}


