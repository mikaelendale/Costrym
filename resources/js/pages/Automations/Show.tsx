import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { PageProps } from '@/types';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Download, Archive, Calendar, Tag } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
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

interface ShowProps extends PageProps {
    automation: Automation;
}

export default function Show({ auth, automation }: ShowProps) {
    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'task_generation':
                return '📋';
            case 'execution_report':
                return '🎯';
            case 'pipeline_stage':
                return '🔄';
            case 'pipeline_complete':
                return '✅';
            default:
                return '📄';
        }
    };

    const getTypeBadgeColor = (type: string) => {
        switch (type) {
            case 'task_generation':
                return 'bg-blue-500';
            case 'execution_report':
                return 'bg-green-500';
            case 'pipeline_stage':
                return 'bg-purple-500';
            case 'pipeline_complete':
                return 'bg-emerald-500';
            default:
                return 'bg-gray-500';
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AppLayout>
            <Head title={automation.name} />

            <div className="py-12">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    {/* Back Button */}
                    <Link href="/automations" className="mb-6 inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Back to Automations
                    </Link>

                    {/* Header Card */}
                    <Card className="mb-6">
                        <CardContent className="pt-6">
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <div className="flex items-center gap-3">
                                        <span className="text-3xl">{getTypeIcon(automation.type)}</span>
                                        <div>
                                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                                {automation.name}
                                            </h1>
                                            {automation.description && (
                                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                    {automation.description}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Metadata */}
                                    <div className="mt-4 flex flex-wrap gap-4">
                                        <Badge className={`${getTypeBadgeColor(automation.type)} text-white`}>
                                            {automation.type.replace('_', ' ')}
                                        </Badge>
                                        
                                        <div className="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400">
                                            <Calendar className="h-4 w-4" />
                                            {formatDate(automation.created_at)}
                                        </div>

                                        {automation.metadata?.pipeline && (
                                            <Badge variant="outline">
                                                <Tag className="mr-1 h-3 w-3" />
                                                {automation.metadata.pipeline}
                                            </Badge>
                                        )}

                                        {automation.metadata?.task_count && (
                                            <Badge variant="outline">
                                                📋 {automation.metadata.task_count} tasks
                                            </Badge>
                                        )}

                                        {automation.metadata?.estimated_savings && (
                                            <Badge className="bg-green-600 text-white">
                                                💰 ${automation.metadata.estimated_savings}/month
                                            </Badge>
                                        )}

                                        {automation.metadata?.stage && (
                                            <Badge variant="outline">
                                                Stage {automation.metadata.stage}
                                            </Badge>
                                        )}

                                        {automation.metadata?.successful_stages !== undefined && (
                                            <Badge variant="outline">
                                                ✅ {automation.metadata.successful_stages} / {automation.metadata.successful_stages + (automation.metadata.failed_stages || 0)} stages
                                            </Badge>
                                        )}
                                    </div>
                                </div>

                                {/* Actions */}
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={() => router.get(`/automations/${automation.id}/download`)}
                                    >
                                        <Download className="mr-2 h-4 w-4" />
                                        Download
                                    </Button>
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            if (confirm('Archive this automation?')) {
                                                router.post(`/automations/${automation.id}/archive`, {}, {
                                                    onSuccess: () => router.visit('/automations'),
                                                });
                                            }
                                        }}
                                    >
                                        <Archive className="mr-2 h-4 w-4" />
                                        Archive
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Markdown Content */}
                    <Card>
                        <CardContent className="pt-6">
                            <div className="prose prose-slate max-w-none dark:prose-invert prose-headings:font-bold prose-h1:text-3xl prose-h2:text-2xl prose-h3:text-xl prose-p:text-gray-700 dark:prose-p:text-gray-300 prose-a:text-blue-600 prose-strong:text-gray-900 dark:prose-strong:text-white prose-code:text-pink-600 prose-code:bg-gray-100 dark:prose-code:bg-gray-800 prose-code:px-1 prose-code:py-0.5 prose-code:rounded prose-pre:bg-gray-900 prose-pre:text-gray-100 prose-table:border-collapse prose-th:border prose-th:border-gray-300 dark:prose-th:border-gray-700 prose-th:bg-gray-100 dark:prose-th:bg-gray-800 prose-th:p-2 prose-td:border prose-td:border-gray-300 dark:prose-td:border-gray-700 prose-td:p-2">
                                <ReactMarkdown remarkPlugins={[remarkGfm]}>
                                    {automation.markdown_content}
                                </ReactMarkdown>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

