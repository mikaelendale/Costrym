import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { XCircle } from 'lucide-react';

export default function SubscriptionCancel() {
    return (
        <div className="flex min-h-screen items-center justify-center p-4">
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100">
                        <XCircle className="h-10 w-10 text-yellow-600" />
                    </div>
                    <CardTitle>Subscription Cancelled</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-center text-sm text-muted-foreground">
                        No charges were made. You can try again anytime.
                    </p>
                    <div className="flex flex-col gap-2">
                        <Button asChild className="w-full">
                            <Link href={'/subscription-checkout'}>Try Again</Link>
                        </Button>
                        <Button asChild variant="outline" className="w-full">
                            <Link href={'/dashboard'}>Go to Dashboard</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}