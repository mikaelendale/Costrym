import AppLayout from '@/layouts/app-layout';
import { SharedData, type BreadcrumbItem } from '@/types';
import { Head, usePage, Link } from '@inertiajs/react';
('use client');

import WorkflowCards from '@/components/DashBoard/WorkflowCards';
import RecentAutomations from '@/components/DashBoard/RecentAutomations';
import IngestionStatusCard from '@/components/DashBoard/IngestionStatusCard';
import AnalysisStatusCard from '@/components/DashBoard/AnalysisStatusCard';
import { useState, useEffect } from 'react';
import { PromptInput, PromptInputAction, PromptInputActions } from '@/components/ui/prompt-input';
import { Button } from '@/components/ui/button';
import { PromptInputTextarea } from '@/components/ui/prompt-input';
import { Plus } from 'lucide-react';
import { Globe } from 'lucide-react';
import { MoreHorizontal } from 'lucide-react';
import { Mic } from 'lucide-react';
import { ArrowUp } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle2, Clock, FileText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { CreditCard } from '@phosphor-icons/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
    },
];

interface DashboardProps {
    pendingTasks: any[];
    firstTimeAutomation?: {
        id: number;
        type: string;
        name: string;
        description: string | null;
        created_at: string;
        metadata: {
            estimated_savings?: number;
            task_count?: number;
        } | null;
    } | null;
    recentAutomations: any[];
    totalAutomations: number;
    subscription: {
        isSubscribed: boolean;
        onTrial: boolean;
        onGracePeriod: boolean;
        plan: string | null;
        endsAt: string | null;
    };
}

export default function Dashboard({ pendingTasks, firstTimeAutomation, recentAutomations, totalAutomations, subscription }: DashboardProps) {
    const [prompt, setPrompt] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const { auth } = usePage<SharedData>().props;

    const [ingestionStatus, setIngestionStatus] = useState<{
        status: 'idle' | 'started' | 'processing' | 'categorizing' | 'completed' | 'failed';
        message?: string;
        data?: any;
    }>({ status: 'idle' });

    const [analysisStatus, setAnalysisStatus] = useState<{
        status: 'idle' | 'started' | 'analyzing' | 'completed' | 'failed';
        message?: string;
        data?: any;
    }>({ status: 'idle' });

    useEffect(() => {
        if (!auth.user?.id) return;
        const echo = (window as any).Echo;
        if (!echo) return;
        const channel = echo.private(`private-user.${auth.user.id}`);
        console.log('📡 Subscribed to:', `private-user.${auth.user.id}`);
        channel.subscribed(() => console.log('✅ Connected!'));
        channel.listen('.ingestion.status.updated', (e: any) => {
            console.log('📨 INGESTION:', e.status, '-', e.data?.message);
            setIngestionStatus({ status: e.status, message: e.data?.message, data: e.data });
        });
        channel.listen('.analysis.status.updated', (e: any) => {
            console.log('🧠 ANALYSIS:', e.status, '-', e.data?.message);
            setAnalysisStatus({ status: e.status, message: e.data?.message, data: e.data });
        });
        return () => {
            channel.stopListening('.ingestion.status.updated');
            channel.stopListening('.analysis.status.updated');
            echo.leave(`private-user.${auth.user.id}`);
        };
    }, [auth.user?.id]);

    const handleSubmit = () => {
        if (!prompt.trim()) return;

        setIsLoading(true);

        // Simulate API call
        console.log('Processing:', prompt);
        setTimeout(() => {
            setPrompt('');
            setIsLoading(false);
        }, 1500);
    };
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="mx-auto max-w-3xl pt-20 mb-4">
                <div className="mb-10 flex flex-col gap-4 p-4">
                    <div className="flex flex-col gap-4">
                        <h1 className="text-5xl font-normal">
                            Hello, <span className="font-spirax">{auth.user.name}!</span>
                        </h1>
                        {/* <p className="text-sm text-muted-foreground">Welcome to your dashboard</p> */}
                    </div>

                </div> 
                <div className="inset-x-0 top-5 mx-auto mt-5 min-w-3xl px-3 pb-3 md:pb-5 space-y-2">
                    {/* First Time Automation Status Card */}

                    {firstTimeAutomation && (
                        <Link
                            href={`/automations/${firstTimeAutomation.id}`}
                            className="group inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm transition-colors hover:bg-accent bg-primary-foreground w-fit"
                        >
                            <CheckCircle2 className="h-4 w-4 text-green-500 flex-shrink-0" />
                            <span className="text-muted-foreground">First-Time Cost Analysis Complete</span>
                            <ArrowUp className="h-4 w-4 rotate-45 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 flex-shrink-0" />
                        </Link>
                    )}
                    <PromptInput
                        isLoading={isLoading}
                        value={prompt}
                        onValueChange={setPrompt}
                        onSubmit={handleSubmit}
                        className="relative z-10 w-full rounded-3xl border-2 bg-primary-foreground p-0 pt-1 shadow-xs"
                    >
                        <div className="flex flex-col">
                            <PromptInputTextarea
                                placeholder="Ask anything"
                                className="min-h-[44px] pt-3 pl-4 text-base leading-[1.3] sm:text-base md:text-base"
                            />

                            <PromptInputActions className="mt-5 flex w-full items-center justify-between gap-2 px-3 pb-3">
                                <div className="flex items-center gap-2">
                                    <PromptInputAction tooltip="Add a new action">
                                        <Button variant="outline" size="icon" className="size-9 rounded-full">
                                            <Plus size={18} />
                                        </Button>
                                    </PromptInputAction>

                                    <PromptInputAction tooltip="Search">
                                        <Button variant="outline" className="rounded-full">
                                            <Globe size={18} />
                                            Search
                                        </Button>
                                    </PromptInputAction>

                                    <PromptInputAction tooltip="More actions">
                                        <Button variant="outline" size="icon" className="size-9 rounded-full">
                                            <MoreHorizontal size={18} />
                                        </Button>
                                    </PromptInputAction>
                                </div>
                                <div className="flex items-center gap-2">
                                    <PromptInputAction tooltip="Voice input">
                                        <Button variant="outline" size="icon" className="size-9 rounded-full">
                                            <Mic size={18} />
                                        </Button>
                                    </PromptInputAction>

                                    <Button size="icon" disabled={!prompt.trim() || isLoading} onClick={handleSubmit} className="size-9 rounded-full">
                                        {!isLoading ? <ArrowUp size={18} /> : <span className="size-3 rounded-xs bg-white" />}
                                    </Button>
                                </div>
                            </PromptInputActions>
                        </div>
                    </PromptInput>
                    
                    <IngestionStatusCard status={ingestionStatus.status} message={ingestionStatus.message} data={ingestionStatus.data} className="mt-4" />
                    <AnalysisStatusCard status={analysisStatus.status} message={analysisStatus.message} data={analysisStatus.data} className="mt-4" />
                </div>

                {/* Recent Automations Section */}
                <div className="flex flex-col pt-10">
                    <RecentAutomations
                        automations={recentAutomations}
                        totalCount={totalAutomations}
                    />
                </div>
                <div className="flex flex-col pt-10">
                    <WorkflowCards />
                </div>

            </div>
        </AppLayout>
    );
}