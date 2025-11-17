import { Card } from '@/components/ui/card';

const STATS: string[] = [
    'It gives every company the same cost-cutting power the giants use',
    'Runs 24/7, hunts down waste, and cuts it out.',
    'Tiny leaks.Overpriced Raw materials. Inflated subscriptions. Forgotten tools. Cloud drift. Idle seats',
];

export default function WhatWeDo() {
    return (
        <section className="gap-8md:py-20 flex flex-col items-center justify-center">
            <h2 className="text-[clamp(1.75rem,5vw,2.5rem)] leading-none font-bold tracking-tight whitespace-nowrap text-foreground">What we Do</h2>
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
                            <p className="mt-2 text-2xl text-muted-foreground sm:text-base">{s}</p>
                        </div>
                    ))}
                </Card>
            </div>
        </section>
    );
}
