import { CheckCircle } from 'lucide-react';

export function EnterpriseSection() {
    const benefits = [
        'Our experts have collectively saved 1bn+ in manufacturing costs for their clients',
        "Expose spend leaks your finance team doesn't have time to chase",
        'Kill tiny costs that could save you millions',
        "Gain a 24/7 surgical layer over every department's costs",
        'Deliver measurable savings on scale — automatically',
    ];

    return (
        <section className="px-4 py-20 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
                <div className="mb-12">
                    <button className="mb-8 inline-block rounded-full border border-accent/50 bg-accent/30 px-6 py-2 text-sm font-medium">
                        Large Scale Enterprises
                    </button>
                    <h2 className="mb-4 text-4xl font-bold">Enterprise-Grade Cost Optimization</h2>
                    <p className="max-w-2xl text-lg text-muted-foreground">Built for companies serious about maximizing profitability at scale.</p>
                </div>

                <div className="rounded-2xl border border-border bg-card p-12">
                    <div className="space-y-6 text-muted-foreground">
                        {benefits.map((item, i) => (
                            <p key={i} className="flex items-start gap-3">
                                <CheckCircle className="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                                <span>✓ {item}</span>
                            </p>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
