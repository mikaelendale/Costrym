import { Breadcrumbs } from "@/components/breadcrumbs"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from "@/components/ui/navigation-menu"
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from "@/components/ui/sheet"
import { UserMenuContent } from "@/components/user-menu-content"
import { useInitials } from "@/hooks/use-initials"
import { cn } from "@/lib/utils"
import type { BreadcrumbItem, NavItem, SharedData } from "@/types"
import { Link, usePage } from "@inertiajs/react"
import { BrickWall, GitPullRequestDraft, LayoutGrid, Menu, Settings, Shield, ShieldClose, UserCheck } from "lucide-react"
import AppLogo from "./app-logo"
import AppLogoIcon from "./app-logo-icon"
import { CommandMenu } from "./command"
import { NotificationsDropdown } from "./notification-dropdown"
import { ModeToggle } from "./mode-toggle"

const mainNavItems: NavItem[] = [
    {
        title: "Dashboard",
        href: "/dashboard",
        icon: LayoutGrid,
        roles: ["user", "admin"],
    },
    {
        title: "Admin Panel",
        href: "/admin/dashboard",
        icon: BrickWall,
        roles: ["admin"], // Define required roles for this item
    },
    {
        title: "Users",
        href: "/admin/users",
        icon: UserCheck,
        roles: ["admin"], // Define required roles for this item
    },
    {
        title: "Roles and Permissions",
        href: "/admin/roles-permissions",
        icon: GitPullRequestDraft,
        roles: ["admin"], // Define required roles for this item
    },
]

interface AppHeaderProps {
    breadcrumbs?: BreadcrumbItem[]
}

export function AppHeader({ breadcrumbs = [] }: AppHeaderProps) {
    const page = usePage<SharedData>()
    const { auth } = page.props
    const getInitials = useInitials()

    const visibleNavItems = mainNavItems.filter((item) => {
        if (!item.roles) return true // Show items without role restrictions
        return item.roles.some((role) => auth.roles?.includes(role))
    })

    return (
        <>
            <div className="border-b border-sidebar-border/80">
                <div className="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                    {/* Mobile Menu */}
                    <div className="lg:hidden">
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button variant="ghost" size="icon" className="mr-2 h-[34px] w-[34px]">
                                    <Menu className="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="flex h-full w-64 flex-col items-stretch justify-between bg-sidebar">
                                <SheetTitle className="sr-only">Navigation Menu</SheetTitle>
                                <SheetHeader className="flex justify-start text-left">
                                    <AppLogoIcon className="h-6 w-6 fill-current text-black dark:text-white" />
                                </SheetHeader>
                                <div className="flex h-full flex-1 flex-col space-y-4 p-4">
                                    <div className="flex h-full flex-col justify-between text-sm">
                                        <div className="flex flex-col space-y-4">
                                            {visibleNavItems.map((item) => (
                                                <Link key={item.title} href={item.href} className="flex items-center space-x-2 font-medium">
                                                    {item.icon && <item.icon className="h-5 w-5" />}
                                                    <span>{item.title}</span>
                                                </Link>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>

                    <Link href="/dashboard" prefetch className="flex items-center space-x-2">
                        <AppLogo />
                    </Link>

                    {/* Desktop Navigation */}
                    <div className="ml-6 hidden h-full items-center space-x-6 lg:flex">
                        <NavigationMenu className="flex h-full items-stretch">
                            <NavigationMenuList className="flex h-full items-stretch space-x-2">
                                {visibleNavItems.map((item, index) => (
                                    <NavigationMenuItem key={index} className="relative flex h-full items-center">
                                        <Link
                                            href={item.href}
                                            className={cn(
                                                navigationMenuTriggerStyle(),
                                                page.url === item.href && "text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100",
                                                "h-9 cursor-pointer px-3",
                                            )}
                                        >
                                            {item.icon && <item.icon className="mr-2 h-4 w-4" />}
                                            {item.title}
                                        </Link>
                                        {page.url === item.href && (
                                            <div className="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"></div>
                                        )}
                                    </NavigationMenuItem>
                                ))}
                            </NavigationMenuList>
                        </NavigationMenu>
                    </div>

                    <div className="ml-auto flex items-center space-x-1">
                        <div className="relative flex items-center space-x-1">
                            <CommandMenu />
                            <NotificationsDropdown />
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="size-10 rounded-full p-1">
                                    <Avatar className="size-8 overflow-hidden rounded-full">
                                        <AvatarImage src={auth.user.profile_url || "/placeholder.svg"} alt={auth.user.name} />
                                        <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {getInitials(auth.user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>
            {breadcrumbs.length > 1 && (
                <div className="flex w-full border-b border-sidebar-border/70">
                    <div className="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            )}
            <ModeToggle className="fixed bottom-4 bg-background rounded-full ring-4 ring-accent/80 right-4 z-20" />
        </>
    )
}
