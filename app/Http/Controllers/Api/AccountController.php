<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CustomersDataStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function __construct(private readonly CustomersDataStore $store)
    {
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json([
            'message' => 'Profile fetched successfully.',
            'profile' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? '',
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $authUser = $request->attributes->get('auth_user');
        $payload = $validator->validated();

        $data = $this->store->update(function (array $data) use ($authUser, $payload) {
            foreach ($data['users'] as &$user) {
                if ((int) $user['id'] !== (int) $authUser['id']) {
                    continue;
                }
                $user['name'] = $payload['name'];
                $user['phone'] = $payload['phone'] ?? '';
                break;
            }
            unset($user);

            return $data;
        });

        $user = collect($data['users'])->firstWhere('id', (int) $authUser['id']);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'profile' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? '',
            ],
        ]);
    }

    public function addresses(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json([
            'message' => 'Addresses fetched successfully.',
            'addresses' => $user['addresses'] ?? [],
        ]);
    }

    public function addAddress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'label' => ['required', 'string', 'max:50'],
            'line1' => ['required', 'string', 'max:150'],
            'line2' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:80'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:80'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $authUser = $request->attributes->get('auth_user');
        $payload = $validator->validated();

        $data = $this->store->update(function (array $data) use ($authUser, $payload) {
            foreach ($data['users'] as &$user) {
                if ((int) $user['id'] !== (int) $authUser['id']) {
                    continue;
                }

                $addresses = $user['addresses'] ?? [];
                $newId = $this->store->nextId($addresses);
                if (!empty($payload['is_default'])) {
                    foreach ($addresses as &$address) {
                        $address['is_default'] = false;
                    }
                    unset($address);
                }

                $addresses[] = [
                    'id' => $newId,
                    'label' => $payload['label'],
                    'line1' => $payload['line1'],
                    'line2' => $payload['line2'] ?? '',
                    'city' => $payload['city'],
                    'state' => $payload['state'],
                    'postal_code' => $payload['postal_code'],
                    'country' => $payload['country'],
                    'is_default' => (bool) ($payload['is_default'] ?? empty($addresses)),
                ];

                $user['addresses'] = $addresses;
                break;
            }
            unset($user);

            return $data;
        });

        $user = collect($data['users'])->firstWhere('id', (int) $authUser['id']);

        return response()->json([
            'message' => 'Address added successfully.',
            'addresses' => $user['addresses'] ?? [],
        ], 201);
    }

    public function deleteAddress(Request $request, int $id): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');

        $data = $this->store->update(function (array $data) use ($authUser, $id) {
            foreach ($data['users'] as &$user) {
                if ((int) $user['id'] !== (int) $authUser['id']) {
                    continue;
                }

                $addresses = collect($user['addresses'] ?? [])->reject(
                    static fn (array $address) => (int) $address['id'] === $id
                )->values()->all();

                if (!collect($addresses)->contains('is_default', true) && count($addresses) > 0) {
                    $addresses[0]['is_default'] = true;
                }

                $user['addresses'] = $addresses;
                break;
            }
            unset($user);

            return $data;
        });

        $user = collect($data['users'])->firstWhere('id', (int) $authUser['id']);

        return response()->json([
            'message' => 'Address deleted successfully.',
            'addresses' => $user['addresses'] ?? [],
        ]);
    }

    public function setDefaultAddress(Request $request, int $id): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');

        $data = $this->store->update(function (array $data) use ($authUser, $id) {
            foreach ($data['users'] as &$user) {
                if ((int) $user['id'] !== (int) $authUser['id']) {
                    continue;
                }

                $found = false;
                foreach ($user['addresses'] as &$address) {
                    $isCurrent = (int) $address['id'] === $id;
                    $address['is_default'] = $isCurrent;
                    if ($isCurrent) {
                        $found = true;
                    }
                }
                unset($address);

                if (!$found) {
                    $data['_error'] = 'Address not found.';
                    return $data;
                }

                break;
            }
            unset($user);

            return $data;
        });

        if (isset($data['_error'])) {
            return response()->json(['message' => $data['_error']], 404);
        }

        $user = collect($data['users'])->firstWhere('id', (int) $authUser['id']);

        return response()->json([
            'message' => 'Default address updated.',
            'addresses' => $user['addresses'] ?? [],
        ]);
    }

    public function preferences(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json([
            'message' => 'Preferences fetched successfully.',
            'preferences' => $user['preferences'] ?? [],
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'currency' => ['required', 'string', 'max:8'],
            'language' => ['required', 'string', 'max:8'],
            'email_notifications' => ['required', 'boolean'],
            'sms_notifications' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $authUser = $request->attributes->get('auth_user');
        $payload = $validator->validated();

        $data = $this->store->update(function (array $data) use ($authUser, $payload) {
            foreach ($data['users'] as &$user) {
                if ((int) $user['id'] !== (int) $authUser['id']) {
                    continue;
                }

                $user['preferences'] = [
                    'currency' => $payload['currency'],
                    'language' => $payload['language'],
                    'email_notifications' => (bool) $payload['email_notifications'],
                    'sms_notifications' => (bool) $payload['sms_notifications'],
                ];

                break;
            }
            unset($user);

            return $data;
        });

        $user = collect($data['users'])->firstWhere('id', (int) $authUser['id']);

        return response()->json([
            'message' => 'Preferences updated successfully.',
            'preferences' => $user['preferences'] ?? [],
        ]);
    }
}
