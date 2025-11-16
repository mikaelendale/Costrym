import AppLogo from '@/components/app-logo';
import { ModeToggle } from '@/components/mode-toggle';
import { Button } from '@/components/ui/button';
import { Link, router } from '@inertiajs/react';
import { LogOutIcon } from 'lucide-react';
import { type PropsWithChildren } from 'react';
import { Toaster } from 'sonner';

interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({ children, title, description }: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div className="w-full">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link href={route('dashboard')} className="flex flex-col items-center gap-2 font-medium">
                            <div className="mb-1 flex items-center justify-center rounded-md">
                                <AppLogo />
                            </div>
                            <span className="sr-only">Costrym</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="font-sans text-3xl font-medium text-muted-foreground">{title}</h1>
                            <p className="text-center text-sm text-muted-foreground">{description}</p>
                        </div>
                    </div>
                    {children}
                    <div className="fixed right-4 bottom-4 z-50 flex items-center space-x-2">
                        <ModeToggle className="rounded-full bg-background ring-4 ring-primary-foreground/30" />
                        <Button
                            variant="ghost"
                            size="icon"
                            className="rounded-full bg-background ring-4 ring-primary-foreground/30 hover:bg-accent hover:text-accent-foreground"
                            onClick={() => router.post(route('logout'))}
                        >
                            <LogOutIcon className="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
            <Toaster
                expand
                toastOptions={{
                    style: {
                        background: 'var(--primary-foreground)',
                        borderColor: 'var(--accent)',
                        color: 'var(--primary)',
                        borderRadius: '20px',
                    },
                }}
                theme="system"
            />
        </div>
    );
}
