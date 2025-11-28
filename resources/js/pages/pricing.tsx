import { Button } from '@/components/ui/button';
import AuthSimpleLayout from '@/layouts/auth/auth-simple-layout';
import LandingLayout from '@/layouts/landing-layout';
import { Head, Link } from '@inertiajs/react';
import { CheckIcon } from '@phosphor-icons/react';

export default function Pricing() {
    return (
        <>
            <Head title="Pricing Plans" />

            <LandingLayout
                title="Choose Your Plan"
                description="Select the perfect plan for your business needs"
            >
                <div className="mx-auto w-full max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                    {/* Header Section */}
                    <div className="text-center pt-10 space-y-4">
                        <h1 className="text-4xl font-normal tracking-tight text-foreground sm:text-5xl lg:text-6xl">
                            Simple, Transparent Pricing
                        </h1>
                        <p className="mx-auto max-w-2xl text-sm text-muted-foreground">
                            Choose the plan that fits your business. All plans include our cost-saving guarantee.
                        </p>
                    </div>

                    {/* Pricing Cards */}
                    <div className="grid gap-6 lg:grid-cols-3 lg:gap-8">
                        {/* Startup Monthly Plan */}
                        <div className="group relative flex flex-col rounded-xl border border-border bg-primary-foreground p-6 transition-all duration-300 hover:border-primary/50 hover:shadow-lg sm:p-8">
                            <div className="space-y-4 pt-4">
                                <div>
                                    <h3 className="text-2xl font-normal font-spirax sm:text-3xl">Startup</h3>
                                    <p className="mt-2 text-sm text-muted-foreground">Monthly plan</p>
                                </div>

                                <div className="space-y-1 py-4">
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-4xl font-bold text-primary sm:text-5xl">$79.99</span>
                                        <span className="text-base text-muted-foreground">/month</span>
                                    </div>
                                    <p className="text-sm text-muted-foreground">Billed monthly</p>
                                </div>

                                <ul className="space-y-2.5 text-xs sm:text-sm">
                                    <li className="flex items-start gap-2">
                                        <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span>Save costs from day one—guaranteed</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span>If we don't save you at least $100 in your first month, you aren't charged for that month</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span>Right for companies with $1,000–$50,000/month in expenses</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span>No monthly subscription—only pay for months we deliver real savings</span>
                                    </li>
                                </ul>

                                <div className="absolute inset-x-0 bottom-0 left-0 p-5">

                                    <Button
                                        className="mt-auto w-full"
                                        variant="outline"
                                        size="lg"
                                        asChild
                                    >
                                        <Link href={route('register')}>
                                            Get Started
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        {/* Startup Annual Plan */}
                        <div className="group relative flex flex-col rounded-xl border border-border bg-primary-foreground p-6 transition-all duration-300 hover:border-primary/50 hover:shadow-lg sm:p-8">
                            <div className="space-y-4 pt-4">
                                <div>
                                    <h3 className="text-2xl font-normal font-spirax sm:text-3xl">Startup</h3>
                                    <p className="mt-2 text-sm text-muted-foreground">Annual plan</p>
                                </div>

                                <div className="space-y-1 py-4">
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-4xl font-bold text-primary sm:text-5xl">$960</span>
                                        <span className="text-base text-muted-foreground">/year</span>
                                    </div>
                                    <p className="text-sm font-semibold text-primary">Best Value • Save $200/year</p>
                                </div>

                                <ul className="space-y-2.5 text-xs sm:text-sm">
                                    <li className="flex items-start gap-2">
                                        <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span>All features included in the Monthly plan</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span>Quarterly strategy sessions with a human cost expert (over $800M in savings delivered across industries)</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span>Priority support and dedicated account management</span>
                                    </li>
                                </ul>

                                <div className="absolute inset-x-0 bottom-0 left-0 p-5">

                                    <Button
                                        className="mt-auto w-full"
                                        variant="outline"
                                        size="lg"
                                        asChild
                                    >
                                        <Link href={route('register')}>
                                            Get Started
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        {/* Enterprise Annual Plan */}
                        <div className="group relative flex flex-col scale-105 rounded-xl border-2 border-primary bg-primary-foreground p-6 shadow-xl shadow-primary/20 transition-all duration-300 hover:shadow-2xl sm:p-8 lg:scale-105">
                            <div className="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-primary px-4 py-2 text-xs font-bold text-primary-foreground shadow-lg">
                                Most Popular
                            </div>
                            <div className="flex flex-col flex-1 space-y-6 pt-2">
                                <div>
                                    <h3 className="text-2xl font-normal font-spirax sm:text-3xl">Enterprise</h3>
                                    <p className="mt-2 text-sm text-muted-foreground">Annual plan</p>
                                </div>

                                <div className="space-y-1 py-4">
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-4xl font-bold text-primary sm:text-5xl">$7,000</span>
                                        <span className="text-base text-muted-foreground">/year</span>
                                    </div>
                                    <p className="text-sm font-semibold text-primary">Maximum savings potential</p>
                                </div>

                                <ul className="space-y-2.5 text-xs sm:text-sm">
                                    <li className="flex items-start gap-2">
                                        <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span>
                                            Begin saving thousands from day one — guaranteed.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span>
                                            Receive monthly cost audits led by seasoned experts.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span>
                                            Unlock expected savings of <span className="font-semibold">$1,000–$10,000</span> every month.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                        <span>
                                            Ideal for companies with $50,000+ in monthly expenses.
                                        </span>
                                    </li>
                                </ul>

                                <Button
                                    className="mt-auto w-full"
                                    variant="default"
                                    size="lg"
                                    asChild
                                >
                                    <Link href={route('register')}>
                                        Get Started
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
 
                </div>
            </LandingLayout>
        </>
    );
}
