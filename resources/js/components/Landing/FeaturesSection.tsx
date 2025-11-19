import { BarChart3, Target, Zap } from 'lucide-react';

export function FeaturesSection() {
    const features = [
        {
            icon: <Zap className="h-8 w-8" />,
            title: 'Intelligent Detection',
            description: 'Runs 24/7, hunting down waste, and cuts it out with surgical precision.',
        },
        {
            icon: <BarChart3 className="h-8 w-8" />,
            title: 'Real-time Analytics',
            description: "Get comprehensive visibility into every department's costs and spending patterns.",
        },
        {
            icon: <Target className="h-8 w-8" />,
            title: 'Targeted Optimization',
            description: 'Kill tiny costs that could save you millions and expose spending leaks.',
        },
    ];

    return (
        <section className="px-4 py-20 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
                <h2 className="mb-4 text-center text-4xl font-bold">What We Do</h2>
                <p className="mx-auto mb-16 max-w-2xl text-center text-muted-foreground">
                    Our AI-powered platform gives every company enterprise-grade cost optimization capabilities
                </p>

                <div className="grid grid-cols-1 gap-8 md:grid-cols-3">
                    {features.map((feature, i) => (
                        <div key={i} className="rounded-xl border border-border p-8 transition hover:bg-accent/20">
                            <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                {feature.icon}
                            </div>
                            <h3 className="mb-3 text-xl font-bold">{feature.title}</h3>
                            <p className="leading-relaxed text-muted-foreground">{feature.description}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
