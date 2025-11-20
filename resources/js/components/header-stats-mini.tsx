import { cn } from '@/lib/utils';
import { DollarSign, PiggyBank, TrendingUp } from 'lucide-react';

type HeaderStatsMiniProps = {
    className?: string;
    totalSavedMonthly?: number;
    totalSavedYearly?: number;
    activeOptimizations?: number;
};

export function HeaderStatsMini({
    className,
    totalSavedMonthly = 342_567,
    totalSavedYearly = 342_567 * 12,
    activeOptimizations = 18,
}: HeaderStatsMiniProps) {
    const items = [
        {
            icon: DollarSign,
            value: `$${totalSavedMonthly.toLocaleString()}`,
            label: 'Saved/mo',
        },
        {
            icon: TrendingUp,
            value: `$${totalSavedYearly.toLocaleString()}`,
            label: 'Saved/yr',
        },
        {
            icon: PiggyBank,
            value: activeOptimizations.toLocaleString(),
            label: 'Optimizations',
        },
    ];

    return (
        <div
            className={cn(
                'flex items-center gap-2 rounded-xl border border-sidebar-border/60 bg-accent/20 px-2 py-1 text-[10px] leading-none text-muted-foreground backdrop-blur-sm',
                'shadow-sm transition-colors hover:bg-accent/30',
                className,
            )}
        >
            {items.map((item, idx) => (
                <div key={idx} className="flex items-center gap-1">
                    <item.icon className="h-3 w-3 text-foreground/80" />
                    <span className="text-lg font-semibold text-foreground/90">{item.value}</span>
                    <span className="text-[12px] text-muted-foreground/80">{item.label}</span>
                    {idx < items.length - 1 && <span className="mx-1 h-3 w-px bg-sidebar-border/70" />}
                </div>
            ))}
        </div>
    );
}

export default HeaderStatsMini;
