'use client';

import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { AlertTriangle, CreditCard, Pin } from 'lucide-react';
import { useState } from 'react';

interface SubscriptionState {
    valid: boolean;
    active: boolean;
    onTrial: boolean;
    expiredTrial: boolean;
    notOnTrial: boolean;
    recurring: boolean;
    pastDue: boolean;
    paused: boolean;
    notPaused: boolean;
    onPausedGracePeriod: boolean;
    notOnPausedGracePeriod: boolean;
    canceled: boolean;
    notCanceled: boolean;
    onGracePeriod: boolean;
    notOnGracePeriod: boolean;
    subscribed: boolean;
    subscribedToDefault: boolean;
    onGenericTrial: boolean;
    hasExpiredTrial: boolean;
}

interface SubscriptionData {
    id: string;
    type: string;
    paddle_id: string;
    status: string;
    trial_ends_at?: string;
    ends_at?: string;
    paused_at?: string;
    created_at: string;
    updated_at: string;
    states: Omit<SubscriptionState, 'subscribed' | 'subscribedToDefault' | 'onGenericTrial' | 'hasExpiredTrial'>;
}

interface PageProps {
    customer: {
        plan: string;
        subscriptionAmount: string;
    };
    price: {
        startup_monthly: string;
        startup_annual: string;
        enterprise_annual: string;
    };
    subscription: {
        hasSubscription: boolean;
        defaultSubscription?: SubscriptionData;
        states: SubscriptionState;
        subscriptions: SubscriptionData[];
        trialEndsAt?: string;
    };
}

