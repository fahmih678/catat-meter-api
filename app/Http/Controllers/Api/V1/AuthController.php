<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Traits\ApiResponseTrait;

class AuthController extends Controller
{
    use ApiResponseTrait;
    /**
     * Login user and create token
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate input
            $request->validate([
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:1',
                'device_name' => 'string|nullable|max:100'
            ]);

            // Find user by email
            $user = User::where('email', $request->email)->first();

            // Check if user exists
            if (!$user) {
                return $this->notFoundResponse('Login failed - User not found');
            }

            // Check if password is correct
            if (!Hash::check($request->password, $user->password)) {
                return $this->errorResponse('Login failed - Invalid credentials', 401);
            }

            // Check if user is active (if the field exists)
            if (isset($user->is_active) && !$user->is_active) {
                return $this->forbiddenResponse('Login failed - Account is inactive');
            }

            // Delete old tokens for this device to prevent multiple sessions
            if ($request->device_name) {
                $user->tokens()->where('name', $request->device_name)->delete();
            }

            // Create new token
            $token = $user->createToken($request->device_name ?? 'mobile-app')->plainTextToken;

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'status' => $user->is_active ? 'active' : 'inactive',
                    'photo' => $user->photo_url ? asset($user->photo_url) : null,
                ],
                'pam' => [
                    'id' => $user->pam?->id,
                    'name' => $user->pam?->name,
                    'logo' => $user->pam?->logo_url ? asset($user->pam?->logo_url) : null,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Login successful');
        } catch (ValidationException $e) {
            Log::error('Login validation error', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Login error', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->errorResponse('Login failed - Internal server error', 500);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $user->getRoleNames(),
                'status' => $user->is_active ? 'active' : 'inactive',
                'photo' => $user->photo_url ? asset($user->photo_url) : null,
            ],
            'pam' => [
                'id' => $user->pam?->id,
                'name' => $user->pam?->name,
            ],
        ], 'Profile retrieved successfully');
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = User::findOrFail($request->user()->id);

            $request->validate([
                'name' => 'sometimes|string|max:50',
                'email' => [
                    'sometimes',
                    'string',
                    'email',
                    'max:150',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phone' => 'sometimes|string|max:20',
                'password' => 'sometimes|string|min:6',
                'photo' => 'sometimes|image|mimes:jpg,jpeg,png|max:5120',
            ]);

            // Update user information
            if ($request->has('name')) {
                $user->name = $request->name;
            }
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('phone')) {
                $user->phone = $request->phone;
            }

            // Update password if provided
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            if ($request->hasFile('photo')) {
                if ($user->photo_url) {
                    $oldPath = parse_url($user->photo_url, PHP_URL_PATH);
                    $oldPath = ltrim(str_replace('/storage/', '', $oldPath), '/');
                    Storage::disk('public')->delete($oldPath);
                }

                $photoUrl = $this->handleImageUpload($request->file('photo'), $user->id);

                if ($photoUrl) {
                    $user->photo_url = $photoUrl;
                }
            }
            $user->save();

            return $this->successResponse([
                'user' => [
                    'name' => $user->name,
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
                ],
            ], 'Profile updated successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update profile', 500);
        }
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request): JsonResponse
    {
        // Get current access token
        $token = $request->user()->currentAccessToken();

        if ($token) {
            // Revoke current token
            $request->user()->tokens()->where('id', $token->id)->delete();
        }

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAll(Request $request): JsonResponse
    {
        // Revoke all tokens
        $request->user()->tokens()->delete();

        return $this->successResponse(null, 'Logged out from all devices');
    }

    /**
     * Refresh token (create new token and revoke old one)
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $deviceName = $request->device_name ?? 'mobile-app';

        // Get current token and revoke it
        $currentToken = $request->user()->currentAccessToken();
        if ($currentToken) {
            $request->user()->tokens()->where('id', $currentToken->id)->delete();
        }

        // Create new token
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer'
        ], 'Token refreshed successfully');
    }

    /**
     * Check token validity
     */
    public function checkToken(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first()
            ],
            'token_name' => $request->user()->currentAccessToken()->name,
            'expires_at' => $request->user()->currentAccessToken()->expires_at
        ], 'Token is valid');
    }

    private function handleImageUpload($file, int $userId): ?string
    {
        try {
            if (!$file->isValid()) {
                return null;
            }

            // Allowed file types
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file->getClientMimeType(), $allowedMimes)) {
                return null;
            }

            // Max 5MB
            if ($file->getSize() > 5 * 1024 * 1024) {
                return null;
            }

            // Create unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = "users_{$userId}_" . time() . "_" . Str::random(10) . '.' . $extension;

            // Folder
            $directory = "users";

            // Store in /storage/app/public/users/{id}
            $path = $file->storeAs($directory, $filename, 'public');

            return $path ? Storage::url($path) : null;
        } catch (\Exception $e) {

            Log::error('Error uploading user image', [
                'error_type' => get_class($e),
                'user_id' => $userId,
                'original_filename' => $file->getClientOriginalName(),
            ]);

            return null;
        }
    }
}
