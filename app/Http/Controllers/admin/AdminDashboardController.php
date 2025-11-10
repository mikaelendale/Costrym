<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $total_users = User::role('user')->count();
        $total_revenue = '$11';
        $subscribed_user = User::role('user')->count();
        
        return Inertia::render('admin/dashboard', [
            'total_users' => $total_users,
            'total_revenue' => $total_revenue,
            'subscribed_user' => $subscribed_user
        ]);
    }

    public function users()
    {
        $users = User::with('roles')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'is_banned' => $user->is_banned,
                'roles' => $user->roles->pluck('name'),
                'plan' => $user->plan ?? 'basic'
            ];
        });

        return Inertia::render('admin/users/index', [
            'users' => $users,
        ]);
    }

    public function showUser(User $user)
    {
        $user->load('roles');
        
        $activities = Activity::where('causer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                    'event' => $activity->event,
                ];
            });

        $roles = Role::all()->pluck('name');

        return Inertia::render('admin/users/show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'is_banned' => $user->is_banned,
                'roles' => $user->roles->pluck('name'),
                'plan' => $user->plan ?? 'basic'
            ],
            'activities' => $activities,
            'available_roles' => $roles
        ]);
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'plan' => 'nullable|string|in:basic,pro,enterprise'
        ]);

        try {
            $user->update($request->only(['name', 'email', 'plan']));
            
            return redirect()->back()->with('success', 'User updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    public function deleteUser(User $user)
    {
        try {
            // Prevent deletion of admin users
            if ($user->hasRole('admin')) {
                return redirect()->back()->with('error', 'Cannot delete admin users');
            }

            $user->delete();
            return redirect()->route('admin.users.index')->with('success', 'User deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name'
        ]);

        try {
            $user->syncRoles([$request->role]);
            
            return redirect()->back()->with('success', 'Role assigned successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to assign role: ' . $e->getMessage());
        }
    }

    public function banUser(User $user)
    {
        try {
            if ($user->hasRole('admin')) {
                return redirect()->back()->with('error', 'Cannot ban admin users');
            }

            $user->update(['is_banned' => true]);
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->log('banned user');
                
            return redirect()->back()->with('success', 'User banned successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to ban user: ' . $e->getMessage());
        }
    }

    public function unbanUser(User $user)
    {
        try {
            $user->update(['is_banned' => false]);
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->log('unbanned user');
                
            return redirect()->back()->with('success', 'User unbanned successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to unban user: ' . $e->getMessage());
        }
    }
}