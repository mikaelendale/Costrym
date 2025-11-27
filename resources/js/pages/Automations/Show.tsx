'use client';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
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

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={automation.name} />

            <div className="flex h-screen flex-col bg-background">

                {/* Two Column Layout */}
                <div className="flex-1 overflow-hidden">
                    <div className="mx-auto flex h-full max-w-[1600px] gap-6 py-6">

                        {/* Right Column - Metrics & Info (1/3 width) */}
                        <div className="flex-1 space-y-4 overflow-y-auto">
                            {/* Status Card */}

                            {/* Header */}
                            <div className="flex-shrink-0">
                                <div className="mx-auto max-w-[1600px] px-6 py-3">
                                    <div className="flex items-center gap-4"> 
                                        <div className="h-6 w-px bg-border" />
                                        <h1 className="text-xl font-semibold">{automation.name}</h1>
                                    </div>
                                </div>
                            </div>
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
                                </CardContent>
                            </Card>

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
                        </div>
                        {/* Left Column - Markdown Content (2/3 width) */}
                        <div className="flex-[2]">
                            <Card className="flex h-full flex-col rounded-lg bg-primary-foreground "> 
                                <CardContent className="flex-1 overflow-y-auto">
                                    <div className="prose prose-sm dark:prose-invert max-w-none">
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
