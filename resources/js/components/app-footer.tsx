import { Link } from "@inertiajs/react";

export function AppFooter() {
    return (
        <footer className="py-6 w-full bg-background">
            <div className="container mx-auto flex flex-col items-center px-4">
                <div className="flex flex-col md:flex-row items-center justify-center md:justify-between w-full max-w-7xl gap-4">
                    <div className="text-sm text-muted-foreground text-center md:text-left">
                        © 2025 Razory. All rights reserved.
                    </div>
                    <div className="flex flex-row space-x-6 justify-center">
                        <Link href="/privacy" className="text-sm text-muted-foreground hover:underline">
                            Privacy Policy
                        </Link>
                        <Link href="/terms" className="text-sm text-muted-foreground hover:underline">
                            Terms of Service
                        </Link>
                    </div>
                </div>
            </div>
        </footer>
    );
}
