import AppLogo from '@/components/app-logo';
import AppLogoIcon from '@/components/app-logo-icon';
import { ModeToggle } from '@/components/mode-toggle';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({ children, title, description }: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link href={route('dashboard')} className="flex flex-col items-center gap-2 font-medium">
                            <div className="mb-1 flex items-center justify-center rounded-md">
                                <AppLogo />
                            </div>
                            <span className="sr-only ">Costrym</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className=" font-medium text-muted-foreground text-3xl font-spirax">{title}</h1>
                            <p className="text-center text-sm text-muted-foreground">{description}</p>
                        </div>
                    </div>
                    {children}
                    <ModeToggle className='absolute bottom-4 bg-background rounded-full ring-4 ring-primary-foreground/30 right-4' />
                </div>
            </div>
        </div>
    );
}
