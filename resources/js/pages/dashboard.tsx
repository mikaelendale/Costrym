import AIChatPanel from '@/components/Chat/AIChatPanel';
import OptomizedCost from '@/components/DashBoard/OptomizedCost';
import Stats from '@/components/DashBoard/Stats';
import WorkflowCards from '@/components/DashBoard/WorkflowCards';
import AppLayout from '@/layouts/app-layout';
import ProgressLayout from '@/layouts/app/ProgressLayout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
    },
];

export default function Dashboard() {
    const { auth, name } = usePage<SharedData>().props;
    const userName = auth?.user?.name ?? name ?? undefined;
    type Step = { name: string; description: string; isCompleted: boolean };
    const [selectedWorkflow, setSelectedWorkflow] = useState<{
        title: string;
        steps: Step[];
    } | null>(null);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <ProgressLayout selectedWorkflow={selectedWorkflow} onResetSelection={() => setSelectedWorkflow(null)}>
                <div className="flex min-h-screen w-full flex-col gap-8">
                    <Stats />
                    <AIChatPanel userName={userName} />

                    <WorkflowCards onSelect={(wf) => setSelectedWorkflow(wf)} />
                    <OptomizedCost />
                </div>
            </ProgressLayout>
        </AppLayout>
    );
}
