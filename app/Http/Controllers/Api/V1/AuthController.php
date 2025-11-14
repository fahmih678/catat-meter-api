<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request)
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
                return response()->json([
                    'status' => 'error',
                    'message' => 'Authentication failed',
                    'error_code' => 'USER_NOT_FOUND',
                    'errors' => [
                        'email' => ['No account found with this email address.']
                    ],
                    'data' => null,
                    'timestamp' => now()->toISOString()
                ], 404);
            }

            // Check if password is correct
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Authentication failed',
                    'error_code' => 'INVALID_CREDENTIALS',
                    'errors' => [
                        'email' => ['The provided credentials are incorrect.']
                    ],
                    'data' => null,
                    'timestamp' => now()->toISOString()
                ], 401);
            }

            // Check if user is active (if the field exists)
            if (isset($user->is_active) && !$user->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account access denied',
                    'error_code' => 'ACCOUNT_INACTIVE',
                    'errors' => [
                        'account' => ['Your account has been deactivated. Please contact support.']
                    ],
                    'data' => null,
                    'timestamp' => now()->toISOString()
                ], 403);
            }

            // Delete old tokens for this device to prevent multiple sessions
            if ($request->device_name) {
                $user->tokens()->where('name', $request->device_name)->delete();
            }

            // Create new token
            $token = $user->createToken($request->device_name ?? 'mobile-app')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames(),
                        'pam_id' => $user->pam_id,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
                'data' => null,
                'timestamp' => now()->toISOString()
            ], 422);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Login error: ' . $e->getMessage(), [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error_code' => 'SERVER_ERROR',
                'errors' => [
                    'server' => ['An unexpected error occurred. Please try again.']
                ],
                'data' => null,
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'message' => 'User profile retrieved successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->getRoleNames(),
                    'pam_id' => $user->pam_id,
                    'photo' => $user->photo_url,
                ]
            ]
        ], 200);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
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
    public function logout(Request $request)
    {
        // Get current access token
        $token = $request->user()->currentAccessToken();

        if ($token) {
            // Revoke current token
            $request->user()->tokens()->where('id', $token->id)->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful'
        ], 200);
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAll(Request $request)
    {
        // Revoke all tokens
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out from all devices'
        ], 200);
    }

    /**
     * Refresh token (create new token and revoke old one)
     */
    public function refreshToken(Request $request)
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

        return response()->json([
            'status' => 'success',
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 200);
    }

    /**
     * Check token validity
     */
    public function checkToken(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'message' => 'Token is valid',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first()
                ],
                'token_name' => $request->user()->currentAccessToken()->name,
                'expires_at' => $request->user()->currentAccessToken()->expires_at
            ]
        ], 200);
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
            $directory = "users/{$userId}";

            // Store in /storage/app/public/users/{id}
            $path = $file->storeAs($directory, $filename, 'public');

            return $path ? Storage::url($path) : null;
        } catch (\Exception $e) {

            Log::error('Error uploading user image', [
                'user_id' => $userId,
                'original_filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
