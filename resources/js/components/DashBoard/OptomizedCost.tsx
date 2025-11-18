import { OptomizedCostList } from '@/data/data';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { Card } from '../ui/card';
import CostDetailModal, { type OptomizedCostItem } from './CostDetailModal';

const OptomizedCost = () => {
    const [openModal, setOpenModal] = useState(false);
    const [selectedCost, setSelectedCost] = useState<OptomizedCostItem | null>(null);

    // const handleOpen = (item: OptomizedCostItem) => {
    //     setSelected(item);
    //     setOpen(true);
    // };

    return (
        <div className="flex flex-col gap-6">
            <h2 className="text-center text-2xl font-semibold tracking-tight sm:text-4xl">Optimized Costs</h2>
            <div className="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {OptomizedCostList.slice(0, 6).map((item) => (
                    <Card
                        key={item.id}
                        className="group glass-card relative h-full overflow-hidden rounded-2xl p-1 pb-3 text-foreground transition-all duration-300 hover:-translate-y-0.5"
                    >
                        <div className="glass-sheen" />
                        <div className="pointer-events-none absolute inset-x-0 top-0 h-px bg-[var(--glass-border)]" />
                        <div className="relative flex items-center justify-between border-b border-[var(--glass-border)] p-3">
                            <div className="flex items-center gap-3">
                                <span
                                    className="inline-flex h-7 w-7 items-center justify-center rounded-full"
                                    style={{ background: 'linear-gradient(180deg, rgba(255,255,255,0.06), transparent)' }}
                                >
                                    <svg
                                        width="12"
                                        height="12"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        style={{ color: 'var(--color-primary)' }}
                                    >
                                        <circle cx="12" cy="12" r="5" fill="currentColor" />
                                    </svg>
                                </span>
                                <h3 className="truncate text-base font-semibold drop-shadow-sm" title={item.name}>
                                    {item.name}
                                </h3>
                            </div>
                            <span className="glass-badge rounded-md px-2.5 py-1 text-[10px] font-medium text-foreground/80">Optimized</span>
                        </div>
                        <div className="relative px-4 text-sm text-foreground/70">
                            <p className="">{item.costDescription}</p>
                        </div>
                        <div className="relative flex items-center justify-between px-4 text-xs text-foreground/60">
                            <span>Details</span>
                            <span className="opacity-0 transition-opacity group-hover:opacity-100">View details →</span>
                        </div>
                        <button
                            type="button"
                            className="absolute inset-0 outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                            aria-label={`Open ${item.name}`}
                            onClick={() => {
                                setSelectedCost(item);
                                setOpenModal(true);
                            }}
                        />
                    </Card>
                ))}
            </div>
            <div className="mt-2 flex w-full justify-center">
                <Link
                    href="/dashboar"
                    className="rounded-full bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:opacity-95"
                >
                    See more
                </Link>
            </div>

            <CostDetailModal open={openModal} onOpenChange={setOpenModal} item={selectedCost} />
        </div>
    );
};

export default OptomizedCost;
