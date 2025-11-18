export function RequirementsSection() {
    return (
        <section id="requirements" className="border-y border-border bg-card px-4 py-20 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-3xl">
                <div className="mb-12 text-center">
                    <h2 className="mb-4 text-4xl font-bold">See requirements page</h2>
                    <p className="text-lg text-muted-foreground">Costrym isn't built for everyone — and it doesn't pretend to be.</p>
                </div>

                <div className="mb-8 rounded-xl border border-border bg-background p-8">
                    <h3 className="mb-4 text-2xl font-bold">Minimum Requirements to become a user:</h3>

                    <div className="mb-8 space-y-4">
                        <div className="flex gap-4">
                            <div className="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-primary" />
                            <div>
                                <p className="font-semibold text-foreground">$1,000+ in monthly expenses</p>
                            </div>
                        </div>
                        <div className="flex gap-4">
                            <div className="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-primary" />
                            <div>
                                <p className="font-semibold text-foreground">At least 3 months of Operations with expenses</p>
                            </div>
                        </div>
                    </div>

                    <p className="mb-6 leading-relaxed text-muted-foreground">
                        We only accept companies where Costrym can produce serious savings. If you're too early or too low-spend, it's not worth your
                        time.
                    </p>

                    <p className="leading-relaxed text-muted-foreground">
                        If you meet the bar, you're in the right place — and Costrym will cut your costs aggressively.
                    </p>
                </div>

                <div className="flex flex-col justify-center gap-4 sm:flex-row">
                    <button className="rounded-full bg-primary px-8 py-3 font-medium text-primary-foreground transition hover:bg-primary/90">
                        Request Access
                    </button>
                    <button className="rounded-full border border-border px-8 py-3 font-medium transition hover:bg-accent/30">
                        Schedule consultation
                    </button>
                </div>

                <p className="mt-6 text-center text-sm text-muted-foreground">If not, get in touch and we'll see how we can help</p>
            </div>
        </section>
    );
}
