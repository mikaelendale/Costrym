'use client';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { Ban, User, Calendar, Edit, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { BreadcrumbItem } from '@/types';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

interface UserDetail {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
    created_at: string;
    updated_at: string;
    is_banned: boolean;
    roles: string[];
    plan: string;
}

interface Activity {
    id: number;
    description: string;
    properties?: Record<string, any>;
    created_at: string;
    event: string;
}

interface UserShowProps {
    user: UserDetail;
    activities: Activity[];
    available_roles: string[];
}

// Convert UserDetail into a form-friendly payload (flattened object)
function toPayload(user: UserDetail): Record<string, any> {
    return {
        id: user.id,
        name: user.name,
        email: user.email,
        email_verified_at: user.email_verified_at,
        created_at: user.created_at,
        updated_at: user.updated_at,
        is_banned: user.is_banned,
        roles: user.roles,
        plan: user.plan,
    };
}

export default function UserShow({ user, activities, available_roles }: UserShowProps) {
    const [isEditing, setIsEditing] = useState<boolean>(false);
    const [editedUser, setEditedUser] = useState<UserDetail>(user);
    const [selectedRole, setSelectedRole] = useState<string>(user.roles[0] || '');
    const [deletingUser, setDeletingUser] = useState<boolean>(false);

    const handleSave = () => {
        router.put(route('admin.users.update', user.id), toPayload(editedUser), {
            preserveScroll: true,
            preserveState: false,
        });
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Users',
            href: '/admin/users',
        },
        {
            title: `User details - ${user.name}`,
            href: `/admin/users/${user.id}`,
        },
    ];

    const handleDelete = () => {
        router.delete(route('admin.users.destroy', user.id), {
            preserveScroll: true,
            preserveState: false,
        });
    };

    const handleBanToggle = () => {
        const routeName = user.is_banned ? 'admin.users.unban' : 'admin.users.ban';
        router.post(route(routeName, user.id), {}, {
            preserveScroll: true,
            preserveState: false,
        });
    };

    const handleRoleAssign = () => {
        if (!selectedRole) return;
        router.post(route('admin.users.assign-role', user.id), {
            role: selectedRole,
        }, {
            preserveScroll: true,
            preserveState: false,
        });
    };

    const getPlanBadgeVariant = (plan: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
        switch (plan.toLowerCase()) {
            case 'sharp':
                return 'default';
            case 'razor':
                return 'secondary';
            case 'basic':
                return 'outline';
            default:
                return 'outline';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`User Details - ${user.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex justify-between">
                    <h2 className="text-lg font-medium">See details for {user.name}</h2>
                </div>

                <Tabs defaultValue="overview" className="space-y-6">
                    <TabsList>
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="activity">Activity Log</TabsTrigger>
                        <TabsTrigger value="settings">Settings</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-6">
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {/* User Info Card */}
                            <Card className="lg:col-span-2 rounded-3xl border-none bg-accent">
                                <CardHeader>
                                    <CardTitle>User Information</CardTitle>
                                    <CardDescription>Basic account details and status</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2 border-b pb-2">
                                            <Label>Name</Label>
                                            {isEditing ? (
                                                <Input
                                                    value={editedUser.name}
                                                    onChange={(e) => setEditedUser({ ...editedUser, name: e.target.value })}
                                                />
                                            ) : (
                                                <p className="text-sm">{user.name}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2 border-b pb-2">
                                            <Label>Email</Label>
                                            {isEditing ? (
                                                <Input
                                                    type="email"
                                                    value={editedUser.email}
                                                    onChange={(e) => setEditedUser({ ...editedUser, email: e.target.value })}
                                                />
                                            ) : (
                                                <p className="text-sm">{user.email}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2 border-b pb-2 space-x-2">
                                            <Label>Email Verification</Label>
                                            <Badge variant={user.email_verified_at ? 'default' : 'destructive'}>
                                                {user.email_verified_at ? 'Verified' : 'Unverified'}
                                            </Badge>
                                        </div>
                                        <div className="space-y-2 border-b pb-2 space-x-2">
                                            <Label>Account Status</Label>
                                            <Badge variant={user.is_banned ? 'destructive' : 'default'}>
                                                {user.is_banned ? 'Banned' : 'Active'}
                                            </Badge>
                                        </div>
                                        <div className="space-y-2 border-b pb-2 space-x-2">
                                            <Label>Plan</Label>
                                            {isEditing ? (
                                                <Select
                                                    value={editedUser.plan}
                                                    onValueChange={(value) => setEditedUser({ ...editedUser, plan: value })}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="sharp">Sharp</SelectItem>
                                                        <SelectItem value="razor">Razor</SelectItem>
                                                        <SelectItem value="basic">Basic</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            ) : (
                                                <Badge variant={getPlanBadgeVariant(user.plan)}>
                                                    {user.plan}
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="space-y-2 border-b pb-2">
                                            <Label>Member Since</Label>
                                            <p className="text-sm">{new Date(user.created_at).toLocaleDateString()}</p>
                                        </div>
                                    </div>

                                    <div className="flex gap-2 pt-4">
                                        {isEditing ? (
                                            <>
                                                <Button onClick={handleSave}>
                                                    <Save className="h-4 w-4 mr-2" />
                                                    Save Changes
                                                </Button>
                                                <Button variant="outline" onClick={() => setIsEditing(false)}>
                                                    Cancel
                                                </Button>
                                            </>
                                        ) : (
                                            <Button onClick={() => setIsEditing(true)}>
                                                <Edit className="h-4 w-4 mr-2" />
                                                Edit User
                                            </Button>
                                        )}
                                        <Button
                                            variant={user.is_banned ? "default" : "destructive"}
                                            onClick={handleBanToggle}
                                        >
                                            <Ban className="h-4 w-4 mr-2" />
                                            {user.is_banned ? 'Unban User' : 'Ban User'}
                                        </Button>
                                        <Button variant="outline" onClick={() => setDeletingUser(true)}>
                                            <Trash2 className="h-4 w-4 mr-2" />
                                            Delete User
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Roles Card */}
                            <Card className='bg-transparent border-none shadow-none'>
                                <CardHeader>
                                    <CardTitle>Roles & Permissions</CardTitle>
                                    <CardDescription>Manage user roles and access</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2">
                                            <Label>Current Role</Label>
                                            <Badge variant="secondary">{user.roles[0] || 'No role assigned'}</Badge>
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label>Assign New Role</Label>
                                        <Select value={selectedRole} onValueChange={setSelectedRole}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a role" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {available_roles.map((role) => (
                                                    <SelectItem key={role} value={role}>
                                                        {role}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <Button onClick={handleRoleAssign} disabled={!selectedRole}>
                                        <User className="h-4 w-4 mr-2" />
                                        Assign Role
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="activity">
                        <Card className='bg-accent rounded-3xl border-none shadow-none'>
                            <CardHeader>
                                <CardTitle>Activity Log</CardTitle>
                                <CardDescription>Recent user activities and events</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {activities.length > 0 ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Action</TableHead>
                                                <TableHead>Details</TableHead>
                                                <TableHead>Date</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {activities.map((activity) => (
                                                <TableRow key={activity.id}>
                                                    <TableCell>
                                                        <Badge variant="outline">{activity.event}</Badge>
                                                    </TableCell>
                                                    <TableCell className="max-w-md truncate">
                                                        {activity.description}
                                                    </TableCell>
                                                    <TableCell>
                                                        {new Date(activity.created_at).toLocaleString()}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                ) : (
                                    <div className="text-center py-8 text-muted-foreground">
                                        <Calendar className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                        <p>No activity recorded yet</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="settings">
                        <Card className='bg-accent rounded-3xl border-none shadow-none1'>
                            <CardHeader>
                                <CardTitle>Danger Zone</CardTitle>
                                <CardDescription>Irreversible actions</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between p-4 ring-4 ring-destructive/30 rounded-lg">
                                    <div>
                                        <h4 className="font-semibold text-destructive">Delete User Account</h4>
                                        <p className="text-sm text-muted-foreground">
                                            Permanently delete this user account and all associated data.
                                        </p>
                                    </div>
                                    <Button variant="destructive" onClick={() => setDeletingUser(true)}>
                                        <Trash2 className="h-4 w-4 mr-2" />
                                        Delete Account
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
                <Dialog open={deletingUser} onOpenChange={setDeletingUser}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete User Account</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete this user account? This action cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setDeletingUser(false)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" onClick={handleDelete}>
                                Delete Account
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
