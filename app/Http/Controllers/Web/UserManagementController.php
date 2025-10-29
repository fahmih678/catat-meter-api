<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserManagementController extends Controller
{
    /**
     * Show users management page.
     */
    public function index()
    {
        $users = User::with('roles')->paginate(10);
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return back()->with('success', 'User information updated successfully');
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
        $request->validate([
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = User::findOrFail($id);
        $roles = $request->roles ?? [];

        $user->syncRoles($roles);

        return back()->with('success', 'User roles updated successfully');
    }

    /**
     * Create new user.
     */
    public function store(Request $request)
    {
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

        return back()->with('success', 'User created successfully');
    }

    /**
     * Delete user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent deletion of self
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully');
    }

    /**
     * Toggle user status (active/inactive).
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        // Prevent deactivation of self
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account');
        }

        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active'
        ]);

        return back()->with('success', 'User status updated successfully');
    }
}