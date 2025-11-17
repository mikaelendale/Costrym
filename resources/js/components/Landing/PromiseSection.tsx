export function PromiseSection() {
    return (
        <section className="bg-primary px-4 py-20 text-primary-foreground sm:px-6 lg:px-8">
            <div className="mx-auto max-w-3xl text-center">
                <h2 className="mb-8 text-4xl font-bold">A simple promise</h2>

                <p className="mb-12 text-xl opacity-90">You only pay if Costrym saves you money.</p>

                <div className="space-y-6">
                    <div className="rounded-xl border border-primary-foreground/20 bg-primary-foreground/10 p-8 backdrop-blur-sm">
                        <h3 className="mb-2 text-2xl font-bold">No savings → Full Refund</h3>
                        <p className="mb-6 text-primary-foreground/80">Simple. Fair. Zero-risk.</p>
                        <p className="font-semibold">Most companies see savings in the first 3–10 days.</p>
                    </div>

                    <button className="w-full rounded-full bg-primary-foreground px-8 py-3 font-medium text-primary transition hover:bg-primary-foreground/90 sm:w-auto">
                        Request Access
                    </button>
                </div>
            </div>
        </section>
    );
}
