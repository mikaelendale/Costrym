import { Card } from '@/components/ui/card';

type Stat = {
    value: string;
    label: string;
};

const STATS: Stat[] = [
    { value: '15%', label: 'Cut Costs' },
    { value: '56%', label: 'Conversion rate' },
    { value: '1,200,000$', label: 'Saved expense' },
];

export default function StatsSection() {
    return (
        <section className="py-12 md:py-20">
            <div className="w-fullpx-4 sm:px-6">
                <Card
                    role="list"
                    aria-label="Key product stats"
                    className={[
                        'grid grid-cols-3',
                        // Equal width and spacing
                        'gap-2 sm:gap-4 md:gap-6',
                        'p-3 sm:p-4 md:p-6',
                        // Vertical dividers between items
                        'divide-x',
                        'border-0',
                        'shadow-none',
                    ].join(' ')}
                >
                    {STATS.map((s, i) => (
                        <div key={i} role="listitem" className="flex flex-col items-center justify-center px-3 text-center">
                            <div
                                className={[
                                    'font-semibold tracking-tight whitespace-nowrap text-foreground',
                                    // Fluid font size
                                    'text-[clamp(1.75rem,5vw,2.5rem)] leading-none',
                                ].join(' ')}
                            >
                                {s.value}
                            </div>
                            <p className="mt-2 text-sm text-muted-foreground sm:text-base">{s.label}</p>
                        </div>
                    ))}
                </Card>
            </div>
        </section>
    );
}
