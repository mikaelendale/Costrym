<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RBACController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('id'), // Return permission IDs instead of names
                'createdAt' => $role->created_at->format('Y-m-d'),
                'updatedAt' => $role->updated_at->format('Y-m-d'),
            ];
        });

        $permissions = Permission::all()->map(function ($permission) {
            return [
                'id' => $permission->id, // Use actual database ID
                'name' => $permission->name,
                'createdAt' => $permission->created_at->format('Y-m-d'),
                'updatedAt' => $permission->updated_at->format('Y-m-d'),
            ];
        });

        return Inertia::render('admin/roles-permissions', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }



    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array',
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

            if ($request->has('permissions')) {
                // Use whereIn with IDs instead of names
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            DB::commit();

            return redirect()->route('admin.roles-permissions')->with('success', 'Role created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.roles-permissions')->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);

        try {
            DB::beginTransaction();

            $role->update(['name' => $request->name]);

            if ($request->has('permissions')) {
                // Use whereIn with IDs instead of names
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            DB::commit();

            return redirect()->route('admin.roles-permissions')->with('success', 'Role updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.roles-permissions')->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        try {
            // Prevent deletion of admin role if needed
            if (in_array($role->name, ['admin', 'super-admin'])) {
                return redirect()->route('admin.roles-permissions')->with('error', 'Cannot delete system roles');
            }

            $role->delete();
            return redirect()->route('admin.roles-permissions')->with('success', 'Role deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.roles-permissions')->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }
    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);

        try {
            Permission::create([
                'name' => $request->name,
                'guard_name' => 'web'
            ]);

            return redirect()->route('admin.roles-permissions')->with('success', 'Permission created successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.roles-permissions')->with('error', 'Failed to create permission: ' . $e->getMessage());
        }
    }
    public function updatePermission(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
        ]);

        try {
            $permission->update(['name' => $request->name]);

            return redirect()->route('admin.roles-permissions')->with('success', 'Permission updated successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.roles-permissions')->with('error', 'Failed to update permission: ' . $e->getMessage());
        }
    }

    public function destroyPermission(Permission $permission)
    {
        try {
            // Check if permission is being used by any role
            if ($permission->roles()->count() > 0) {
                return redirect()->route('admin.roles-permissions')->with('error', 'Cannot delete permission that is assigned to roles');
            }

            $permission->delete();
            return redirect()->route('admin.roles-permissions')->with('success', 'Permission deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.roles-permissions')->with('error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }
}
