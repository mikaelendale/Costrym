import AppLogoIcon from '@/components/app-logo-icon';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

export default function AuthCardLayout({
    children,
    title,
    description,
}: PropsWithChildren<{
    name?: string;
    title?: string;
    description?: string;
}>) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10  relative" >
            <div className="flex w-full max-w-sm flex-col gap-6 z-10">
                <div className="flex flex-col">
                        <div className="flex justify-center pt-10">
                            <Link href={route('home')} className="flex items-center gap-2 font-medium">
                                <div className="flex h-10 w-10 items-center bg-white rounded-full  justify-center">
                                    <AppLogoIcon className="size-9  fill-current text-black dark:text-white rounded-full" />
                                </div>
                            </Link>
                        </div>
                        <div className="px-10 py-4 text-center">
                            <h1 className="text-2xl">{title}</h1>
                            <p className="text-sm text-primary/90">{description}</p>
                        </div>
                    <Card className="rounded-4xl ring-4 ring-primary-foreground/30 dark:ring-accent/30 ">
                        <CardContent className="bg-transparent border-none pb-10">{children}</CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
