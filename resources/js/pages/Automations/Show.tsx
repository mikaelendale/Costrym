'use client';

import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { GoogleDrive } from 'brand-logos';
import { ArrowLeft, Sparkles, Zap, FileDown } from 'lucide-react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';

interface Automation {
    id: number;
    type: string;
    name: string;
    description: string | null;
    markdown_content: string;
    created_at: string;
    metadata: {
        pipeline?: string;
        task_count?: number;
        estimated_savings?: number;
        agent?: string;
        stage?: number;
        successful_stages?: number;
        failed_stages?: number;
    } | null;
    task?: {
        id: number;
        data: {
            name: string;
        };
    } | null;
    task_queue?: {
        id: number;
    } | null;
}

export default function Show({ automation }: { automation: Automation }) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Automations',
            href: '/automations',
        },
        {
            title: automation.name,
            href: `/automations/${automation.id}`,
        },
    ];
    const getTypeBadgeVariant = (type: string) => {
        switch (type) {
            case 'task_generation':
                return 'default';
            case 'execution_report':
                return 'secondary';
            case 'pipeline_stage':
                return 'outline';
            case 'pipeline_complete':
                return 'default';
            default:
                return 'outline';
        }
    };

    const [executionStatus, setExecutionStatus] = useState<'idle' | 'executing' | 'success' | 'error'>('idle');
    const [executionMessage, setExecutionMessage] = useState<string | null>(null);

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const handleExecute = () => {
        if (executionStatus === 'executing') return;

        setExecutionStatus('executing');
        setExecutionMessage('Executing this automation using your connected integrations...');

        router.post(`/automations/${automation.id}/execute`, {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                // Try to surface any flash message from backend if available
                const props: any = page.props || {};
                const flash = props.flash || {};
                const msg = flash.success || flash.error || 'Execution completed. Check your systems for applied changes.';

                setExecutionStatus(flash.error ? 'error' : 'success');
                setExecutionMessage(msg);
            },
            onError: () => {
                setExecutionStatus('error');
                setExecutionMessage('Execution failed. Please ensure you have required integrations connected and try again.');
            },
        });
    };

    // Auto-clear transient status message after a while (but keep last result in UI for a bit)
    useEffect(() => {
        if (executionStatus === 'success' || executionStatus === 'error') {
            const timeout = setTimeout(() => {
                setExecutionStatus('idle');
                setExecutionMessage(null);
            }, 10000);

            return () => clearTimeout(timeout);
        }
    }, [executionStatus]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={automation.name} />

            <div className="flex h-screen flex-col bg-background">

                {/* Two Column Layout */}
                <div className="flex-1 overflow-hidden">
                    <div className="mx-auto flex h-full max-w-[1600px] gap-6 py-6">

                        {/* Right Column - Metrics & Info (1/3 width) */}
                        <div className="flex-1 flex flex-col space-y-4 overflow-y-auto">
                            {/* Header */}
                            <div className="flex-shrink-0">
                                <div className="mx-auto max-w-[1600px] py-3">
                                    <div className="flex items-center gap-4"> 
                                        <div className="h-6 w-px bg-border" />
                                        <h1 className="text-xl underline underline-offset-4 font-normal">{automation.name}</h1>
                                    </div>
                                </div>
                            </div>

                            {/* Metrics Card */}
                            {(automation.metadata?.task_count || automation.metadata?.estimated_savings) && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base">Metrics</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {automation.metadata?.estimated_savings && (
                                            <div>
                                                <div className="text-xs text-muted-foreground">Estimated Savings</div>
                                                <div className="mt-1 text-2xl font-bold text-green-600 dark:text-green-500">
                                                    ${automation.metadata.estimated_savings}
                                                    <span className="text-sm font-normal text-muted-foreground">/month</span>
                                                </div>
                                            </div>
                                        )}
                                        {automation.metadata?.task_count && (
                                            <div>
                                                <div className="text-xs text-muted-foreground">Total Tasks</div>
                                                <div className="mt-1 text-2xl font-bold">{automation.metadata.task_count}</div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            )}

                            {/* Pipeline Progress Card */}
                            {(automation.metadata?.stage || automation.metadata?.successful_stages !== undefined) && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base">Pipeline Progress</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {automation.metadata?.stage && (
                                            <div className="flex items-center justify-between">
                                                <span className="text-sm text-muted-foreground">Current Stage</span>
                                                <Badge variant="outline">Stage {automation.metadata.stage}</Badge>
                                            </div>
                                        )}
                                        {automation.metadata?.successful_stages !== undefined && (
                                            <>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm text-muted-foreground">Successful</span>
                                                    <span className="font-semibold">{automation.metadata.successful_stages}</span>
                                                </div>
                                                {automation.metadata?.failed_stages !== undefined && automation.metadata.failed_stages > 0 && (
                                                    <div className="flex items-center justify-between">
                                                        <span className="text-sm text-muted-foreground">Failed</span>
                                                        <span className="font-semibold">{automation.metadata.failed_stages}</span>
                                                    </div>
                                                )}
                                                <div className="pt-2">
                                                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                                                        <div
                                                            className="h-full bg-primary transition-all"
                                                            style={{
                                                                width: `${(automation.metadata.successful_stages /
                                                                    (automation.metadata.successful_stages +
                                                                        (automation.metadata.failed_stages || 0))) *
                                                                    100
                                                                    }%`,
                                                            }}
                                                        />
                                                    </div>
                                                </div>
                                            </>
                                        )}
                                    </CardContent>
                                </Card>
                            )}

                            {/* Related Task Card */}
                            {automation.task && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base">Related Task</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <Link
                                            href={`/tasks/${automation.task.id}`}
                                            className="block text-sm text-primary hover:underline"
                                        >
                                            {automation.task.data.name}
                                        </Link>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Spacer to push bottom cards down */}
                            <div className="flex-1" />


                            {/* Status Card - Moved to bottom */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Status</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-muted-foreground">Type</span>
                                        <Badge variant={getTypeBadgeVariant(automation.type)}>
                                            {automation.type.replace(/_/g, ' ')}
                                        </Badge>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-muted-foreground">Created</span>
                                        <span className="text-sm">{formatDate(automation.created_at)}</span>
                                    </div>
                                    {automation.metadata?.pipeline && (
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-muted-foreground">Pipeline</span>
                                            <Badge variant="outline">{automation.metadata.pipeline}</Badge>
                                        </div>
                                    )}

                                    {/* Export Options */}
                                    <div className="space-y-3 border-t pt-4">
                                        <div className="flex items-center gap-2">
                                            <FileDown className="h-4 w-4 text-muted-foreground" />
                                            <span className="text-sm font-medium text-foreground">Export to</span>
                                        </div>
                                        
                                        <div className="space-y-2">
                                            {/* Notion */}
                                            <button className="group flex w-full items-center gap-3 rounded-lg border border-border bg-background px-3 py-2 transition-all hover:border-primary/50 hover:bg-accent">
                                                <div className="flex h-8 w-8 items-center justify-center rounded bg-black dark:bg-white">
                                                    <svg className="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                                        <path className="text-white dark:text-black" d="M4.459 4.208c.746.606 1.026.56 2.428.466l13.215-.793c.28 0 .047-.28-.046-.326L17.86 1.968c-.42-.326-.981-.7-2.055-.607L3.01 2.295c-.466.046-.56.28-.374.466zm.793 3.08v13.904c0 .747.373 1.027 1.214.98l14.523-.84c.841-.046.935-.56.935-1.167V6.354c0-.606-.233-.933-.748-.887l-15.177.887c-.56.047-.747.327-.747.933zm14.337.745c.093.42 0 .84-.42.888l-.7.14v10.264c-.608.327-1.168.514-1.635.514-.748 0-.935-.234-1.495-.933l-4.577-7.186v6.952L12.21 19s0 .84-1.168.84l-3.222.186c-.093-.186 0-.653.327-.746l.84-.233V9.854L7.822 9.76c-.094-.42.14-1.026.793-1.073l3.456-.233 4.764 7.279v-6.44l-1.215-.139c-.093-.514.28-.887.747-.933zM1.936 1.035l13.31-.98c1.634-.14 2.055-.047 3.082.7l4.249 2.986c.7.513.934.653.934 1.213v16.378c0 1.026-.373 1.634-1.68 1.726l-15.458.934c-.98.047-1.448-.093-1.962-.747l-3.129-4.06c-.56-.747-.793-1.306-.793-1.96V2.667c0-.839.374-1.54 1.447-1.632z"/>
                                                    </svg>
                                                </div>
                                                <span className="text-sm font-medium">Notion</span>
                                            </button>

                                            {/* Google Docs */}
                                            <button className="group flex w-full items-center gap-3 rounded-lg border border-border bg-background px-3 py-2 transition-all hover:border-primary/50 hover:bg-accent">
                                                <div className="flex h-8 w-8 items-center justify-center rounded ">
                                                    <GoogleDrive className="h-5 w-5" />
                                                </div>
                                                <span className="text-sm font-medium">Google Docs</span>
                                            </button>

                                            {/* Markdown */}
                                            <button className="group flex w-full items-center gap-3 rounded-lg border border-border bg-background px-3 py-2 transition-all hover:border-primary/50 hover:bg-accent">
                                                <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-gray-700 to-gray-900">
                                                    <svg className="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                                        <path className="text-white" d="M22.27 19.385H1.73A1.73 1.73 0 0 1 0 17.655V6.345a1.73 1.73 0 0 1 1.73-1.73h20.54A1.73 1.73 0 0 1 24 6.345v11.308a1.73 1.73 0 0 1-1.73 1.731zM5.769 15.923v-4.5l2.308 2.885 2.307-2.885v4.5h2.308V8.077h-2.308l-2.307 2.885-2.308-2.885H3.46v7.846zm13.846-4.5H17.31v-1.5h-2.308v1.5h-2.307l3.461 4.5z"/>
                                                    </svg>
                                                </div>
                                                <span className="text-sm font-medium">Markdown</span>
                                            </button>

                                            {/* PDF */}
                                            <button className="group flex w-full items-center gap-3 rounded-lg border border-border bg-background px-3 py-2 transition-all hover:border-primary/50 hover:bg-accent">
                                                <div className="flex h-8 w-8 items-center justify-center rounded bg-[#F40F02]">
                                                    <svg className="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                                        <path className="text-white" d="M8.267 14.68c-.184 0-.308.018-.372.036v1.178c.076.018.171.023.302.023.479 0 .774-.242.774-.651 0-.366-.254-.586-.704-.586zm3.487.012c-.2 0-.33.018-.407.036v2.61c.077.018.201.018.313.018.817.006 1.349-.444 1.349-1.396.006-.83-.479-1.268-1.255-1.268z"/>
                                                        <path className="text-white" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM9.498 16.19c-.309.29-.765.42-1.296.42a2.23 2.23 0 0 1-.308-.018v1.426H7v-3.936A7.558 7.558 0 0 1 8.219 14c.557 0 .953.106 1.22.319.254.202.426.533.426.923-.001.392-.131.723-.367.948zm3.807 1.355c-.42.349-1.059.515-1.84.515-.468 0-.799-.03-1.024-.06v-3.917A7.947 7.947 0 0 1 11.66 14c.757 0 1.249.136 1.633.426.415.308.675.799.675 1.504 0 .763-.279 1.29-.663 1.615zM17 14.77h-1.532v.911H16.9v.734h-1.432v1.604h-.906V14.03H17v.74zM14 9h-1V4l5 5h-4z"/>
                                                    </svg>
                                                </div>
                                                <span className="text-sm font-medium">PDF</span>
                                            </button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            {/* Execute Report Card */}
                            <Card className="border-border">
                                <CardContent className="py-0">
                                    <div className="flex items-center justify-between gap-4">
                                        <p className="text-sm text-muted-foreground">
                                            Apply the recommendations from this analysis
                                        </p>
                                        <button
                                            type="button"
                                            onClick={handleExecute}
                                            disabled={executionStatus === 'executing'}
                                            className="flex-shrink-0 rounded-md bg-primary px-4 py-1.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60 disabled:cursor-not-allowed"
                                        >
                                            {executionStatus === 'executing' ? 'Executing…' : 'Execute'}
                                        </button>
                                    </div>
                                    {executionMessage && (
                                        <p className="mt-2 text-xs text-muted-foreground">
                                            {executionMessage}
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                        {/* Left Column - Markdown Content (2/3 width) */}
                        <div className="flex-[2]">
                            <Card className="flex h-full flex-col rounded-lg bg-primary-foreground shadow-none border-none"> 
                                <CardContent className="flex-1 overflow-y-auto">
                                    <div className="prose prose-sm dark:prose-invert max-w-none px-15 py-10">
                                        <ReactMarkdown
                                            remarkPlugins={[remarkGfm]}
                                            components={{
                                                h1: ({ children }) => (
                                                    <h1 className="mb-6 text-4xl font-bold tracking-tight text-foreground">{children}</h1>
                                                ),
                                                h2: ({ children }) => {
                                                    const text = children?.toString() || '';
                                                    const slug = text
                                                        .toLowerCase()
                                                        .replace(/[^a-z0-9]+/g, '-')
                                                        .replace(/(^-|-$)/g, '');
                                                    return (
                                                        <h2 id={slug} className="mb-4 mt-12 scroll-mt-20 text-2xl font-bold text-foreground first:mt-0">
                                                            {children}
                                                        </h2>
                                                    );
                                                },
                                                h3: ({ children }) => <h3 className="mb-4 mt-8 text-xl font-semibold text-foreground">{children}</h3>,
                                                h4: ({ children }) => <h4 className="mb-3 mt-6 text-lg font-semibold text-foreground">{children}</h4>,
                                                p: ({ children }) => <p className="mb-4 leading-relaxed text-muted-foreground">{children}</p>,
                                                ul: ({ children }) => <ul className="mb-6 space-y-2 pl-6">{children}</ul>,
                                                li: ({ children }) => (
                                                    <li className="flex items-start">
                                                        <span className="mr-3 mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-primary" />
                                                        <span className="leading-relaxed text-muted-foreground">{children}</span>
                                                    </li>
                                                ),
                                                strong: ({ children }) => <strong className="font-semibold text-foreground">{children}</strong>,
                                                code: ({ children }) => (
                                                    <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-sm text-foreground">{children}</code>
                                                ),
                                                pre: ({ children }) => (
                                                    <pre className="mb-6 overflow-x-auto rounded-lg bg-muted p-4">
                                                        <code className="font-mono text-sm text-foreground">{children}</code>
                                                    </pre>
                                                ),
                                                a: ({ href, children }) => (
                                                    <a href={href} className="text-primary underline-offset-4 hover:underline" target="_blank" rel="noopener noreferrer">
                                                        {children}
                                                    </a>
                                                ),
                                                blockquote: ({ children }) => (
                                                    <blockquote className="border-l-4 border-primary pl-4 italic text-muted-foreground">{children}</blockquote>
                                                ),
                                                table: ({ children }) => <table className="w-full border-collapse border border-border">{children}</table>,
                                                th: ({ children }) => <th className="border border-border bg-muted p-2 text-left">{children}</th>,
                                                td: ({ children }) => <td className="border border-border p-2">{children}</td>,
                                            }}
                                        >
                                            {automation.markdown_content}
                                        </ReactMarkdown>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
