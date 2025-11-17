import { ArrowRight } from 'lucide-react';

export function CTASection() {
    return (
        <section className="bg-primary px-4 py-20 text-center text-primary-foreground sm:px-6 lg:px-8">
            <div className="mx-auto max-w-2xl">
                <h2 className="mb-6 text-4xl font-bold">Ready to cut costs?</h2>
                <p className="mb-8 text-lg opacity-90">Join leading companies saving millions with Costrym. Start optimizing in minutes.</p>
                <button className="inline-flex items-center gap-2 rounded-full bg-primary-foreground px-8 py-3 font-bold text-primary transition hover:bg-primary-foreground/90">
                    Get Started Free <ArrowRight className="h-4 w-4" />
                </button>
            </div>
        </section>
    );
}
