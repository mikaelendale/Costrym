import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { SharedData, type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/dashboard',
    },
];

export default function Dashboard({ total_users, total_emails, total_revenue, subscribed_user }: {
    total_users: number;
    total_emails: number;
    total_revenue: number;
    subscribed_user: number;
}) {
    const { auth } = usePage<SharedData>().props;
    const stats = [
        {
            title: 'Total Users',
            value: total_users,
            label: 'registered users',
        },
        {
            title: 'Revenue This Month',
            value: total_revenue,
            label: 'monthly revenue',
        },
        {
            title: 'Active Subscriptions',
            value: subscribed_user,
            label: 'paying customers',
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex justify-between">
                    <h2 className="text-lg font-medium">Welcome back Admin !</h2>
                </div>
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    {stats.map((stat, index) => (
                        <div className="relative  overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <Card key={index} className="bg-primary-foreground">
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium text-primary">{stat.title}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="mb-1 text-3xl font-bold text-primary">{stat.value}</div>
                                    <p className="text-xs text-primary">{stat.label}</p>
                                </CardContent>
                            </Card>
                        </div>
                    ))}
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </AppLayout>
    );
}
