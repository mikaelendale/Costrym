import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { SharedData, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChatTeardropTextIcon, CreditCard, GiftIcon, HouseIcon, NetworkIcon, TrendUpIcon } from '@phosphor-icons/react';
import { BrickWall, GitPullRequestDraft, UserCheck } from 'lucide-react';
import AppLogo from './app-logo';
const NavFooterBudNetCardItems: NavItem[] = [
    {
        title: 'BudNet',
        href: '/budnet',
        icon: NetworkIcon,
    },
];

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: HouseIcon,
        // roles: ['user', 'admin'],
    },
    {
        title: 'Strategy',
        href: '/strategy-overview',
        icon: TrendUpIcon,
        roles: ['user', 'admin'],
    },
    {
        title: 'Admin Panel',
        href: '/admin/dashboard',
        icon: BrickWall,
        roles: ['admin'], // Define required roles for this item
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: UserCheck,
        roles: ['admin'], // Define required roles for this item
    },
    {
        title: 'Roles and Permissions',
        href: '/admin/roles-permissions',
        icon: GitPullRequestDraft,
        roles: ['admin'], // Define required roles for this item
    },
];

const secondNavItems: NavItem[] = [
    {
        title: 'Buddy',
        href: '/chat',
        icon: ChatTeardropTextIcon,
        roles: ['user', 'admin'],
    },
];
const automationNavItems: NavItem[] = [
    // {
    //     title: 'Agents',
    //     href: '/agents',
    //     icon: RobotIcon,
    //     roles: ['user', 'admin'],
    // },
    {
        title: 'Automations',
        href: '/automation',
        icon: NetworkIcon,
        roles: ['user', 'admin'],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Billing',
        href: '/billing',
        icon: CreditCard,
    },
    {
        title: 'Referral',
        href: '/referral',
        icon: GiftIcon,
    },
    // {
    //     title: 'Customer Support',
    //     href: '/customer-support',
    //     icon: Headset,
    // },
];

export function AppSidebar() {
    const page = usePage<SharedData>();
    const { auth } = page.props;

    const visibleNavItems = mainNavItems.filter((item) => {
        if (!item.roles) return true; // Show items without role restrictions
        return item.roles.some((role) => auth.roles?.includes(role));
    });
    const visibleSecondNavItems = secondNavItems.filter((item) => {
        if (!item.roles) return true; // Show items without role restrictions
        return item.roles.some((role) => auth.roles?.includes(role));
    });
    const visibleAutomationNavItems = automationNavItems.filter((item) => {
        if (!item.roles) return true; // Show items without role restrictions
        return item.roles.some((role) => auth.roles?.includes(role));
    });
    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
