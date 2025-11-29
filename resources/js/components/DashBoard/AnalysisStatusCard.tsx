import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Brain, CheckCircle2, Loader2, AlertCircle, XCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

interface AnalysisStatusCardProps {
    status: 'idle' | 'started' | 'analyzing' | 'completed' | 'failed';
    message?: string;
    data?: {
        current_step?: number;
        total_steps?: number;
        automation_id?: number;
        error?: string;
    };
    className?: string;
}

const statusConfig = {
    idle: {
        icon: Brain,
        iconColor: 'text-muted-foreground',
        badgeVariant: 'secondary' as const,
        badgeText: 'Idle',
        title: 'Cost Analysis',
        description: 'No active analysis',
        animate: false,
    },
    started: {
        icon: Loader2,
        iconColor: 'text-blue-500',
        badgeVariant: 'default' as const,
        badgeText: 'Starting',
        title: 'Cost Analysis',
        description: 'Initializing analysis...',
        animate: true,
    },
    analyzing: {
        icon: Brain,
        iconColor: 'text-purple-500',
        badgeVariant: 'default' as const,
        badgeText: 'Analyzing',
        title: 'Cost Analysis',
        description: 'Running AI analysis...',
        animate: true,
    },
    completed: {
        icon: CheckCircle2,
        iconColor: 'text-green-500',
        badgeVariant: 'default' as const,
        badgeText: 'Complete',
        title: 'Cost Analysis',
        description: 'Analysis complete!',
        animate: false,
    },
    failed: {
        icon: XCircle,
        iconColor: 'text-red-500',
        badgeVariant: 'destructive' as const,
        badgeText: 'Failed',
        title: 'Cost Analysis',
        description: 'Analysis failed',
        animate: false,
    },
};

export default function AnalysisStatusCard({ 
    status, 
    message, 
    data,
    className 
}: AnalysisStatusCardProps) {
    const config = statusConfig[status];
    const Icon = config.icon;
    const isActive = ['started', 'analyzing'].includes(status);
    const progress = data?.current_step && data?.total_steps 
        ? (data.current_step / data.total_steps) * 100 
        : 0;

    return (
        <Card className={cn(
            'transition-all duration-300',
            isActive && 'border-purple-500/50 shadow-lg',
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
                            config.animate && status === 'started' && 'animate-spin',
                            config.animate && status === 'analyzing' && 'animate-pulse'
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

                    {/* Progress bar */}
                    {status === 'analyzing' && data?.current_step && data?.total_steps && (
                        <div className="space-y-2">
                            <div className="flex justify-between text-xs text-muted-foreground">
                                <span>Step {data.current_step} of {data.total_steps}</span>
                                <span>{Math.round(progress)}%</span>
                            </div>
                            <div className="w-full bg-secondary rounded-full h-2 overflow-hidden">
                                <div 
                                    className="bg-purple-500 h-full transition-all duration-500 ease-out"
                                    style={{ width: `${progress}%` }}
                                />
                            </div>
                        </div>
                    )}

                    {/* Completion message */}
                    {status === 'completed' && (
                        <div className="flex items-start gap-2 p-3 rounded-lg bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900/50">
                            <CheckCircle2 className="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                            <p className="text-xs text-green-700 dark:text-green-400">
                                Your comprehensive cost analysis is ready! Check your automations for the full report.
                            </p>
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
                                <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse" style={{ animationDelay: '0ms' }} />
                                <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse" style={{ animationDelay: '150ms' }} />
                                <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse" style={{ animationDelay: '300ms' }} />
                            </div>
                            <span className="text-xs text-muted-foreground">
                                {status === 'analyzing' ? 'Analyzing your costs...' : 'Preparing analysis...'}
                            </span>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
