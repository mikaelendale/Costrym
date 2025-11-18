import ConnectServiceModal from '@/components/Integrations/ConnectServiceModal';
import { Card, CardTitle } from '@/components/ui/card';
import { Integration, integrations } from '@/data/integrations';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Integrations',
        href: '/integrations',
    },
];

export const Index = () => {
    const [open, setOpen] = useState(false);
    const [selected, setSelected] = useState<Integration | null>(null);

    const selectedTitle = selected?.name ?? '';
    // `selected.icon` is a string URL for the icon; pass it directly to the modal
    const selectedIcons = useMemo(() => selected?.icon ?? '', [selected]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Integrations" />
            <div className="mx-auto w-full max-w-5xl">
                <h2 className="mb-8 text-center text-3xl font-bold tracking-tight sm:text-4xl">Integrated Platforms</h2>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3">
                    {integrations.map((card) => (
                        <Card
                            key={card.name}
                            className="group relative h-full overflow-hidden rounded-xl border border-border/50 bg-card/60 p-4 backdrop-blur transition-colors hover:bg-card"
                        >
                            <div className="mb-3 flex items-center gap-2">
                                <img src={card.icon} alt={`${card.name} icon`} className="h-6 w-6" />
                            </div>

                            <CardTitle className="flex justify-between text-base font-semibold">
                                {card.name}
                                <span
                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                                        card.connected ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'
                                    }`}
                                >
                                    {card.connected ? 'Connected' : 'Not connected'}
                                </span>
                            </CardTitle>

                            <p className="line-clamp-3 text-sm text-muted-foreground">{card.description}</p>

                            <div className="pointer-events-none absolute top-3 right-3 rounded-full bg-primary/10 p-2 text-primary transition-transform duration-200 group-hover:translate-x-0.5">
                                <span className="block text-lg leading-none">→</span>
                            </div>

                            <button
                                type="button"
                                className="absolute inset-0"
                                aria-label={`Open ${card.name}`}
                                onClick={() => {
                                    setSelected(card);
                                    setOpen(true);
                                }}
                            />
                        </Card>
                    ))}
                </div>

                <ConnectServiceModal open={open} onClose={() => setOpen(false)} title={selectedTitle} services={selectedIcons} />
            </div>
        </AppLayout>
    );
};

export default Index;
