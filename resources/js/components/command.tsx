"use client"

import * as React from "react"
import {
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/components/ui/command"
import { ScrollArea } from "./ui/scroll-area"
import { Button } from "./ui/button"
import { router } from "@inertiajs/react"
import { usePage } from "@inertiajs/react"

export function CommandMenu() {
  const { props } = usePage()
  const routes = (props.routes as Array<{ name: string; url: string }>) || []

  const [open, setOpen] = React.useState(false)
  const [query, setQuery] = React.useState("")
  const [selectedIndex, setSelectedIndex] = React.useState(0)

  const filteredRoutes = React.useMemo(() => {
    if (!query) return routes
    return routes.filter((route) => route.name.toLowerCase().includes(query.toLowerCase()))
  }, [query, routes])

  React.useEffect(() => {
    setSelectedIndex(0)
  }, [filteredRoutes])

  React.useEffect(() => {
    const down = (e: KeyboardEvent) => {
      if (e.key === "k" && (e.metaKey || e.ctrlKey)) {
        e.preventDefault()
        setOpen((open) => !open)
      }

      if (open && e.key === "Enter" && filteredRoutes.length > 0) {
        e.preventDefault()
        const selectedRoute = filteredRoutes[selectedIndex]
        router.visit(selectedRoute.url)
        setOpen(false)
        setQuery("")
      }

      if (open && e.key === "ArrowDown") {
        e.preventDefault()
        setSelectedIndex((prev) => (prev < filteredRoutes.length - 1 ? prev + 1 : 0))
      }

      if (open && e.key === "ArrowUp") {
        e.preventDefault()
        setSelectedIndex((prev) => (prev > 0 ? prev - 1 : filteredRoutes.length - 1))
      }
    }

    document.addEventListener("keydown", down)
    return () => document.removeEventListener("keydown", down)
  }, [open, filteredRoutes, selectedIndex])

  const handleRouteSelect = (route: { name: string; url: string }) => {
    router.visit(route.url)
    setOpen(false)
    setQuery("")
  }

  return (
    <>
      <div className="w-auto justify-center hidden sm:flex">
        <Button
          className="shadow-none select-none items-center w-auto rounded-xl font-medium "
          onFocus={() => setOpen(true)}
          aria-label="Open command menu"
          style={{ cursor: "pointer" }}
          variant={"link"}
        >
          <span className="text-muted-foreground">Search (⌘+K)</span>
        </Button>
      </div>
      <CommandDialog open={open} onOpenChange={setOpen}>
        <CommandInput placeholder="Search Routes..." value={query} onValueChange={setQuery} />
        <CommandList>
          <CommandEmpty>No results found.</CommandEmpty>
          <CommandGroup heading="Pages">
            <ScrollArea className="h-50 w-full rounded-md">
              {filteredRoutes.map((route, index) => (
                <CommandItem
                  key={route.name}
                  onSelect={() => handleRouteSelect(route)}
                  className={index === selectedIndex ? "bg-accent/40" : ""}
                >
                  <span>{route.name}</span>
                </CommandItem>
              ))}
            </ScrollArea>
          </CommandGroup>
        </CommandList>
      </CommandDialog>
    </>
  )
}
2