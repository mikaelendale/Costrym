import CostrymLogo from '@/assets/CostrymLogo.png';
import { MenuToggle } from '@/components/menu-toggle';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetFooter } from '@/components/ui/sheet';
import React from 'react';

export function SimpleHeader() {
    const [open, setOpen] = React.useState(false);

    // const links = [
    //     {
    //         label: 'Features',
    //         href: '#',
    //     },
    //     {
    //         label: 'Pricing',
    //         href: '#',
    //     },
    //     {
    //         label: 'About',
    //         href: '#',
    //     },
    // ];

    return (
        <header className="fixed top-2 z-50 w-full">
            <nav className="mx-auto flex h-14 w-full max-w-4xl items-center justify-between rounded-lg border-b border-border bg-background/95 px-4 py-4 backdrop-blur-lg supports-[backdrop-filter]:bg-background/80">
                <div className="flex items-center gap-2">
                    <img src={CostrymLogo} alt="Costrym Logo" className="h-8 w-8" />
                    <p className="font-spirax text-4xl">Costrym</p>
                </div>
                <div className="hidden items-center gap-2 lg:flex">
                    {/* {links.map((link) => (
                        <a className={buttonVariants({ variant: 'ghost' })} href={link.href}>
                            {link.label}
                        </a>
                    ))} */}
                    <Button variant="outline" asChild>
                        <a href="https://app.costrym.com/login">Sign In</a>
                    </Button>
                    <Button asChild>
                        <a href="https://app.costrym.com/register">Get Started</a>
                    </Button>
                </div>
                <Sheet open={open} onOpenChange={setOpen}>
                    <Button size="icon" variant="outline" className="lg:hidden">
                        <MenuToggle strokeWidth={2.5} open={open} onOpenChange={setOpen} className="size-6" />
                    </Button>
                    <SheetContent
                        className="gap-0 bg-background/95 backdrop-blur-lg supports-[backdrop-filter]:bg-background/80"
                        showClose={false}
                        side="left"
                    >
                        {/* <div className="grid gap-y-2 overflow-y-auto px-4 pt-12 pb-5">
                            {links.map((link) => (
                                <a
                                    className={buttonVariants({
                                        variant: 'ghost',
                                        className: 'justify-start',
                                    })}
                                    href={link.href}
                                >
                                    {link.label}
                                </a>
                            ))}
                        </div> */}
                        <SheetFooter>
                            <Button variant="outline" asChild>
                                <a href="https://app.costrym.com/login">Sign In</a>
                            </Button>
                            <Button asChild>
                                <a href="https://app.costrym.com/register">Get Started</a>
                            </Button>
                        </SheetFooter>
                    </SheetContent>
                </Sheet>
            </nav>
        </header>
    );
}
