import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Database, CheckCircle2, Loader2, AlertCircle, XCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

interface IngestionStatusCardProps {
    status: 'idle' | 'started' | 'processing' | 'categorizing' | 'completed' | 'failed';
    message?: string;
    data?: {
        total_records?: number;
        categorized_records?: number;
        is_initial_sync?: boolean;
        error?: string;
    };
    className?: string;
}

const statusConfig = {
    idle: {
        icon: Database,
        iconColor: 'text-muted-foreground',
        badgeVariant: 'secondary' as const,
        badgeText: 'Idle',
        title: 'Data Ingestion',
        description: 'No active ingestion',
        animate: false,
    },
    started: {
        icon: Loader2,
        iconColor: 'text-blue-500',
        badgeVariant: 'default' as const,
        badgeText: 'Starting',
        title: 'Data Ingestion',
        description: 'Initializing data ingestion...',
        animate: true,
    },
    processing: {
        icon: Loader2,
        iconColor: 'text-blue-500',
        badgeVariant: 'default' as const,
        badgeText: 'Processing',
        title: 'Data Ingestion',
        description: 'Fetching financial data...',
        animate: true,
    },
    categorizing: {
        icon: Loader2,
        iconColor: 'text-purple-500',
        badgeVariant: 'default' as const,
        badgeText: 'Categorizing',
        title: 'Data Ingestion',
        description: 'Categorizing records...',
        animate: true,
    },
    completed: {
        icon: CheckCircle2,
        iconColor: 'text-green-500',
        badgeVariant: 'default' as const,
        badgeText: 'Complete',
        title: 'Data Ingestion',
        description: 'All data processed successfully',
        animate: false,
    },
    failed: {
        icon: XCircle,
        iconColor: 'text-red-500',
        badgeVariant: 'destructive' as const,
        badgeText: 'Failed',
        title: 'Data Ingestion',
        description: 'Ingestion failed',
        animate: false,
    },
};

export default function IngestionStatusCard({ 
    status, 
    message, 
    data,
    className 
}: IngestionStatusCardProps) {
    const config = statusConfig[status];
    const Icon = config.icon;
    const isActive = ['started', 'processing', 'categorizing'].includes(status);

    return (
        <Card className={cn(
            'transition-all duration-300',
            isActive && 'border-blue-500/50 shadow-lg',
            status === 'completed' && 'border-green-500/50',
            status === 'failed' && 'border-red-500/50',
            className
        )}>
            <CardHeader className="pb-3">
                <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <Icon className={cn(
                            'h-5 w-5',
                            config.iconColor,
                            config.animate && 'animate-spin'
                        )} />
                        {config.title}
                    </CardTitle>
                    <Badge variant={config.badgeVariant}>
                        {config.badgeText}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent>
                <div className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        {message || config.description}
                    </p>

                    {/* Progress details */}
                    {status === 'completed' && data && (
                        <div className="flex gap-4 pt-2 border-t">
                            {data.total_records !== undefined && (
                                <div className="flex-1">
                                    <p className="text-xs text-muted-foreground">Total Records</p>
                                    <p className="text-lg font-semibold">{data.total_records}</p>
                                </div>
                            )}
                            {data.categorized_records !== undefined && (
                                <div className="flex-1">
                                    <p className="text-xs text-muted-foreground">Categorized</p>
                                    <p className="text-lg font-semibold text-green-500">
                                        {data.categorized_records}
                                    </p>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Error details */}
                    {status === 'failed' && data?.error && (
                        <div className="flex items-start gap-2 p-3 rounded-lg bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/50">
                            <AlertCircle className="h-4 w-4 text-red-500 mt-0.5 flex-shrink-0" />
                            <p className="text-xs text-red-700 dark:text-red-400">
                                {data.error}
                            </p>
                        </div>
                    )}

                    {/* Processing indicator */}
                    {isActive && (
                        <div className="flex items-center gap-2 pt-2">
                            <div className="flex gap-1">
                                <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse" style={{ animationDelay: '0ms' }} />
                                <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse" style={{ animationDelay: '150ms' }} />
                                <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse" style={{ animationDelay: '300ms' }} />
                            </div>
                            <span className="text-xs text-muted-foreground">Processing in background...</span>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
