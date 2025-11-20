import { WORKFLOW_STEPS, WORKFLOWS } from '@/data/workflow';
import { GRADIENTS_2 } from '@/utils/gradient';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import React, { useMemo, useRef } from 'react';
import { Card } from '../ui/card';

type Step = { name: string; description: string; isCompleted: boolean };

type Props = {
    onSelect?: (workflow: { title: string; steps: Step[] }) => void;
};

const WorkflowCards: React.FC<Props> = ({ onSelect }) => {
    const scrollRef = useRef<HTMLDivElement>(null);

    // Gradient variations inspired by the provided base gradient
    const GRADIENTS = useMemo(() => GRADIENTS_2(), []);

    const scrollByAmount = (dir: 'left' | 'right') => {
        const el = scrollRef.current;
        if (!el) return;
        const firstCard = el.querySelector('[data-workflow-card]') as HTMLElement | null;
        const gap = 20; // approximate gap between cards
        const step = firstCard ? firstCard.offsetWidth + gap : Math.floor(el.clientWidth * 0.8);
        const amount = dir === 'left' ? -step : step;
        el.scrollBy({ left: amount, behavior: 'smooth' });
    };

    return (
        <div className="mx-auto flex w-full flex-col gap-8">
            <div className="w-full text-center text-4xl font-bold">Recent Activities</div>

            <div className="relative">
                <div
                    ref={scrollRef}
                    role="region"
                    aria-label="Workflow steps carousel"
                    tabIndex={0}
                    onKeyDown={(e) => {
                        if (e.key === 'ArrowLeft') scrollByAmount('left');
                        if (e.key === 'ArrowRight') scrollByAmount('right');
                    }}
                    className="flex snap-x snap-mandatory items-stretch gap-5 overflow-x-auto px-2 pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    {WORKFLOWS.map((workflow, idx) => {
                        const isPending = workflow.status === 'PENDING';
                        return (
                            <Card
                                key={workflow.id}
                                data-workflow-card
                                style={{
                                    backgroundImage: GRADIENTS[idx % GRADIENTS.length],
                                    backgroundSize: 'cover',
                                    backgroundPosition: 'center',
                                }}
                                className="group relative flex h-64 min-h-64 min-w-[260px] flex-[0_0_86%] cursor-pointer snap-start flex-col overflow-hidden rounded-2xl border border-white/10 text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:ring-1 hover:ring-white/40 sm:flex-[0_0_62%] md:flex-[0_0_48%] lg:flex-[0_0_34%] xl:flex-[0_0_28%]"
                                onClick={() =>
                                    onSelect?.({
                                        title: workflow.title,
                                        steps: WORKFLOW_STEPS[workflow.id] ?? [],
                                    })
                                }
                            >
                                {/* subtle dark layer for readability */}
                                <div className="pointer-events-none absolute inset-0 bg-black/20 opacity-0 transition-opacity group-hover:opacity-10" />

                                {/* Pending shine animation */}
                                {isPending && (
                                    <div className="pointer-events-none absolute inset-0 overflow-hidden">
                                        <div className="animate-shine absolute inset-y-0 left-[-50%] w-[55%] rotate-12 bg-gradient-to-r from-transparent via-white/40 to-transparent" />
                                    </div>
                                )}

                                <div className="flex flex-col gap-2 p-4 pb-2">
                                    <div className="flex items-start justify-between gap-3">
                                        <span className="truncate text-lg font-semibold drop-shadow" title={workflow.title}>
                                            {workflow.title}
                                        </span>
                                        <span
                                            className={`rounded-md px-2.5 py-1 text-[10px] font-medium backdrop-blur-sm ${
                                                workflow.status === 'ENABLED'
                                                    ? 'bg-white/15 text-white ring-1 ring-white/30'
                                                    : isPending
                                                      ? 'animate-pulse bg-white/25 text-white ring-1 ring-white/40'
                                                      : 'bg-black/25 text-white ring-1 ring-white/20'
                                            }`}
                                        >
                                            {isPending ? 'Pending…' : workflow.status === 'ENABLED' ? 'Active' : workflow.status}
                                        </span>
                                    </div>
                                    <p className="line-clamp-4 text-sm leading-relaxed text-white/90">{workflow.description}</p>
                                </div>
                                <div className="mt-auto flex items-center justify-between px-4 pb-4 text-xs text-white/80">
                                    <span className="flex items-center gap-2">
                                        Status: {workflow.status}
                                        {isPending && (
                                            <svg
                                                className="h-3.5 w-3.5 animate-spin"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            >
                                                <circle cx="12" cy="12" r="10" className="opacity-20" />
                                                <path d="M4 12a8 8 0 0 1 8-8" />
                                            </svg>
                                        )}
                                    </span>
                                    <span className="opacity-0 transition-opacity group-hover:opacity-100">View details →</span>
                                </div>

                                {/* Overlay message for pending (optional, appears on hover) */}
                                {isPending && (
                                    <div className="pointer-events-none absolute inset-x-0 bottom-0 flex justify-end p-3 text-[11px] font-medium text-white/80 opacity-0 transition-opacity group-hover:opacity-100">
                                        Processing steps… ~10 min
                                    </div>
                                )}
                            </Card>
                        );
                    })}
                </div>

                {/* Left/Right controls overlayed, do not scroll with content */}
                <button
                    type="button"
                    aria-label="Scroll left"
                    onClick={() => scrollByAmount('left')}
                    className="absolute top-1/2 left-0 z-10 -translate-y-1/2 rounded-full bg-background/70 p-2 shadow backdrop-blur hover:bg-background/90 md:left-1"
                >
                    <ChevronLeft className="h-5 w-5" />
                </button>
                <button
                    type="button"
                    aria-label="Scroll right"
                    onClick={() => scrollByAmount('right')}
                    className="absolute top-1/2 right-0 z-10 -translate-y-1/2 rounded-full bg-background/70 p-2 shadow backdrop-blur hover:bg-background/90 md:right-1"
                >
                    <ChevronRight className="h-5 w-5" />
                </button>
            </div>
        </div>
    );
};

export default WorkflowCards;
