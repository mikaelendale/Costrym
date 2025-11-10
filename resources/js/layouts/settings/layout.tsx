import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { SharedData, type NavItem } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { Key, Link2, SunMoonIcon, UserCog } from 'lucide-react';
import { type PropsWithChildren } from 'react';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: '/settings/profile',
        icon: UserCog,
    },
    {
        title: 'Password',
        href: '/settings/password',
        icon: Key,
    },
    {
        title: 'Appearance',
        href: '/settings/appearance',
        icon: SunMoonIcon,
    },
    {
        title: 'Connected As',
        href: '/settings/social',
        icon: Link2,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<SharedData>().props;
    const hadSocial = auth.user.provider_id !== null;
    const filteredSidebarNavItems = sidebarNavItems.filter((item) => {
        if (hadSocial) {
            return item.title !== 'Password';
        } else {
            return item.title !== 'Connected As';
        }
    });
    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    const currentPath = window.location.pathname;

    return (
        <div className="px-4 py-6">
            <Heading title="Settings" description="Manage your profile and account settings" />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {filteredSidebarNavItems.map((item) => {
                            const isActive = currentPath === item.href;
                            return (
                                <Button
                                    key={`${item.href}`}
                                    onClick={() => router.visit(item.href)}
                                    size="sm"
                                    variant="ghost"
                                    asChild
                                    className={cn('w-full text-muted-foreground justify-start', {
                                        'bg-muted': currentPath === item.href,
                                        'text-primary': isActive,
                                    })}
                                >
                                    <Link href={item.href} prefetch>
                                        {item.icon && <item.icon className="h-4 w-4 mr-2" />}
                                        {item.title}
                                    </Link>
                                </Button>
                            );
                        })}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">{children}</section>
                </div>
            </div>
        </div>
    );
}
