<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasPamFiltering;
use Illuminate\Http\Request;
use App\Models\User;
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
        // Check if user has management access (superadmin or admin)
        if (!RoleHelper::hasManagementAccess()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
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
                    return response()->json([
                        'message' => 'Cannot filter by PAM that is not assigned to you.',
                    ], 403);
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
        $sortBy = $request->get('sort_by', 'created_at');
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
            ];
        });

        return response()->json([
            'message' => 'Data users retrieved successfully',
            'data' => $transformedUsers,
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'hasNextPage' => $users->hasMorePages(),
            ]
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show($id): JsonResponse
    {
        // Check if user has management access
        if (!RoleHelper::hasManagementAccess()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $user = User::with(['pam', 'roles'])->findOrFail($id);

        // Check if user has permission to view this user
        if (!RoleHelper::isSuperAdmin() && !RoleHelper::canAccessPam($user->pam_id)) {
            return response()->json([
                'message' => 'Unauthorized to view this user. You can only view users from your own PAM.',
            ], 403);
        }

        return response()->json([
            'message' => 'User details retrieved successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $user->roles->pluck('name'),
                'status' => $user->is_active ? 'active' : 'inactive',
            ],
        ]);
    }

    /**
     * Update user data (nama, email, phone, status, password, pam_id).
     */
    public function update(Request $request, $id): JsonResponse
    {
        // Check if user has management access
        if (!RoleHelper::hasManagementAccess()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $user = User::findOrFail($id);

        // Check if user has permission to update this user
        if (!RoleHelper::isSuperAdmin() && !RoleHelper::canAccessPam($user->pam_id)) {
            return response()->json([
                'message' => 'Unauthorized to update this user. You can only update users from your own PAM.',
            ], 403);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'sometimes|string|max:20',
            'status' => 'sometimes|boolean',
            'password' => 'sometimes|string|min:6',
            'pam_id' => 'sometimes|nullable|exists:pams,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate PAM access for non-superadmin
        if ($request->has('pam_id') && !RoleHelper::isSuperAdmin()) {
            $requestedPamId = $request->pam_id;
            $userPamId = RoleHelper::getUserPamId();

            if ($requestedPamId != $userPamId) {
                return response()->json([
                    'message' => 'Cannot assign user to PAM that is not assigned to you.',
                ], 403);
            }
        }

        // Update user data
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

        return response()->json([
            'message' => 'User updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->is_active ? 'active' : 'inactive',
                'pam_id' => $user->pam_id,
                'updated_at' => $user->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Assign role to user.
     */
    public function assignRole(Request $request, $id): JsonResponse
    {
        // Check if user has management access
        if (!RoleHelper::hasManagementAccess()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $user = User::findOrFail($id);

        // Check if user has permission to update this user
        if (!RoleHelper::isSuperAdmin() && !RoleHelper::canAccessPam($user->pam_id)) {
            return response()->json([
                'message' => 'Unauthorized to assign role to this user. You can only assign roles to users from your own PAM.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($user->hasRole($request->role)) {
            return response()->json([
                'message' => 'User already has the ' . $request->role . ' role',
            ], 400);
        }

        $user->assignRole([$request->role]);

        return response()->json([
            'message' => 'Role assigned successfully',
            'data' => [
                'user_id' => $user->id,
                'role' => $request->role,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Remove role(s) from user.
     */
    public function removeRole(Request $request, $id): JsonResponse
    {
        // Check if user has management access
        if (!RoleHelper::hasManagementAccess()) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $user = User::findOrFail($id);

        // Check if user has permission to update this user
        if (!RoleHelper::isSuperAdmin() && !RoleHelper::canAccessPam($user->pam_id)) {
            return response()->json([
                'message' => 'Unauthorized to remove role from this user. You can only remove roles from users from your own PAM.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required_without:roles|string|exists:roles,name',
            'roles' => 'required_without:role|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
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

        foreach ($rolesToRemove as $role) {
            if ($user->hasRole($role)) {
                $user->removeRole($role);
                $removedRoles[] = $role;
            } else {
                $notFoundRoles[] = $role;
            }
        }

        $message = count($removedRoles) > 0 ? 'Roles removed successfully' : 'No roles were removed';

        $response = [
            'message' => $message,
            'data' => [
                'user_id' => $user->id,
                'removed_roles' => $removedRoles,
                'current_roles' => $user->getRoleNames(),
            ],
        ];

        if (!empty($notFoundRoles)) {
            $response['data']['not_found_roles'] = $notFoundRoles;
            $response['message'] .= '. Some roles were not found: ' . implode(', ', $notFoundRoles);
        }

        return response()->json($response);
    }

    /**
     * Delete user (soft delete).
     */
    public function destroy($id): JsonResponse
    {
        // Check if user is superadmin
        if (!RoleHelper::isSuperAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $user = User::findOrFail($id);

        // Prevent self-deletion
        if (Auth::user()->id === $user->id) {
            return response()->json([
                'message' => 'Cannot delete your own account.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
            'data' => [
                'user_id' => $id,
                'deleted_at' => now()->toISOString(),
            ],
        ]);
    }
}
