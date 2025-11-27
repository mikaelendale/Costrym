import { OptCostItem, optimizedCosts } from '@/data/optimizedCosts';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { Card } from '../ui/card';
import CostDetailModal from './CostDetailModal';

const OptomizedCost = () => {
    const [openModal, setOpenModal] = useState(false);
    const [selectedCost, setSelectedCost] = useState<OptCostItem | null>(null);

    // const handleOpen = (item: OptomizedCostItem) => {
    //     setSelected(item);
    //     setOpen(true);
    // };

    return (
        <div className="flex flex-col gap-6">
            <h2 className=" text-2xl font-normal tracking-tight ">Optimized Costs</h2>
            <div className="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {optimizedCosts.slice(0, 6).map((item) => (
                    <Card
                        key={item.id}
                        className="group glass-card relative h-full cursor-pointer overflow-hidden rounded-2xl p-1 pb-3 text-foreground transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                    >
                        <div className="glass-sheen" />
                        <div className="relative flex items-center justify-between border-b border-accent p-3">
                            <div className="flex items-center gap-3">
                                <h3 className="truncate text-base font-normal" title={item.name}>
                                    {item.name}
                                </h3>
                            </div>
                            <span className={`rounded-md  px-2.5 py-1 text-[10px] font-medium ${item.status === 'In Progress' ? 'bg-accent/15 text-primary ring-1 ring-accent/30' : 'bg-accent/25 text-primary ring-1 ring-accent/20'}`}>{item.status}</span>
                        </div>
                        <div className="relative px-4 text-sm text-muted-foreground">
                            <p className="">{item.costDescription}</p>
                        </div>
                        <div className="relative flex items-center justify-between px-4 text-xs text-muted-foreground">
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
                    href="/optimization-costs"
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
