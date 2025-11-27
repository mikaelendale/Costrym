import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';
import { Toaster } from 'sonner';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    return (
        <AppShell variant="sidebar">
            {/* Hide the full left sidebar on small screens to improve mobile layout */}
            <div className="hidden lg:block">
                <AppSidebar />
            </div>
            <AppContent variant="sidebar" className="overflow-x-hidden rounded-md bg-background">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
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
        </AppShell>
    );
}
