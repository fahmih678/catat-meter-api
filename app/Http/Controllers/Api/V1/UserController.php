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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use HasPamFiltering;

    /**
     * Display a listing of users with filters and pagination.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            if (!RoleHelper::hasManagementAccess()) {
                return $this->forbiddenResponse('User access forbidden - Insufficient permissions');
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
                        return $this->forbiddenResponse('PAM filter forbidden - Cannot filter by PAM not assigned to you');
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
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'roles' => $user->roles->pluck('name'),
                        'status' => $user->is_active ? 'active' : 'inactive',
                        'photo' => $user->photo_url ? asset($user->photo_url) : null,
                    ],
                    'pam' => $user->pam != null ? [
                        'id' => $user->pam?->id,
                        'name' => $user->pam?->name,
                    ] : null,
                ];
            });

            return $this->successResponse([
                'pagination' => [
                    'total' => $users->total(),
                    'has_more_pages' => $users->hasMorePages(),
                ],
                'items' => $transformedUsers
            ], 'Users retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat mengambil data pengguna', 500);
        }
    }

    /**
     * Display the specified user.
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            if (!RoleHelper::hasManagementAccess()) {
                return $this->forbiddenResponse('User access forbidden - Insufficient permissions');
            }

            $user = User::with(['pam', 'roles'])->findOrFail($id);

            if (!RoleHelper::isSuperAdmin() && RoleHelper::getUserPamId() !== $user->pam_id) {
                return $this->forbiddenResponse('User view forbidden - Cannot view user from different PAM');
            }

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->roles->pluck('name'),
                    'status' => $user->is_active ? 'active' : 'inactive',
                    'photo' => $user->photo_url ? asset($user->photo_url) : null,
                ],
                'pam' => [
                    'id' => $user->pam?->id,
                    'name' => $user->pam?->name,
                ],
            ], 'User details retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Throwable $e) {
            return $this->errorResponse('Terjadi kesalahan saat mengambil detail pengguna', 500);
        }
    }

    /**
     * Update user data (nama, email, phone, status, password, pam_id).
     * @param Request $request
     * @param int $id
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            if (!RoleHelper::hasManagementAccess()) {
                return $this->forbiddenResponse('User update forbidden - Insufficient permissions');
            }

            $user = User::findOrFail($id);

            if (!RoleHelper::isSuperAdmin() && RoleHelper::getUserPamId() !== $user->pam_id) {
                return $this->forbiddenResponse('User update forbidden - Cannot update user from different PAM');
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
                'phone' => 'sometimes|nullable|string|max:20',
                'status' => 'sometimes|boolean',
                'password' => 'sometimes|string|min:6',
                'pam_id' => 'sometimes|nullable|exists:pams,id',
                'photo' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
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
                'updated_data' => $request->only(['name', 'email', 'phone', 'status']),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ], 'User updated successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat memperbarui pengguna', 500);
        }
    }

    /**
     * Assign role to user.
     * @param Request $request
     * @param int $id
     */
    public function assignRole(Request $request, $id): JsonResponse
    {
        try {
            // Check if user has management access
            if (!RoleHelper::hasManagementAccess()) {
                return $this->forbiddenResponse('Role assignment forbidden - Insufficient permissions');
            }

            $user = User::findOrFail($id);

            if (!RoleHelper::isSuperAdmin() && RoleHelper::getUserPamId() !== $user->pam_id) {
                return $this->forbiddenResponse('Role assignment forbidden - Cannot assign role to user from different PAM');
            }

            $validator = Validator::make($request->all(), [
                'role' => 'required|string|exists:roles,name',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->role === 'superadmin' && !RoleHelper::isSuperAdmin()) {
                return $this->forbiddenResponse('Super Admin role assignment forbidden - Insufficient permissions');
            }

            if ($user->hasRole($request->role)) {
                return $this->errorResponse('User already has the ' . $request->role . ' role', 400);
            }

            $user->assignRole([$request->role]);

            return $this->successResponse([
                'user_id' => $user->id,
                'added_role' => $request->role,
                'current_roles' => $user->getRoleNames(),
            ], 'Role assigned successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat menetapkan role', 500);
        }
    }

    /**
     * Remove role(s) from user.
     * @param Request $request
     * @param int $id
     */
    public function removeRole(Request $request, $id): JsonResponse
    {
        try {
            if (!RoleHelper::hasManagementAccess()) {
                return $this->forbiddenResponse('Role removal forbidden - Insufficient permissions');
            }

            $user = User::findOrFail($id);

            if (!RoleHelper::isSuperAdmin() && RoleHelper::getUserPamId() !== $user->pam_id) {
                return $this->forbiddenResponse('Role removal forbidden - Cannot remove role from user from different PAM');
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

            // Prevent self superadmin role deletion
            $authUser = $request->user();
            if (
                $authUser->id === (int) $id &&
                in_array('superadmin', $rolesToRemove, true) &&
                $authUser->hasRole('superadmin')
            ) {
                return $this->forbiddenResponse('Self role removal forbidden - Cannot remove your own Super Admin role');
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
            return $this->errorResponse('Terjadi kesalahan saat menghapus role', 500);
        }
    }

    /**
     * Delete user (soft delete).
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Check if user is superadmin
            if (!RoleHelper::isSuperAdmin()) {
                return $this->forbiddenResponse('User deletion forbidden - Insufficient permissions');
            }

            $user = User::findOrFail($id);
            $name = $user->name;

            // Prevent self-deletion
            if (Auth::user()->id === $user->id) {
                return $this->forbiddenResponse('Self deletion forbidden - Cannot delete your own account');
            }
            $user->delete();

            return $this->successResponse(['name' => $name, 'deleted_at' => now()->toDateTimeString()], 'User deleted successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat menghapus pengguna', 500);
        }
    }

    /**
     * Handle image upload for user profile
     * @param File $file
     * @param int $userId
     * @return ?string
     */
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
                'user_id' => $userId,
                'original_filename' => $file->getClientOriginalName(),
            ]);

            return null;
        }
    }
}
