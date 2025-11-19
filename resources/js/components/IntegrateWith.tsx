import { logoList } from '@/data/IntegrateWith';
import { cn } from '@/lib/utils';
import { LogoCloud } from './logo-cloud-3';

export default function IntegrateWith() {
    return (
        <div className="mt-8 w-full place-content-center">
            <div
                aria-hidden="true"
                className={cn(
                    'pointer-events-none absolute -top-1/2 left-1/2 -z-10 h-[120vmin] w-[120vmin] -translate-x-1/2 rounded-b-full',
                    'bg-[radial-gradient(ellipse_at_center,--theme(--color-foreground/.1),transparent_50%)]',
                    'blur-[30px]',
                )}
            />

            <section className="relative mx-auto">
                <h2 className="mb-5 text-center text-xl font-medium tracking-tight text-foreground md:text-3xl">
                    <span className="text-muted-foreground">We integrate with</span>
                    <br />
                    <span className="font-semibold">all your tools</span>
                </h2>
                <div className="mx-auto my-5 h-px max-w-sm bg-border [mask-image:linear-gradient(to_right,transparent,black,transparent)]" />

                <LogoCloud logos={logoList} />

                <div className="mt-5 h-px bg-border [mask-image:linear-gradient(to_right,transparent,black,transparent)]" />
            </section>
        </div>
    );
}
