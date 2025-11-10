import { AppContent } from '@/components/app-content';
import { AppFooter } from '@/components/app-footer';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { SharedData, type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, type PropsWithChildren } from 'react';
import { toast, Toaster } from 'sonner';

export default function AppHeaderLayout({ children, breadcrumbs }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { flash } = usePage<SharedData>().props as any;

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);
    return (
        <AppShell>
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent>{children}</AppContent>
            <AppFooter />
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
        </AppShell>
    );
}
