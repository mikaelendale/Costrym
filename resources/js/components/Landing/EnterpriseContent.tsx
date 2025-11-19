'use client';

import { ArrowRight, CheckCircle } from 'lucide-react';
import { useState } from 'react';

export function EnterpriseContent() {
    const [isEnterprise, setIsEnterprise] = useState(false);

    return (
        <section className="px-4 py-20 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
                <h2 className="mb-12 text-center text-4xl font-bold">Choose your path</h2>

                {/* Toggle Buttons */}
                <div className="mb-12 flex justify-center gap-4">
                    <button
                        onClick={() => setIsEnterprise(false)}
                        className={`rounded-full px-6 py-2 font-medium transition ${
                            !isEnterprise ? 'bg-primary text-primary-foreground' : 'border border-border text-foreground hover:bg-accent/20'
                        }`}
                    >
                        I am a Small/Medium Enterprise
                    </button>
                    <button
                        onClick={() => setIsEnterprise(true)}
                        className={`rounded-full px-6 py-2 font-medium transition ${
                            isEnterprise ? 'bg-primary text-primary-foreground' : 'border border-border text-foreground hover:bg-accent/20'
                        }`}
                    >
                        I am a Large Enterprise
                    </button>
                </div>

                {/* Enterprise Content */}
                {isEnterprise ? (
                    <div className="mx-auto max-w-3xl">
                        <div className="rounded-xl border border-border bg-card p-8 md:p-12">
                            <h3 className="mb-8 text-3xl font-bold">For Large Scale Enterprises</h3>

                            <p className="mb-8 leading-relaxed text-muted-foreground">
                                Our experts have collectively saved 1bn+ in manufacturing costs for their clients and Costrym was trained on their
                                thinking process.
                            </p>

                            <div className="space-y-4">
                                <div className="flex gap-4">
                                    <CheckCircle className="h-6 w-6 flex-shrink-0 text-primary" />
                                    <p className="text-foreground">
                                        Expose spend leaks your finance team doesn't have time to chase or the ability to understand
                                    </p>
                                </div>
                                <div className="flex gap-4">
                                    <CheckCircle className="h-6 w-6 flex-shrink-0 text-primary" />
                                    <p className="text-foreground">Kill tiny costs that could save you millions</p>
                                </div>
                                <div className="flex gap-4">
                                    <CheckCircle className="h-6 w-6 flex-shrink-0 text-primary" />
                                    <p className="text-foreground">Gain a 24/7 surgical layer over every department's costs</p>
                                </div>
                                <div className="flex gap-4">
                                    <CheckCircle className="h-6 w-6 flex-shrink-0 text-primary" />
                                    <p className="text-foreground">Deliver measurable savings at scale — automatically</p>
                                </div>
                            </div>

                            <button className="mt-4 inline-flex items-center gap-2 rounded-full bg-primary-foreground px-8 py-3 font-bold text-primary transition hover:bg-primary-foreground/90">
                                <a href="https://app.costrym.com/register">Continue</a> <ArrowRight className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                ) : (
                    <div className="mx-auto max-w-3xl">
                        <div className="rounded-xl border border-border bg-card p-8 md:p-12">
                            <h3 className="mb-8 text-3xl font-bold">For SMEs</h3>

                            <div className="space-y-4">
                                <div className="flex gap-4">
                                    <CheckCircle className="h-6 w-6 flex-shrink-0 text-primary" />
                                    <p className="text-foreground">Get Fortune-500 cost discipline without hiring analysts</p>
                                </div>
                                <div className="flex gap-4">
                                    <CheckCircle className="h-6 w-6 flex-shrink-0 text-primary" />
                                    <p className="text-foreground">Automatically detect waste you never knew existed</p>
                                </div>
                                <div className="flex gap-4">
                                    <CheckCircle className="h-6 w-6 flex-shrink-0 text-primary" />
                                    <p className="text-foreground">Extend runway instantly — no extra headcount</p>
                                </div>
                                <div className="flex gap-4">
                                    <CheckCircle className="h-6 w-6 flex-shrink-0 text-primary" />
                                    <p className="text-foreground">Only pay when Costrym saves you money</p>
                                </div>
                            </div>

                            <button className="mt-4 inline-flex items-center gap-2 rounded-full bg-primary-foreground px-8 py-3 font-bold text-primary transition hover:bg-primary-foreground/90">
                                <a href="https://app.costrym.com/register">Continue</a> <ArrowRight className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}
