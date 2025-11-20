import AIChatPanel from '@/components/Chat/AIChatPanel';
import OptomizedCost from '@/components/DashBoard/OptomizedCost';
import Stats from '@/components/DashBoard/Stats';
import WorkflowCards from '@/components/DashBoard/WorkflowCards';
import AppLayout from '@/layouts/app-layout';
<<<<<<< Updated upstream
import ProgressLayout from '@/layouts/app/ProgressLayout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
=======
import { SharedData, type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowUpLeft, ArrowUpRight } from 'lucide-react';
import PendingTasksCard from '@/Components/Dashboard/PendingTasksCard';
>>>>>>> Stashed changes

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
    },
];

<<<<<<< Updated upstream
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
=======
interface Task {
    id: number;
    agent_name: string;
    status: string;
    priority: number;
    data: {
        name: string;
        description: string;
        task_type: string;
        estimated_savings?: string;
        schedule?: string;
        metadata?: any;
    };
    created_at: string;
}

interface DashboardProps extends SharedData {
    pendingTasks: Task[];
}

export default function Dashboard() {
    const { auth, pendingTasks } = usePage<DashboardProps>().props;
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex justify-between">
                    {auth.roles.includes('admin') && <Button className='w-auto justify-end' variant="link"><Link href="/admin/dashboard" className='inline-block'>Admin</Link><ArrowUpRight className='ml-2 h-4 w-4' /> </Button>}
                </div>
                
                {/* AI-Generated Tasks Section */}
                <div className="mt-4">
                    <PendingTasksCard tasks={pendingTasks || []} />
                </div>
            </div>
>>>>>>> Stashed changes
        </AppLayout>
    );
}
