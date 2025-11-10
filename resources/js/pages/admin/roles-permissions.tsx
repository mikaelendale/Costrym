"use client"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"
import { TrashIcon, EditIcon, Plus, ChevronDown, ChevronUp } from "lucide-react"
import { useState } from "react"
import AppLayout from "@/layouts/app-layout"
import { Badge } from "@/components/ui/badge"
import { ScrollArea } from "@/components/ui/scroll-area"
import { usePage, useForm, router } from '@inertiajs/react'

interface Role {
    id: number;
    name: string;
    permissions: number[];
    createdAt: string;
    updatedAt: string;
}

interface Permission {
    id: number;
    name: string;
    createdAt: string;
    updatedAt: string;
}

interface RolesPermissionsProps {
    roles: Role[];
    permissions: Permission[];
}

export default function RolesPermissions({ roles: initialRoles, permissions: initialPermissions }: RolesPermissionsProps) {
    const { props } = usePage();
    const [roles, setRoles] = useState<Role[]>(initialRoles || []);
    const [permissions, setPermissions] = useState<Permission[]>(initialPermissions || []);
    const [newRole, setNewRole] = useState({
        name: "",
        permissions: [] as number[],
    });
    const [newPermission, setNewPermission] = useState({
        name: "",
    });
    const [editingRole, setEditingRole] = useState<Role | null>(null);
    const [editingPermission, setEditingPermission] = useState<Permission | null>(null);
    const [expandedPermissions, setExpandedPermissions] = useState(false);
    const [isAddPermissionOpen, setIsAddPermissionOpen] = useState(false);
    const [isEditRoleOpen, setIsEditRoleOpen] = useState(false);
    const [isEditPermissionOpen, setIsEditPermissionOpen] = useState(false);

    // Helper function to get permission name by ID
    const getPermissionNameById = (permissionId: number) => {
        const permission = permissions.find(p => p.id === permissionId);
        return permission ? permission.name : 'Unknown Permission';
    };

    const handleAddPermission = () => {
        if (newPermission.name.trim()) {
            router.post(route('admin.permissions.store'), {
                name: newPermission.name
            }, {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    setNewPermission({ name: "" });
                    setIsAddPermissionOpen(false);
                }
            });
        }
    }

    const handleCreateRole = () => {
        if (newRole.name.trim()) {
            router.post(route('admin.roles.store'), {
                name: newRole.name,
                permissions: newRole.permissions
            }, {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    setNewRole({
                        name: "",
                        permissions: [],
                    });
                }
            });
        }
    }

    const handleEditRole = (role: Role) => {
        setEditingRole({
            ...role,
            permissions: role.permissions
        });
        setIsEditRoleOpen(true);
    }

    const handleEditPermission = (permission: Permission) => {
        setEditingPermission(permission);
        setIsEditPermissionOpen(true);
    }

    const handleUpdateRole = () => {
        if (editingRole) {
            router.put(route('admin.roles.update', { role: editingRole.id }), {
                name: editingRole.name,
                permissions: editingRole.permissions
            }, {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    setIsEditRoleOpen(false);
                    setEditingRole(null);
                }
            });
        }
    }

    const handleUpdatePermission = () => {
        if (editingPermission) {
            router.put(route('admin.permissions.update', { permission: editingPermission.id }), {
                name: editingPermission.name
            }, {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    setIsEditPermissionOpen(false);
                    setEditingPermission(null);
                }
            });
        }
    }

    const handleDeleteRole = (roleId: number) => {
        router.delete(route('admin.roles.destroy', { role: roleId }), {
            preserveScroll: true,
            preserveState: false,
        });
    }

    const handleDeletePermission = (permissionId: number) => {
        router.delete(route('admin.permissions.destroy', { permission: permissionId }), {
            preserveScroll: true,
            preserveState: false,
        });
    }

    const handleNewRolePermissionToggle = (permissionId: number) => {
        const updatedPermissions = newRole.permissions.includes(permissionId)
            ? newRole.permissions.filter((p) => p !== permissionId)
            : [...newRole.permissions, permissionId];
        setNewRole({ ...newRole, permissions: updatedPermissions });
    }

    const handleEditRolePermissionToggle = (permissionId: number) => {
        if (!editingRole) return;

        const updatedPermissions = editingRole.permissions.includes(permissionId)
            ? editingRole.permissions.filter((p) => p !== permissionId)
            : [...editingRole.permissions, permissionId];
        setEditingRole({ ...editingRole, permissions: updatedPermissions });
    }

    const getDisplayPermissions = (rolePermissions: number[], showAll = false) => {
        const checked = permissions.filter((p) => rolePermissions.includes(p.id));
        const unchecked = permissions.filter((p) => !rolePermissions.includes(p.id));
        const sortedPermissions = [...checked, ...unchecked];

        if (!showAll && sortedPermissions.length > 8) {
            return sortedPermissions.slice(0, 8);
        }
        return sortedPermissions;
    }

    return (
        <AppLayout>
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                <div className="flex justify-between items-center">
                    <h2 className="text-2xl font-bold">Roles and Permissions</h2>
                    <Dialog open={isAddPermissionOpen} onOpenChange={setIsAddPermissionOpen}>
                        <DialogTrigger asChild>
                            <Button size="sm">
                                <Plus className="h-4 w-4 mr-2" />
                                Add Permission
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Add New Permission</DialogTitle>
                                <DialogDescription>Create a new permission that can be assigned to roles.</DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-4 py-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="permission-name">Permission Name</Label>
                                    <Input
                                        id="permission-name"
                                        value={newPermission.name}
                                        onChange={(e) => setNewPermission({ ...newPermission, name: e.target.value })}
                                        placeholder="e.g., Manage Products"
                                    />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setIsAddPermissionOpen(false)}>
                                    Cancel
                                </Button>
                                <Button onClick={handleAddPermission}>Submit</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>

                <Card className="rounded-3xl bg-accent border-none shadow-none">
                    <CardHeader>
                        <CardTitle>Create New Role</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="role-name">Role Name</Label>
                                <Input
                                    id="role-name"
                                    value={newRole.name}
                                    onChange={(e) => setNewRole({ ...newRole, name: e.target.value })}
                                    placeholder="Enter role name..."
                                />
                            </div>
                        </div>

                        <div className="space-y-4">
                            <Label>Permissions</Label>
                            <div className="grid grid-cols-1 pt-2 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                {getDisplayPermissions(newRole.permissions, expandedPermissions).map((permission) => (
                                    <Card
                                        key={permission.id}
                                        className={`cursor-pointer transition-all shadow-none border border-dashed h-12 ${newRole.permissions.includes(permission.id)
                                            ? "ring ring-primary bg-primary/5"
                                            : "hover:bg-muted/50"
                                            }`}
                                        onClick={() => handleNewRolePermissionToggle(permission.id)}
                                    >
                                        <CardContent className="flex h-full">
                                            <div className="flex items-center gap-2">
                                                <Checkbox
                                                    checked={newRole.permissions.includes(permission.id)}
                                                    onChange={() => { }}
                                                    className="h-4 w-4"
                                                />
                                                <div className="flex-1 min-w-0">
                                                    <p className="font-medium text-sm">{permission.name}</p>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </div>

                        <div className="flex justify-between items-center pt-4 border-t">
                            {permissions.length > 8 && (
                                <Button
                                    variant="link"
                                    size="sm"
                                    onClick={() => setExpandedPermissions(!expandedPermissions)}
                                    className=""
                                >
                                    {expandedPermissions ? (
                                        <>
                                            <ChevronUp className="h-4 w-4 mr-2" />
                                            Show Less
                                        </>
                                    ) : (
                                        <>
                                            <ChevronDown className="h-4 w-4 mr-2" />
                                            Show More ({permissions.length - 8} more)
                                        </>
                                    )}
                                </Button>
                            )}
                            <Button onClick={handleCreateRole} disabled={!newRole.name.trim()}>
                                Create Role
                            </Button>
                        </div>
                    </CardContent>
                </Card>


                <Card className="rounded-3xl bg-accent shadow-none border-none">
                    <CardHeader>
                        <CardTitle>Existing Roles ({roles.length})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4">
                            {roles.map((role) => (
                                <Card key={role.id} className="border border-dashed rounded-3xl shadow-none">
                                    <CardContent className="">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <h3 className="text-lg font-semibold">{role.name}</h3>
                                                <div className="flex gap-4 text-xs text-muted-foreground">
                                                    <span>Created: {role.createdAt}</span>
                                                    <span>Updated: {role.updatedAt}</span>
                                                </div>
                                            </div>
                                            <div className="flex gap-2">
                                                <Button variant="link" size="sm" onClick={() => handleEditRole(role)}>
                                                    <EditIcon className="h-4 w-4 mr-2" />
                                                    Edit
                                                </Button>
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button variant="link" size="sm">
                                                            <TrashIcon className="h-4 w-4 mr-2" />
                                                            Delete
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>Delete Role</DialogTitle>
                                                            <DialogDescription>
                                                                Are you sure you want to delete the "{role.name}" role? This action cannot be undone and
                                                                will affect all users assigned to this role.
                                                            </DialogDescription>
                                                        </DialogHeader>
                                                        <DialogFooter>
                                                            <Button
                                                                onClick={() => handleDeleteRole(role.id)}
                                                                variant={'destructive'}
                                                            >
                                                                Delete Role
                                                            </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            </div>
                                        </div>

                                        <div className="space-y-3">
                                            <div className="flex items-center justify-between">
                                                <Label className="text-sm font-medium">Permissions ({role.permissions.length})</Label>
                                            </div>
                                            <div className="gap-2">
                                                {role.permissions.map((permissionId) => (
                                                    <Badge key={permissionId} variant={'outline'} className="mx-1 my-1">
                                                        <span className="text-sm font-medium">{getPermissionNameById(permissionId)}</span>
                                                    </Badge>
                                                ))}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-3xl bg-accent shadow-none border-none">
                    <CardHeader>
                        <CardTitle>Existing Permissions ({permissions.length})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4">
                            {permissions.map((permission) => (
                                <Card key={permission.id} className="border border-dashed rounded-3xl shadow-none">
                                    <CardContent className="">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <h3 className="text-lg font-semibold">{permission.name}</h3>
                                                <div className="flex gap-4 text-xs text-muted-foreground">
                                                    <span>Created: {permission.createdAt}</span>
                                                    <span>Updated: {permission.updatedAt}</span>
                                                </div>
                                            </div>
                                            <div className="flex gap-2">
                                                <Button variant="link" size="sm" onClick={() => handleEditPermission(permission)}>
                                                    <EditIcon className="h-4 w-4 mr-2" />
                                                    Edit
                                                </Button>
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button variant="link" size="sm">
                                                            <TrashIcon className="h-4 w-4 mr-2" />
                                                            Delete
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>Delete Permission</DialogTitle>
                                                            <DialogDescription>
                                                                Are you sure you want to delete the "{permission.name}" permission? This action cannot be undone.
                                                            </DialogDescription>
                                                        </DialogHeader>
                                                        <DialogFooter>
                                                            <Button
                                                                onClick={() => handleDeletePermission(permission.id)}
                                                                variant={'destructive'}
                                                            >
                                                                Delete Permission
                                                            </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Dialog open={isEditRoleOpen} onOpenChange={setIsEditRoleOpen}>
                    <DialogContent className="max-w-4xl max-h-[80vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Edit Role</DialogTitle>
                            <DialogDescription>Modify the role details and permissions.</DialogDescription>
                        </DialogHeader>
                        {editingRole && (
                            <div className="space-y-6 py-4">
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-role-name">Role Name</Label>
                                        <Input
                                            id="edit-role-name"
                                            value={editingRole.name}
                                            onChange={(e) => setEditingRole({ ...editingRole, name: e.target.value })}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <Label>Permissions ({editingRole.permissions.length} selected)</Label>
                                    <ScrollArea className="h-50 pr-1">
                                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                            {permissions
                                                .sort((a, b) => {
                                                    const aChecked = editingRole.permissions.includes(a.id);
                                                    const bChecked = editingRole.permissions.includes(b.id);
                                                    if (aChecked && !bChecked) return -1;
                                                    if (!aChecked && bChecked) return 1;
                                                    return 0;
                                                })
                                                .map((permission) => (
                                                    <Card
                                                        key={permission.id}
                                                        className={`cursor-pointer transition-all shadow-none border border-dashed h-12 ${editingRole.permissions.includes(permission.id)
                                                            ? "ring ring-primary bg-primary/5"
                                                            : "hover:bg-muted/50"
                                                            }`}
                                                        onClick={() => handleEditRolePermissionToggle(permission.id)}
                                                    >
                                                        <CardContent className="flex h-full">
                                                            <div className="flex items-center gap-2">
                                                                <Checkbox
                                                                    className="h-4 w-4"
                                                                    checked={editingRole.permissions.includes(permission.id)}
                                                                    onChange={() => { }}
                                                                />
                                                                <div className="flex-1 min-w-0">
                                                                    <p className="font-medium text-sm">{permission.name}</p>
                                                                </div>
                                                            </div>
                                                        </CardContent>
                                                    </Card>
                                                ))}
                                        </div>
                                    </ScrollArea>
                                </div>
                            </div>
                        )}
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsEditRoleOpen(false)}>
                                Cancel
                            </Button>
                            <Button onClick={handleUpdateRole}>Update Role</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog open={isEditPermissionOpen} onOpenChange={setIsEditPermissionOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Edit Permission</DialogTitle>
                            <DialogDescription>Modify the permission name.</DialogDescription>
                        </DialogHeader>
                        {editingPermission && (
                            <div className="grid gap-4 py-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="edit-permission-name">Permission Name</Label>
                                    <Input
                                        id="edit-permission-name"
                                        value={editingPermission.name}
                                        onChange={(e) => setEditingPermission({ ...editingPermission, name: e.target.value })}
                                    />
                                </div>
                            </div>
                        )}
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsEditPermissionOpen(false)}>
                                Cancel
                            </Button>
                            <Button onClick={handleUpdatePermission}>Update Permission</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    )
}