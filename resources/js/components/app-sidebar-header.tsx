import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { ModeToggle } from './mode-toggle';
import { NotificationsDropdown } from './notification-dropdown';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    return (
        <header className="z-10 flex h-16 shrink-0 items-center gap-2 border-sidebar-border/50 px-4 pt-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12">
            <div className="flex w-full items-center justify-between gap-2">
                <div className="flex items-center gap-2 rounded-2xl bg-accent/30 px-2 py-1">
                    <SidebarTrigger className="-ml-1" />
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                    {/* <div className="hidden xl:block">
                        <HeaderStatsMini className="ml-2" />
                    </div> */}
                </div>
                <div className="flex items-center gap-2 px-2 py-1">
                    {/* <CommandMenu /> */}
                    {/* <div className="block xl:hidden">
                        <HeaderStatsMini />
                    </div> */}
                    <NotificationsDropdown />
                    <ModeToggle className="" />
                </div>
            </div>
        </header>
    );
}
