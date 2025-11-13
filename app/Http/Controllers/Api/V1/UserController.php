<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasPamFiltering;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use HasPamFiltering;

    /**
     * Display a listing of users with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            if (!RoleHelper::hasManagementAccess()) {
                return $this->forbiddenResponse('You do not have permission to access users.');
            }

            $query = User::with(['pam', 'roles']);

            // If not superadmin, only show users from their own PAM
            if (!RoleHelper::isSuperAdmin()) {
                $userPamId = RoleHelper::getUserPamId();
                $query->where('pam_id', $userPamId);
            }

            // Filter by PAM (only allow if superadmin or if PAM belongs to user's PAM)
            if ($request->has('pam_id')) {
                $requestedPamId = $request->pam_id;
                if (!RoleHelper::isSuperAdmin()) {
                    // For non-superadmin, only allow filtering their own PAM
                    $userPamId = RoleHelper::getUserPamId();
                    if ($requestedPamId != $userPamId) {
                        return $this->forbiddenResponse('Cannot filter by PAM that is not assigned to you.');
                    }
                }
                $query->where('pam_id', $requestedPamId);
            }

            // Filter by Role
            if ($request->has('role')) {
                $query->role($request->role);
            }

            // Filter by Status (based on email verification or custom status field)
            if ($request->has('status')) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Search by name, email, or phone
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'desc');

            // Validate sort_by field to prevent SQL injection
            $allowedSortFields = ['id', 'name', 'email', 'phone', 'created_at', 'updated_at'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            // Validate sort_order
            if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }

            $query->orderBy($sortBy, $sortOrder);

            // Pagination with per_page parameter (default: 15, max: 100)
            $perPage = min($request->get('per_page', 20), 100);
            $users = $query->paginate($perPage);

            // Transform the data to match required format
            $transformedUsers = $users->getCollection()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->roles->pluck('name'),
                    'status' => $user->is_active ? 'active' : 'inactive',
                    'photo' => $user->photo_url,
                    'pam' => [
                        'id' => $user->pam?->id,
                        'name' => $user->pam?->name,
                    ],
                ];
            });

            return $this->successResponse([
                'items' => $transformedUsers,
                'pagination' => [
                    'total' => $users->total(),
                    'has_more_pages' => $users->hasMorePages(),
                ]
            ], 'Users retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error retrieving users: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user.
     */
    public function show($id): JsonResponse
    {
        try {
            if (!RoleHelper::hasManagementAccess()) {
                return $this->forbiddenResponse('You do not have permission to access users.');
            }

            $user = User::with(['pam', 'roles'])->findOrFail($id);

            if (!RoleHelper::isSuperAdmin() && RoleHelper::getUserPamId() !== $user->pam_id) {
                return $this->forbiddenResponse('You are not allowed to view this user. You can only view users from your own PAM.');
            }

            return $this->successResponse([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $user->roles->pluck('name'),
                'status' => $user->is_active ? 'active' : 'inactive',
                'photo' => $user->photo_url
            ], 'User details retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error retrieving user details: ' . $e->getMessage());
        }
    }

    /**
     * Update user data (nama, email, phone, status, password, pam_id).
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            if (!RoleHelper::hasManagementAccess()) {
                return $this->forbiddenResponse('You do not have permission to update users.');
            }

            $user = User::findOrFail($id);

            if (!RoleHelper::isSuperAdmin() && RoleHelper::getUserPamId() !== $user->pam_id) {
                return $this->forbiddenResponse('You are not allowed to update to this user. You can only update to users from your own PAM.');
            }

            // Validation rules
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:50',
                'email' => [
                    'sometimes',
                    'string',
                    'email',
                    'max:150',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phone' => 'sometimes|string|max:20',
                'status' => 'sometimes|boolean',
                'password' => 'sometimes|string|min:6',
                'pam_id' => 'sometimes|nullable|exists:pams,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors(), 422);
            }

            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            if ($request->has('phone')) {
                $user->phone = $request->phone;
            }

            if ($request->has('status')) {
                $user->is_active = $request->boolean('status');
            }

            if ($request->has('pam_id')) {
                $user->pam_id = $request->pam_id;
            }

            if ($request->has('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            return $this->successResponse([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->is_active ? 'active' : 'inactive',
                'pam_id' => $user->pam_id,
                'updated_at' => $user->updated_at->toDateTimeString(),
            ], 'User updated successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error updating user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assign role to user.
     */
    public function assignRole(Request $request, $id): JsonResponse
    {
        try {
            // Check if user has management access
            if (!RoleHelper::hasManagementAccess()) {
                return $this->forbiddenResponse('You are not allowed to assign role to this user.');
            }

            $user = User::findOrFail($id);

            if (!RoleHelper::isSuperAdmin() && RoleHelper::getUserPamId() !== $user->pam_id) {
                return $this->forbiddenResponse('You are not allowed to assign role to this user. You can only assign role to users from your own PAM.');
            }

            $validator = Validator::make($request->all(), [
                'role' => 'required|string|exists:roles,name',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->role === 'superadmin' && !RoleHelper::isSuperAdmin()) {
                return $this->forbiddenResponse('You are not allowed to assign Super Admin role.');
            }

            if ($user->hasRole($request->role)) {
                return $this->errorResponse('User already has the ' . $request->role . ' role', 400);
            }

            $user->assignRole([$request->role]);

            return $this->successResponse([
                'user_id' => $user->id,
                'role' => $request->role,
                'roles' => $user->getRoleNames(),
            ], 'Role assigned successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error assigning role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove role(s) from user.
     */
    public function removeRole(Request $request, $id): JsonResponse
    {
        try {
            if (!RoleHelper::hasManagementAccess()) {
                return $this->forbiddenResponse('You are not allowed to remove role from this user');
            }

            $user = User::findOrFail($id);

            if (!RoleHelper::isSuperAdmin() && RoleHelper::getUserPamId() !== $user->pam_id) {
                return $this->forbiddenResponse('You are not allowed to remove role from this user. You can only remove role from users from your own PAM.');
            }

            $validator = Validator::make($request->all(), [
                'role' => 'required_without:roles|string|exists:roles,name',
                'roles' => 'required_without:role|array',
                'roles.*' => 'string|exists:roles,name',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Handle single role or multiple roles
            $rolesToRemove = [];
            $removedRoles = [];
            $notFoundRoles = [];

            if ($request->has('role')) {
                $rolesToRemove = [$request->role];
            } elseif ($request->has('roles')) {
                $rolesToRemove = $request->roles;
            }

            // Prevent self superadmin role deletation
            $authUser = $request->user();
            if (
                $authUser->id === (int) $id &&
                in_array('superadmin', $rolesToRemove, true) &&
                $authUser->hasRole('superadmin')
            ) {
                return $this->forbiddenResponse('You cannot remove your own Super Admin role.');
            }

            foreach ($rolesToRemove as $role) {
                if ($user->hasRole($role)) {
                    $user->removeRole($role);
                    $removedRoles[] = $role;
                } else {
                    $notFoundRoles[] = $role;
                }
            }

            if (count($removedRoles) > 0) {
                $message = 'Roles removed successfully.';
            } else {
                $message = 'No roles were removed.';
            }

            if (!empty($notFoundRoles)) {
                $message .= ' Some roles were not found: ' . implode(', ', $notFoundRoles);
            }

            return $this->successResponse([
                'user_id' => $user->id,
                'removed_roles' => $removedRoles,
                'not_found_roles' => $notFoundRoles,
                'current_roles' => $user->getRoleNames(),
            ], $message);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error removing role: ' . $e->getMessage());
        }
    }

    /**
     * Delete user (soft delete).
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Check if user is superadmin
            if (!RoleHelper::isSuperAdmin()) {
                return $this->forbiddenResponse('You are not allowed to delete this user.');
            }

            $user = User::findOrFail($id);
            $name = $user->name;

            // Prevent self-deletion
            if (Auth::user()->id === $user->id) {
                return $this->forbiddenResponse('You are not allowed to delete yourself.');
            }
            $user->delete();

            return $this->successResponse(['name' => $name, 'deleted_at' => now()->toDateTimeString()], 'User deleted successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error deleting user: ' . $e->getMessage());
        }
    }
}
