<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CustomersDataStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(private readonly CustomersDataStore $store)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $payload = $validator->validated();

        $data = $this->store->update(function (array $data) use ($payload) {
            $exists = collect($data['users'])->contains(
                static fn (array $user) => strtolower($user['email']) === strtolower($payload['email'])
            );

            if ($exists) {
                $data['_error'] = 'Email already registered.';
                return $data;
            }

            $userId = $this->store->nextId($data['users']);
            $data['users'][] = [
                'id' => $userId,
                'name' => $payload['name'],
                'email' => strtolower($payload['email']),
                'password_hash' => Hash::make($payload['password']),
                'phone' => '',
                'addresses' => [],
                'preferences' => [
                    'currency' => 'INR',
                    'language' => 'en',
                    'email_notifications' => true,
                    'sms_notifications' => false,
                ],
                'wishlist' => [],
            ];

            $token = Str::random(64);
            $data['tokens'][$token] = $userId;
            $data['_issued_token'] = $token;

            return $data;
        });

        if (isset($data['_error'])) {
            return response()->json(['message' => $data['_error']], 409);
        }

        $token = $data['_issued_token'];
        $userId = (int) $data['tokens'][$token];
        $user = collect($data['users'])->firstWhere('id', $userId);

        return response()->json([
            'message' => 'Registered successfully.',
            'token' => $token,
            'user' => $this->publicUser($user),
        ], 201);
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $email = strtolower((string) $validator->validated()['email']);

        $data = $this->store->update(function (array $data) use ($email) {
            $code = (string) random_int(100000, 999999);
            $data['otps'][$email] = [
                'code' => $code,
                'expires_at' => now()->addMinutes(10)->toIso8601String(),
            ];
            $data['_otp_code'] = $code;
            return $data;
        });

        return response()->json([
            'message' => 'OTP generated successfully.',
            'email' => $email,
            'otp' => $data['_otp_code'],
            'expires_in_minutes' => 10,
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $provider = (string) $request->input('provider', 'password');
        if ($provider === 'google') {
            return $this->googleLogin($request);
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'otp' => ['nullable', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $payload = $validator->validated();
        $email = strtolower((string) $payload['email']);

        $data = $this->store->read();
        $user = collect($data['users'])->first(static fn (array $item) => strtolower($item['email']) === $email);
        if (!$user || !Hash::check((string) $payload['password'], (string) $user['password_hash'])) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (!empty($payload['otp'])) {
            $otpRow = $data['otps'][$email] ?? null;
            if (!$otpRow || !isset($otpRow['code'], $otpRow['expires_at'])) {
                return response()->json(['message' => 'OTP not requested for this email.'], 422);
            }

            if ((string) $otpRow['code'] !== (string) $payload['otp']) {
                return response()->json(['message' => 'Invalid OTP.'], 422);
            }

            if (now()->greaterThan(Carbon::parse((string) $otpRow['expires_at']))) {
                return response()->json(['message' => 'OTP expired.'], 422);
            }

            $this->store->update(function (array $state) use ($email) {
                unset($state['otps'][$email]);
                return $state;
            });
        }

        $data = $this->store->update(function (array $state) use ($user) {
            $token = Str::random(64);
            $state['tokens'][$token] = (int) $user['id'];
            $state['_issued_token'] = $token;
            return $state;
        });

        $token = $data['_issued_token'];

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $this->publicUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = (string) $request->attributes->get('auth_token', '');

        $this->store->update(function (array $data) use ($token) {
            unset($data['tokens'][$token]);
            return $data;
        });

        return response()->json(['message' => 'Logged out successfully.']);
    }

    private function googleLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_token' => ['required', 'string', 'min:16'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $idToken = (string) $validator->validated()['id_token'];
        $hash = substr(hash('sha256', $idToken), 0, 16);
        $email = sprintf('google_%s@curatedstore.in', $hash);

        $data = $this->store->update(function (array $data) use ($email) {
            $user = collect($data['users'])->first(static fn (array $item) => strtolower($item['email']) === $email);
            if (!$user) {
                $userId = $this->store->nextId($data['users']);
                $user = [
                    'id' => $userId,
                    'name' => 'Google User',
                    'email' => $email,
                    'password_hash' => Hash::make(Str::random(32)),
                    'phone' => '',
                    'addresses' => [],
                    'preferences' => [
                        'currency' => 'INR',
                        'language' => 'en',
                        'email_notifications' => true,
                        'sms_notifications' => false,
                    ],
                    'wishlist' => [],
                ];
                $data['users'][] = $user;
            }

            $token = Str::random(64);
            $data['tokens'][$token] = (int) $user['id'];
            $data['_issued_token'] = $token;
            $data['_user_id'] = (int) $user['id'];

            return $data;
        });

        $user = collect($data['users'])->firstWhere('id', (int) $data['_user_id']);

        return response()->json([
            'message' => 'Google login successful.',
            'token' => $data['_issued_token'],
            'user' => $this->publicUser($user),
        ]);
    }

    private function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'phone' => (string) ($user['phone'] ?? ''),
        ];
    }
}