export default function Billing() {
    const { customer, price, subscription } = usePage<SharedData & PageProps>().props;

    const [modal, setModal] = useState<null | { plan: string; billing: string; name: string; price: string }>(null);
    const openModal = (plan: string, billing: string, name: string, price: string) => {
        setModal({ plan, billing, name, price });
    };
    const closeModal = () => setModal(null);
    const handleConfirm = () => {
        if (modal) {
            router.post('/subscription/swap', { plan: modal.plan, billing: modal.billing });
            closeModal();
        }
    };

    const plans = [
        {
            id: 'startup-monthly',
            name: 'Startup monthly',
            price: price.startup_monthly,
            period: 'month',
            description: 'Perfect for small teams getting started',
            popular: false,
        },
        {
            id: 'startup-annual',
            name: 'Startup annual',
            price: price.startup_annual,
            period: 'year',
            description: 'Perfect for small teams getting started',
            popular: false,
        },
        {
            id: 'enterprise-annual',
            name: 'Enterprise annual',
            price: price.enterprise_annual,
            period: 'year',
            description: 'Perfect for large teams and enterprises',
            popular: false,
        },
    ];
    const handleCancelSubscription = () => {
        router.post(route('subscription.cancel'));
    };

    const formatDate = (dateString?: string) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };
    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-3xl font-bold">Billing & Subscription</h1>
                <p className="mt-1 text-sm text-muted-foreground">Manage your subscription, billing, and usage</p>
            </div>

            {subscription.states.onGracePeriod && (
                <Card className="border-yellow-400 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-950">
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base text-yellow-700 dark:text-yellow-300">
                            <AlertTriangle className="h-5 w-5" />
                            You are on a grace period
                        </CardTitle>
                        <CardDescription className="dark:text-yellow-200">
                            Your subscription is currently in a grace period. Please update your payment method or renew your subscription to avoid
                            losing access to premium features. It will end at <strong>{formatDate(subscription.defaultSubscription?.ends_at)}</strong>
                            .
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-0">
                        <Button onClick={() => router.post('/subscription/stop-cancellation')}>Resume Subscription</Button>
                    </CardContent>
                </Card>
            )}

            <div className="space-y-4">
                {/* Current Plan */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-base">Current Plan</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-2xl font-bold">{customer.plan}</p> 
                            </div>
                            <Badge variant={subscription.states.active ? 'default' : 'secondary'}>
                                {subscription.hasSubscription ? 'Active' : 'None'}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                {/* Payment Method */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <CreditCard className="h-4 w-4" />
                            Payment Method
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-14 items-center justify-center rounded bg-gradient-to-br from-blue-600 to-purple-600">
                                    <CreditCard className="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium">Payment Information</p>
                                    <p className="text-xs text-muted-foreground">Your payment method is up to date</p>
                                </div>
                            </div>
                            <a href="/subscription/payment-method">
                                <Button variant="outline" size="sm">
                                    Update
                                </Button>
                            </a>
                        </div>
                    </CardContent>
                </Card>

                {/* Cancel Subscription */}
                {!subscription.states.onGracePeriod && (
                    <Card className="border-destructive/30">
                        <CardHeader className="pb-4">
                            <CardTitle className="flex items-center gap-2 text-base text-destructive">
                                <AlertTriangle className="h-4 w-4" />
                                Danger Zone
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-sm text-muted-foreground">
                                Once you cancel your subscription, you'll lose access to all premium features.
                            </p>
                            <div className="flex justify-end">
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button variant="destructive" size="sm">
                                            Cancel Subscription
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Are you sure you want to cancel?</DialogTitle>
                                            <DialogDescription>
                                                This action cannot be undone. You'll lose access to all premium features at the end of your current
                                                billing period
                                                {subscription.defaultSubscription?.ends_at
                                                    ? ` (${new Date(subscription.defaultSubscription.ends_at).toLocaleDateString()})`
                                                    : ''}
                                                . Your data will be preserved for 30 days in case you want to reactivate.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button variant="outline">Keep Subscription</Button>
                                            </DialogClose>
                                            <Button variant="destructive" onClick={handleCancelSubscription}>
                                                Yes, Cancel Subscription
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            <Card>
                <CardHeader className="pb-4">
                    <CardTitle className="text-base">Available Plans</CardTitle>
                    <CardDescription className="text-sm">Upgrade or downgrade your plan at any time</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-3">
                        {plans.map((plan) => (
                            <div
                                key={plan.id}
                                className={`pointer-events-none relative rounded-lg border p-4   ${
                                    customer.plan === plan.id ? 'bg-primary-foreground ring-2 ring-primary' : 'opacity-60 grayscale'
                                }`}
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2">
                                            <h3 className="font-semibold">{plan.name}</h3>
                                            {customer.plan === plan.id && (
                                                    <Pin className="mr-1 h-3 w-3" />
                                            )}
                                        </div>
                                        <p className="mt-1 text-xs text-muted-foreground">{plan.description}</p>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-xl font-bold">{plan.price}</div>
                                        <div className="text-xs text-muted-foreground">/{plan.period}</div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-center">
                <Dialog>
                    <DialogTrigger asChild>
                        <Button variant="outline">Advanced Plan Options</Button>
                    </DialogTrigger>
                    <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Switch Plan (Advanced)</DialogTitle>
                            <DialogDescription>Choose how you want your plan change to be billed.</DialogDescription>
                        </DialogHeader>
                        <div className="space-y-3">
                            {plans.map((plan) => (
                                <div key={plan.id} className="rounded-lg border p-4">
                                    <div className="mb-3">
                                        <h5 className="font-semibold">{plan.name} Plan</h5>
                                        <p className="text-sm text-muted-foreground">
                                            {plan.price}/{plan.period}
                                        </p>
                                    </div>
                                    <div className="flex flex-col gap-2 sm:flex-row">
                                        <Button
                                            className="flex-1"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => openModal(plan.id, 'next_cycle', plan.name, plan.price)}
                                            disabled={customer.plan === plan.id}
                                        >
                                            Switch (Next Cycle)
                                        </Button>
                                        <Button
                                            className="flex-1"
                                            variant="default"
                                            size="sm"
                                            onClick={() => openModal(plan.id, 'immediate', plan.name, plan.price)}
                                            disabled={customer.plan === plan.id}
                                        >
                                            Switch Now (Immediate)
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                        <Accordion type="single" collapsible>
                            <AccordionItem value="advanced-billing" className="border-none">
                                <AccordionTrigger className="text-sm hover:no-underline">More Billing Options</AccordionTrigger>
                                <AccordionContent>
                                    <div className="flex flex-wrap gap-2">
                                        {plans.map((plan) => (
                                            <Button
                                                key={plan.id}
                                                variant="outline"
                                                size="sm"
                                                onClick={() => openModal(plan.id, 'no_prorate', plan.name, plan.price)}
                                                disabled={customer.plan === plan.id}
                                            >
                                                {plan.name} (No Prorate)
                                            </Button>
                                        ))}
                                    </div>
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </DialogContent>
                </Dialog>
            </div>

            {/* Modal for confirmation */}
            <Dialog open={!!modal} onOpenChange={closeModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Confirm Plan Change</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        <p>
                            Are you sure you want to switch to the <strong>{modal?.name}</strong> plan ({modal?.price})?
                        </p>
                        {modal?.billing === 'immediate' && (
                            <div className="rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-yellow-950 dark:text-yellow-200">
                                You will be charged immediately with proration.
                            </div>
                        )}
                        {modal?.billing === 'no_prorate' && (
                            <div className="rounded-lg bg-blue-50 p-3 text-sm text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                                This will switch your plan in the next billing cycle.
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={closeModal}>
                            Cancel
                        </Button>
                        <Button onClick={handleConfirm}>Confirm</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
