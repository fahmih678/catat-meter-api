<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    /**
     * Show users management page.
     */
    public function index()
    {
        $users = User::with('roles')->orderBy('id', 'desc')->paginate(10);
        return view('dashboard.users', compact('users'));
    }

    /**
     * Show user detail page.
     */
    public function show($id)
    {
        $user = User::with('roles', 'permissions')->findOrFail($id);
        return view('dashboard.user-detail', compact('user'));
    }

    /**
     * Update user information.
     */
    public function update(Request $request, $id)
    {
        try {
            $validationRules = [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255|unique:users,email,' . $id,
                'phone' => 'sometimes|nullable|string|max:20',
                'password' => 'sometimes|nullable|string|min:8',
                'password_confirmation' => 'sometimes|nullable|same:password',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,name',
            ];

            $request->validate($validationRules);

            $user = User::findOrFail($id);

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

            $user->save();

            // Update roles if provided
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            }

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                // Reload user to get updated relationships
                $user->refresh();

                return response()->json([
                    'success' => true,
                    'message' => 'User information updated successfully',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'roles' => $user->roles->pluck('name'),
                            'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
                        ]
                    ]
                ]);
            }

            return back()->with('success', 'User information updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            throw $e;
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('User update error: ' . $e->getMessage(), [
                'user_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return error response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user: ' . $e->getMessage(),
                    'errors' => []
                ], 500);
            }

            return back()->with('error', 'Failed to update user. Please try again.');
        }
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|same:password',
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Password updated successfully']);
        }

        return back()->with('success', 'Password updated successfully');
    }

    /**
     * Update user role.
     */
    public function updateRole(Request $request, $id)
    {
        try {
            $request->validate([
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,name',
            ]);

            $user = User::findOrFail($id);
            $roles = $request->roles ?? [];

            $user->syncRoles($roles);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User roles updated successfully',
                    'data' => [
                        'user_id' => $user->id,
                        'roles' => $user->roles->pluck('name'),
                        'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
                    ]
                ]);
            }

            return back()->with('success', 'User roles updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            throw $e;
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('User role update error: ' . $e->getMessage(), [
                'user_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return error response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update roles: ' . $e->getMessage(),
                    'errors' => []
                ], 500);
            }

            return back()->with('error', 'Failed to update roles. Please try again.');
        }
    }

    /**
     * Create new user.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'password' => 'required|string|min:8',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,name',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            }

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'roles' => $user->roles->pluck('name'),
                            'created_at' => $user->created_at->format('Y-m-d H:i:s')
                        ]
                    ]
                ], 201);
            }

            return back()->with('success', 'User created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            throw $e;
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('User creation error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return error response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user: ' . $e->getMessage(),
                    'errors' => []
                ], 500);
            }

            return back()->with('error', 'Failed to create user. Please try again.');
        }
    }

    /**
     * Delete user.
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deletion of self
            if ($user->id === Auth::id()) {
                $errorMessage = 'You cannot delete your own account';

                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 403);
                }

                return back()->with('error', $errorMessage);
            }

            $user->delete();

            // Return JSON response for AJAX requests
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User deleted successfully',
                    'data' => [
                        'deleted_user_id' => $user->id,
                        'deleted_user_name' => $user->name
                    ]
                ], 200);
            }

            return back()->with('success', 'User deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $errorMessage = 'User not found';

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => []
                ], 404);
            }

            return back()->with('error', $errorMessage);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('User deletion error: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to delete user. Please try again.';

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => []
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Toggle user status (active/inactive).
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deactivation of self
            if ($user->id === Auth::id()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You cannot deactivate your own account',
                        'errors' => []
                    ], 403);
                }
                return back()->with('error', 'You cannot deactivate your own account');
            }

            // Toggle is_active status
            $user->update([
                'is_active' => !$user->is_active
            ]);

            $statusText = $user->is_active ? 'activated' : 'deactivated';
            $message = "User {$statusText} successfully";

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'user_id' => $user->id,
                        'is_active' => $user->is_active,
                        'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
                    ]
                ]);
            }

            return back()->with('success', $message);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $errorMessage = 'User not found';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => []
                ], 404);
            }

            return back()->with('error', $errorMessage);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('User status toggle error: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to update user status. Please try again.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => []
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }
}
