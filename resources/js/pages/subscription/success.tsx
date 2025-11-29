
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle2 } from 'lucide-react';

interface SubscriptionSuccessProps {
    subscribed: boolean;
}

export default function SubscriptionSuccess({ subscribed }: SubscriptionSuccessProps) {
    return (
        <div className="flex min-h-screen items-center justify-center p-4">
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                        <CheckCircle2 className="h-10 w-10 text-green-600" />
                    </div>
                    <CardTitle>Subscription Successful!</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-center text-sm text-muted-foreground">
                        {subscribed
                            ? 'Your subscription is now active.'
                            : 'Processing... You will receive confirmation shortly.'}
                    </p>
                    <div className="flex flex-col gap-2">
                        <Button asChild className="w-full">
                            <Link href={'/dashboard'}>Go to Dashboard</Link>
                        </Button>
                        <Button asChild variant="outline" className="w-full">
                            <Link href={'/billing'}>Manage Subscription</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}