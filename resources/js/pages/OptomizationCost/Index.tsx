import CostDetailModal from '@/components/DashBoard/CostDetailModal';
import { Card } from '@/components/ui/card';
import { optimizedCosts, type OptCostItem } from '@/data/optimizedCosts';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Optimized Costs', href: '/optimization-costs' }];

function weekRangeLabel(dateStr: string) {
    const d = new Date(dateStr);
    // compute start of ISO week (Monday)
    const day = d.getDay();
    const diffToMonday = (day + 6) % 7; // 0 -> Monday
    const monday = new Date(d);
    monday.setDate(d.getDate() - diffToMonday);
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);

    const fmt = (dt: Date) => dt.toLocaleDateString(undefined, { month: 'short', day: '2-digit' }).toLowerCase().replace(' ', '-');
    return `${fmt(monday)} to ${fmt(sunday)}`; // e.g. nov-03 to nov-09
}

function groupByWeek(items: OptCostItem[]) {
    const map = new Map<string, OptCostItem[]>();
    for (const it of items) {
        const label = weekRangeLabel(it.date);
        const arr = map.get(label) ?? [];
        arr.push(it);
        map.set(label, arr);
    }
    // sort map by earliest date in each group
    return Array.from(map.entries()).sort((a, b) => {
        const aDate = new Date(a[1][0].date).getTime();
        const bDate = new Date(b[1][0].date).getTime();
        return aDate - bDate;
    });
}

const Index = () => {
    const groups = groupByWeek(optimizedCosts);
    const [openModal, setOpenModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<OptCostItem | null>(null);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Optimized Costs" />
            <div className="mx-auto w-full max-w-6xl py-10">
                <h2 className="mb-6 text-2xl font-semibold">Optimized Costs</h2>

                {groups.map(([label, items]) => (
                    <section key={label} className="mb-8">
                        <h3 className="mb-4 text-sm font-medium text-muted-foreground">{label}</h3>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {items.map((item) => (
                                <Card
                                    key={item.id}
                                    className="group glass-card relative cursor-pointer p-4 transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg"
                                >
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <h4 className="text-base font-semibold">{item.name}</h4>
                                            <p className="mt-2 text-sm text-foreground/70">{item.costDescription}</p>
                                        </div>
                                        <div className="text-right">
                                            <div className="text-sm font-medium">{item.estimatedSavings ?? ''}</div>
                                            <div className="mt-1 text-xs text-muted-foreground">{new Date(item.date).toLocaleDateString()}</div>
                                        </div>
                                    </div>
                                    {/* Hover CTA */}
                                    <div className="pointer-events-none absolute right-4 bottom-3 opacity-0 transition-opacity group-hover:opacity-100">
                                        <span className="inline-flex items-center rounded-full bg-primary px-3 py-1 text-xs font-medium text-primary-foreground shadow-sm">
                                            Open
                                        </span>
                                    </div>

                                    <button
                                        type="button"
                                        className="absolute inset-0 cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-primary/60"
                                        aria-label={`Open ${item.name}`}
                                        onClick={() => {
                                            setSelectedItem(item);
                                            setOpenModal(true);
                                        }}
                                    />
                                </Card>
                            ))}
                        </div>
                    </section>
                ))}
                <CostDetailModal open={openModal} onOpenChange={setOpenModal} item={selectedItem} />
            </div>
        </AppLayout>
    );
};

export default Index;
