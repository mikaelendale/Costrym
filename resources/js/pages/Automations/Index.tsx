import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { PageProps } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { FileText, Download, Archive, Search, Filter, Calendar, FileCode } from 'lucide-react';
import { useState } from 'react';

interface Automation {
    id: number;
    type: string;
    name: string;
    description: string | null;
    created_at: string;
    metadata: {
        pipeline?: string;
        task_count?: number;
        estimated_savings?: number;
        agent?: string;
        stage?: number;
    } | null;
}

interface AutomationsProps extends PageProps {
    automations: {
        data: Automation[];
        links: any[];
        meta: any;
    };
    filters: {
        type: string;
        search: string;
    };
    typeCounts: Record<string, number>;
}

export default function Index({ auth, automations, filters, typeCounts }: AutomationsProps) {
    const [search, setSearch] = useState(filters.search);
    const [selectedType, setSelectedType] = useState(filters.type);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/automations', { search, type: selectedType }, { preserveState: true });
    };

    const handleTypeFilter = (type: string) => {
        setSelectedType(type);
        router.get('/automations', { search, type }, { preserveState: true });
    };

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
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const totalAutomations = Object.values(typeCounts).reduce((sum, count) => sum + count, 0);

    return (
        <AppLayout>
            <Head title="Automations & Reports" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            📄 Automations & Reports
                        </h1>
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            View all generated reports, analysis, and automation workflows
                        </p>
                    </div>

                    {/* Stats Cards */}
                    <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <Card
                            className={`cursor-pointer transition-all hover:shadow-lg ${
                                selectedType === 'all' ? 'ring-2 ring-blue-500' : ''
                            }`}
                            onClick={() => handleTypeFilter('all')}
                        >
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium">All Reports</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{totalAutomations}</div>
                            </CardContent>
                        </Card>

                        <Card
                            className={`cursor-pointer transition-all hover:shadow-lg ${
                                selectedType === 'task_generation' ? 'ring-2 ring-blue-500' : ''
                            }`}
                            onClick={() => handleTypeFilter('task_generation')}
                        >
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium">📋 Task Gen</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{typeCounts.task_generation || 0}</div>
                            </CardContent>
                        </Card>

                        <Card
                            className={`cursor-pointer transition-all hover:shadow-lg ${
                                selectedType === 'execution_report' ? 'ring-2 ring-green-500' : ''
                            }`}
                            onClick={() => handleTypeFilter('execution_report')}
                        >
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium">🎯 Execution</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{typeCounts.execution_report || 0}</div>
                            </CardContent>
                        </Card>

                        <Card
                            className={`cursor-pointer transition-all hover:shadow-lg ${
                                selectedType === 'pipeline_stage' ? 'ring-2 ring-purple-500' : ''
                            }`}
                            onClick={() => handleTypeFilter('pipeline_stage')}
                        >
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium">🔄 Stages</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{typeCounts.pipeline_stage || 0}</div>
                            </CardContent>
                        </Card>

                        <Card
                            className={`cursor-pointer transition-all hover:shadow-lg ${
                                selectedType === 'pipeline_complete' ? 'ring-2 ring-emerald-500' : ''
                            }`}
                            onClick={() => handleTypeFilter('pipeline_complete')}
                        >
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium">✅ Complete</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{typeCounts.pipeline_complete || 0}</div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Search Bar */}
                    <Card className="mb-6">
                        <CardContent className="pt-6">
                            <form onSubmit={handleSearch} className="flex gap-2">
                                <div className="relative flex-1">
                                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <Input
                                        type="text"
                                        placeholder="Search automations..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                                <Button type="submit">
                                    <Filter className="mr-2 h-4 w-4" />
                                    Filter
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Automations List */}
                    <div className="space-y-4">
                        {automations.data.length === 0 ? (
                            <Card>
                                <CardContent className="py-12 text-center">
                                    <FileText className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                        No automations found
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Try adjusting your filters or search query
                                    </p>
                                </CardContent>
                            </Card>
                        ) : (
                            automations.data.map((automation) => (
                                <Card
                                    key={automation.id}
                                    className="cursor-pointer transition-all hover:shadow-lg"
                                    onClick={() => router.visit(`/automations/${automation.id}`)}
                                >
                                    <CardHeader>
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-2xl">{getTypeIcon(automation.type)}</span>
                                                    <CardTitle className="text-lg">{automation.name}</CardTitle>
                                                    <Badge className={`${getTypeBadgeColor(automation.type)} text-white`}>
                                                        {automation.type.replace('_', ' ')}
                                                    </Badge>
                                                </div>
                                                {automation.description && (
                                                    <CardDescription className="mt-2">
                                                        {automation.description}
                                                    </CardDescription>
                                                )}
                                            </div>
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        router.get(`/automations/${automation.id}/download`);
                                                    }}
                                                >
                                                    <Download className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        if (confirm('Archive this automation?')) {
                                                            router.post(`/automations/${automation.id}/archive`);
                                                        }
                                                    }}
                                                >
                                                    <Archive className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="flex items-center gap-6 text-sm text-gray-500">
                                            <div className="flex items-center gap-1">
                                                <Calendar className="h-4 w-4" />
                                                {formatDate(automation.created_at)}
                                            </div>
                                            {automation.metadata?.pipeline && (
                                                <div className="flex items-center gap-1">
                                                    <FileCode className="h-4 w-4" />
                                                    Pipeline: {automation.metadata.pipeline}
                                                </div>
                                            )}
                                            {automation.metadata?.task_count && (
                                                <div>📋 {automation.metadata.task_count} tasks</div>
                                            )}
                                            {automation.metadata?.estimated_savings && (
                                                <div className="font-semibold text-green-600">
                                                    💰 ${automation.metadata.estimated_savings}/month
                                                </div>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            ))
                        )}
                    </div>

                    {/* Pagination */}
                    {automations.links && automations.links.length > 3 && (
                        <div className="mt-6 flex justify-center gap-2">
                            {automations.links.map((link, index) => (
                                <Button
                                    key={index}
                                    variant={link.active ? 'default' : 'outline'}
                                    size="sm"
                                    disabled={!link.url}
                                    onClick={() => link.url && router.visit(link.url)}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

