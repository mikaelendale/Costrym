import { Link, router } from "@inertiajs/react";
import AppLogo from "./app-logo";
import { ModeToggle } from "./mode-toggle";
import { Button } from "./ui/button";


export default function FooterSection() {
    return (
        <footer className="mt-16 sm:mt-20">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 pt-12 pb-8 sm:py-16">
                <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8 lg:gap-12">
                    {/* Left - WiFi Icon */}
                    <Link href="/">
                        <div className="flex items-center space-x-2 px-4  rounded-3xl" >
                            <AppLogo />
                        </div>
                    </Link>

                </div>

                {/* Bottom Section */}
                <div className="border-t border-accent mt-5 pt-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 text-sm text-muted-foreground">
                        <span>©{new Date().getFullYear()} - All Rights Reserved.</span>
                        <div className="flex items-center gap-6">
                            <Link href="/privacy" className="hover:text-primary transition-colors">
                                Privacy Policy
                            </Link>
                            <Link href="/terms" className="hover:text-primary transition-colors">
                                Terms of Service
                            </Link>
                            <Link href="/changelog" className="hover:text-primary transition-colors">
                                Changelog
                            </Link>
                        </div>
                    </div>
                    <div className="items-center gap-2 hidden sm:flex text-sm text-muted-foreground">
                        <ModeToggle />
                    </div>
                </div>
            </div>
        </footer>
    )
}
