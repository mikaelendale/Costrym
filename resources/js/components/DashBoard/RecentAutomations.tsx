import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { FileText, ArrowRight, Download, Calendar, TrendingUp, Sparkles } from 'lucide-react';

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
        records_analyzed?: number;
    } | null;
}

interface RecentAutomationsProps {
    automations: Automation[];
    totalCount: number;
}

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
        case 'first_time_cost_analysis':
            return '📊';
        default:
            return '📄';
    }
};

const getTypeBadgeColor = (type: string) => {
    switch (type) {
        case 'task_generation':
            return 'bg-blue-500 hover:bg-blue-600';
        case 'execution_report':
            return 'bg-green-500 hover:bg-green-600';
        case 'pipeline_stage':
            return 'bg-purple-500 hover:bg-purple-600';
        case 'pipeline_complete':
            return 'bg-emerald-500 hover:bg-emerald-600';
        case 'first_time_cost_analysis':
            return 'bg-orange-500 hover:bg-orange-600';
        default:
            return 'bg-gray-500 hover:bg-gray-600';
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
    });
};

const formatTypeLabel = (type: string) => {
    return type
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

export default function RecentAutomations({ automations, totalCount }: RecentAutomationsProps) {
    // Empty state
    if (automations.length === 0) {
        return (
            <div className="space-y-4">
                <div className="flex items-center justify-between px-4">
                    <div>
                        <h2 className="text-2xl font-semibold">Recent Reports & Analysis</h2>
                        <p className="text-sm text-muted-foreground mt-1">
                            Latest automation reports and cost analysis
                        </p>
                    </div>
                </div>

                <div className="px-4">
                    <Card className="border-dashed">
                        <CardContent className="flex flex-col items-center justify-center py-16">
                            <h3 className="text-lg font-semibold mb-2">No Reports Yet</h3>
                            <p className="text-sm text-muted-foreground text-center max-w-md mb-6">
                                Your automation reports and cost analysis will appear here once your first analysis completes.
                            </p>
                            <Link href="/automations">
                                <Button variant="outline">
                                    <FileText className="mr-2 h-4 w-4" />
                                    View All Reports
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between px-4">
                <div>
                    <h2 className="text-2xl font-semibold">Recent Reports & Analysis</h2>
                    <p className="text-sm text-muted-foreground mt-1">
                        Latest automation reports and cost analysis
                    </p>
                </div>
                <Link href="/automations">
                    <Button variant="outline" className="group">
                        View All ({totalCount})
                        <ArrowRight className="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </Button>
                </Link>
            </div>

            <div className="grid gap-4 px-4">
                {automations.map((automation) => (
                    <Link
                        key={automation.id}
                        href={`/automations/${automation.id}`}
                        className="block"
                    >
                        <Card className="transition-all hover:shadow-lg hover:border-primary/50 group">
                            <CardHeader className="pb-3">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex-1 space-y-2">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <span className="text-2xl">{getTypeIcon(automation.type)}</span>
                                            <CardTitle className="text-base leading-tight">
                                                {automation.name}
                                            </CardTitle>
                                            <Badge 
                                                className={`${getTypeBadgeColor(automation.type)} text-white text-xs`}
                                            >
                                                {formatTypeLabel(automation.type)}
                                            </Badge>
                                        </div>
                                        {automation.description && (
                                            <p className="text-sm text-muted-foreground line-clamp-2">
                                                {automation.description}
                                            </p>
                                        )}
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            window.location.href = `/automations/${automation.id}/download`;
                                        }}
                                    >
                                        <Download className="h-4 w-4" />
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                                    <div className="flex items-center gap-1.5">
                                        <Calendar className="h-3.5 w-3.5" />
                                        <span>{formatDate(automation.created_at)}</span>
                                    </div>

                                    {automation.metadata?.pipeline && (
                                        <div className="flex items-center gap-1.5">
                                            <FileText className="h-3.5 w-3.5" />
                                            <span>{automation.metadata.pipeline}</span>
                                        </div>
                                    )}

                                    {automation.metadata?.task_count && (
                                        <div className="flex items-center gap-1.5">
                                            <span className="text-base">📋</span>
                                            <span>{automation.metadata.task_count} tasks</span>
                                        </div>
                                    )}

                                    {automation.metadata?.records_analyzed && (
                                        <div className="flex items-center gap-1.5">
                                            <span className="text-base">📊</span>
                                            <span>{automation.metadata.records_analyzed} records</span>
                                        </div>
                                    )}

                                    {automation.metadata?.estimated_savings && (
                                        <div className="flex items-center gap-1.5 font-semibold text-green-600 dark:text-green-400 ml-auto">
                                            <TrendingUp className="h-3.5 w-3.5" />
                                            <span>${automation.metadata.estimated_savings.toLocaleString()}/mo</span>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                ))}
            </div>

            {totalCount > automations.length && (
                <div className="px-4">
                    <Link href="/automations">
                        <Button variant="outline" className="w-full group">
                            View {totalCount - automations.length} More Reports
                            <ArrowRight className="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </Button>
                    </Link>
                </div>
            )}
        </div>
    );
}
