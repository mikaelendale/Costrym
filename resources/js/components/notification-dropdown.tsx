"use client"

import { useState } from "react"
import { Bell } from "lucide-react"
import { Button } from "@/components/ui/button"
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Badge } from "@/components/ui/badge"
import { cn } from "@/lib/utils"
import { SharedData } from "@/types"
import { usePage, router } from "@inertiajs/react"

interface Notification {
    id: string
    type: string
    data: {
        icon: string
        message: string
        description?: string
        action?: string
        url?: string
    }
    read_at: string | null
    created_at: string
    time_ago: string
}

export function NotificationsDropdown() {
    const { auth } = usePage<SharedData>().props
    const [activeTab, setActiveTab] = useState<"all" | "unread">("all")

    // Use actual notifications from Laravel
    const notifications: Notification[] = auth.notifications || []
    const unreadCount = notifications.filter((n) => !n.read_at).length

    const filteredNotifications = activeTab === "unread"
        ? notifications.filter((n) => !n.read_at)
        : notifications

    const displayedNotifications = filteredNotifications.slice(0, 3)

    const handleMarkAsRead = (notificationId: string) => {
        router.post(`/notifications/${notificationId}/read`)
    }

    const handleNotificationClick = (notification: Notification) => {
        if (!notification.read_at) {
            handleMarkAsRead(notification.id)
        }
        if (notification.data.url) {
            window.location.href = notification.data.url
        }
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant={'ghost'} className="size-10 border rounded-full p-1 relative">
                    <Bell className="h-3 w-3" />
                    {unreadCount > 0 && (
                        <Badge
                            className="absolute -top-1 bg-accent border-primary text-primary font-bold -right-1 h-5 w-5 rounded-full p-0 flex items-center justify-center text-xs"
                        >
                            {unreadCount}
                        </Badge>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-80 p-0" align="end">
                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b">
                    <h3 className="font-semibold text-base">Notifications</h3>
                    <div className="flex gap-4">
                        <button
                            onClick={() => setActiveTab("all")}
                            className={cn(
                                "text-sm font-medium transition-colors",
                                activeTab === "all" ? "text-foreground" : "text-muted-foreground hover:text-foreground",
                            )}
                        >
                            All
                        </button>
                        <button
                            onClick={() => setActiveTab("unread")}
                            className={cn(
                                "text-sm font-medium transition-colors",
                                activeTab === "unread" ? "text-foreground" : "text-muted-foreground hover:text-foreground",
                            )}
                        >
                            Unread
                        </button>
                    </div>
                </div>

                {/* Notifications List */}
                <div>
                    {displayedNotifications.length === 0 ? (
                        <div className="p-4 text-center text-muted-foreground text-sm">
                            {activeTab === "unread" ? "No unread notifications" : "No notifications"}
                        </div>
                    ) : (
                        displayedNotifications.map((notification) => (
                            <div
                                key={notification.id}
                                className={cn(
                                    "flex items-start gap-3 p-4 hover:bg-muted/50 transition-colors relative",
                                    !notification.read_at && "bg-muted/30",
                                )}
                                onClick={() => handleNotificationClick(notification)}
                            >
                                {/* Avatar with status indicator */}
                                <div className="relative">
                                    <Avatar className="h-10 w-10 ">
                                        <AvatarFallback className="">
                                            {notification.data.icon || '🔔'}
                                        </AvatarFallback>
                                    </Avatar>
                                </div>

                                {/* Content */}
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 mb-1">
                                        <span className="font-medium text-sm">{notification.data.message}</span>
                                        <span className="text-xs text-muted-foreground">{notification.time_ago}</span>
                                        {!notification.read_at && <div className="h-2 w-2 bg-green-500 rounded-full ml-auto" />}
                                    </div>

                                    {notification.data.description && (
                                        <p className="text-xs text-muted-foreground line-clamp-2">
                                            {notification.data.description}
                                        </p>
                                    )}

                                    {/* Action buttons for invitations */}
                                    {notification.data.action && (
                                        <div className="flex gap-2 mt-3">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="h-8 px-3 text-xs bg-transparent"
                                                onClick={() => router.visit(notification.data.url)}
                                            >
                                                {notification.data.action}
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))
                    )}
                </div>

                {notifications.length > 0 && (
                    <div className="border-t">
                        <Button
                            variant="ghost"
                            className="w-full rounded-none text-sm font-medium text-muted-foreground hover:text-foreground"
                            onClick={() => window.location.href = '/notifications'}
                        >
                            View all notifications
                        </Button>
                    </div>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    )
}