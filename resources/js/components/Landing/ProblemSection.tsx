'use client';

export default function ProblemSection() {
    return (
        <section className="w-full bg-background py-24 text-foreground">
            <div className="container mx-auto px-6">
                <div className="mx-auto space-y-6">
                    <h2 className="text-3xl leading-tight font-bold md:text-4xl">
                        Until now, true cost engineering was something only Amazon, Apple, GM, and Fortune-500 finance teams could afford.
                    </h2>

                    <p className="text-lg text-muted-foreground">
                        They have dedicated analysts, cost specialists, and internal engines constantly optimizing spend. Their systems run
                        continuously — catching leaks, modeling future costs, and eliminating inefficiencies before they become expensive problems.
                    </p>

                    <div className="rounded-xl border border-border bg-secondary p-6 text-secondary-foreground">
                        <p className="text-base leading-relaxed">
                            Other businesses never had access to that level of expertise because it was unaffordable — and the specialists never
                            wanted to work with smaller companies. This left most businesses spending blindly, without the tools to detect the waste
                            draining their profitability.
                        </p>
                    </div>

                    <p className="text-lg text-muted-foreground">
                        Costrym changes that. For the first time, cost-engineering intelligence previously kept behind enterprise walls becomes
                        available to every company — without hiring analysts or building internal systems.
                    </p>
                </div>
            </div>
        </section>
    );
}
