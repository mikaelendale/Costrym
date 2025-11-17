import FooterSection from '@/components/footer-one';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast, Toaster } from 'sonner';

export default function LandingLayout({ children, title, description, ...props }: { children: React.ReactNode; title: string; description: string }) {
    const { flash } = usePage<SharedData>().props as any;

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);
    return (
        <>
            {children}
            <Toaster
                expand
                toastOptions={{
                    style: {
                        background: 'var(--primary-foreground)',
                        borderColor: 'var(--accent)',
                        color: 'var(--primary)',
                        borderRadius: '20px', // Modern, moderately rounded corners
                    },
                }}
                theme="system"
            />
            <FooterSection />
        </>
    );
}
