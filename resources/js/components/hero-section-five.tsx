import { Button } from '@/components/ui/button'
import { BookOpen } from 'lucide-react'
import { cn } from '@/lib/utils'
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from './app-logo-icon';

export default function HeroSection() {

    const { auth } = usePage<SharedData>().props;
    return (
        <section className="py-20">
            <div className="relative z-10 mx-auto w-full max-w-2xl px-6 lg:px-0">
                <div className="relative text-center">
                    <MistKitLogo className="mx-auto" />
                    <h1 className="mx-auto mt-16 max-w-xl text-balance text-5xl font-medium">
                        Razory
                    </h1>

                    <p className="text-muted-foreground mx-auto mb-6 mt-4 text-balance text-xl">
                        Stay razor-sharp. Razory helps you track tab usage, cut distractions, and
                        stay focused on what really matters.
                    </p>

                    <div className="flex flex-col items-center gap-2 *:w-full sm:flex-row sm:justify-center sm:*:w-auto">
                        {auth.user ? (
                            <Button asChild variant="default">
                                <Link href={route('dashboard')}>
                                    <span className="text-nowrap">Dashboard</span>
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild variant="default">
                                    <Link href={route('login')}>
                                        <span className="text-nowrap">Get Started</span>
                                    </Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href={route('register')}>
                                        <span className="text-nowrap">View Demo</span>
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <div className="relative mt-10 overflow-hidden rounded-3xl">
                    <div className="bg-background rounded-3xl relative overflow-hidden border border-transparent shadow-xl shadow-black/15 ring-4 ring-accent/10 sm:m-8 md:m-12">
                        <img
                            src="https://cdn.dribbble.com/userupload/43307864/file/original-e6754e319cde71d1495dde8e4f1a7555.png?resize=1024x768&vertical=center"
                            alt="app screen"
                            width="2880"
                            height="1842"
                            className="object-top-left size-full object-cover"
                        />
                    </div>
                </div>
            </div>
        </section>
    )
}

const MistKitLogo = ({ className }: { className?: string }) => (
    <div
        aria-hidden
        className={cn('border-background rounded-xl bg-white relative flex size-12 translate-y-0.5 items-center justify-center border  ring-6 ring-accent/40 ', className)}>
        <AppLogoIcon className='size-9'/>
    </div>
)
