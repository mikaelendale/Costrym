import { ModeToggle } from '@/components/mode-toggle';
import AuthLayoutTemplate from '@/layouts/auth/auth-card-layout';
import { SharedData, type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, type PropsWithChildren } from 'react';
import { toast, Toaster } from 'sonner';

export default function AuthLayout({ children, title, description, ...props }: { children: React.ReactNode; title: string; description: string }) {
    const { flash } = usePage<SharedData>().props as any;

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);
    return (
        <AuthLayoutTemplate title={title} description={description} {...props}>
            {children}
            <ModeToggle className='absolute bottom-4 bg-background rounded-full ring-4 ring-primary-foreground/30 right-4' />

            <Toaster expand
                toastOptions={{
                    style: {
                        background: 'var(--primary-foreground)',
                        borderColor: 'var(--accent)',
                        color: 'var(--primary)',
                        borderRadius: '20px', // Modern, moderately rounded corners
                    },
                }
                }
                theme="system"
            />
        </AuthLayoutTemplate>
    );
}
