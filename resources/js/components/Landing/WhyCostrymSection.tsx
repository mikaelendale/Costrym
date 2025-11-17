export function WhyCostrymSection() {
    return (
        <section className="border-y border-border bg-card px-4 py-20 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-3xl text-center">
                <h2 className="mb-8 text-4xl font-bold">Why Costrym Exists</h2>

                <div className="space-y-6 text-left">
                    <p className="text-lg leading-relaxed text-muted-foreground">
                        Until now, true cost engineering was something only Amazon, Apple, GM, and Fortune-500 finance teams could afford.
                    </p>

                    <p className="text-lg leading-relaxed text-muted-foreground">
                        They have dedicated analysts, cost specialists, and internal engines constantly optimizing spend. Other businesses never had
                        access to that level of expertise because it was unaffordable and the experts didn't really want to work for smaller
                        companies.
                    </p>

                    <p className="mt-8 text-lg font-semibold text-foreground">Costrym changes that.</p>

                    <div className="mt-6 space-y-3">
                        <div className="flex gap-3">
                            <span className="font-bold text-primary">→</span>
                            <p className="text-muted-foreground">It gives every company the same cost-cutting power the giants use.</p>
                        </div>
                        <div className="flex gap-3">
                            <span className="font-bold text-primary">→</span>
                            <p className="text-muted-foreground">Runs 24/7, hunts down waste, and cuts it out.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
