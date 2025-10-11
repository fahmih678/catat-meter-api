<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(AuthRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();

            // Find user by email
            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Check if user is active (not soft deleted)
            if ($user->deleted_at) {
                return $this->errorResponse('User account is deactivated', 403);
            }

            // Create token with user's abilities based on roles
            $abilities = $this->getUserAbilities($user);
            $token = $user->createToken('auth-token', $abilities)->plainTextToken;

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'pam_id' => $user->pam_id,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Login successful');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Login failed: ' . $e->getMessage());
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            // Get current access token
            $token = $request->user()->currentAccessToken();

            if ($token) {
                // Revoke current token
                $request->user()->tokens()->where('id', $token->id)->delete();
            }

            return $this->successResponse(null, 'Logout successful');
        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed: ' . $e->getMessage());
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return $this->successResponse([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'pam_id' => $user->pam_id,
                'pam' => $user->pam,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'created_at' => $user->created_at,
            ], 'User profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get user profile: ' . $e->getMessage());
        }
    }

    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get current token and revoke it
            $currentToken = $request->user()->currentAccessToken();
            if ($currentToken) {
                $request->user()->tokens()->where('id', $currentToken->id)->delete();
            }

            // Create new token
            $abilities = $this->getUserAbilities($user);
            $token = $user->createToken('auth-token', $abilities)->plainTextToken;

            return $this->successResponse([
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Token refresh failed: ' . $e->getMessage());
        }
    }

    public function revokeAllTokens(Request $request): JsonResponse
    {
        try {
            // Revoke all tokens for the user
            $request->user()->tokens()->delete();

            return $this->successResponse(null, 'All tokens revoked successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to revoke tokens: ' . $e->getMessage());
        }
    }

    private function getUserAbilities(User $user): array
    {
        // Get all permissions for the user based on their roles
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        // Add role-based abilities
        $roles = $user->getRoleNames()->toArray();

        // Combine permissions and roles as abilities
        return array_merge($permissions, array_map(fn($role) => "role:$role", $roles));
    }
}
