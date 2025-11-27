import { WORKFLOW_STEPS, WORKFLOWS } from '@/data/workflow';
import { GRADIENTS_2 } from '@/utils/gradient';
import React, { useMemo } from 'react';
import { Card } from '../ui/card';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '../ui/carousel';

type Step = { name: string; description: string; isCompleted: boolean };

type Props = {
    onSelect?: (workflow: { title: string; steps: Step[] }) => void;
};

const WorkflowCards: React.FC<Props> = ({ onSelect }) => {
    // Gradient variations inspired by the provided base gradient
    const GRADIENTS = useMemo(() => GRADIENTS_2(), []);

    return (
        <div className="mx-auto flex w-full flex-col gap-8 px-4">
            <div className="w-full text-2xl font-normal">Recent Activities</div>

            <Carousel
                opts={{
                    align: 'start',
                    loop: false,
                }}
                className="w-full"
            >
                <CarouselContent className="-ml-2 md:-ml-4">
                    {WORKFLOWS.map((workflow, idx) => {
                        const isPending = workflow.status === 'PENDING';
                        return (
                            <CarouselItem
                                key={workflow.id}
                                className="pl-2 md:pl-4 basis-full sm:basis-1/2 md:basis-1/3 lg:basis-1/4 xl:basis-1/3"
                            >
                                <Card
                                    className="group relative flex h-64 min-h-64 cursor-pointer flex-col overflow-hidden rounded-2xl border border-accent text-primary shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:ring-1 hover:ring-accent/40"
                                    onClick={() =>
                                        onSelect?.({
                                            title: workflow.title,
                                            steps: WORKFLOW_STEPS[workflow.id] ?? [],
                                        })
                                    }
                                >
                                    <div className="flex flex-col gap-2 p-4 pb-2">
                                        <div className="flex items-start justify-between gap-3">
                                            <span className="truncate text-lg font-semibold drop-shadow" title={workflow.title}>
                                                {workflow.title}
                                            </span>
                                            <span
                                                className={`rounded-md px-2.5 py-1 text-[10px] font-medium backdrop-blur-sm ${
                                                    workflow.status === 'ENABLED'
                                                        ? 'bg-accent/15 text-primary ring-1 ring-accent/30'
                                                        : isPending
                                                          ? 'animate-pulse bg-accent/25 text-primary ring-1 ring-accent/40'
                                                          : 'bg-accent/25 text-primary ring-1 ring-accent/20'
                                                }`}
                                            >
                                                {isPending ? 'Pending…' : workflow.status === 'ENABLED' ? 'Active' : workflow.status}
                                            </span>
                                        </div>
                                        <p className="line-clamp-4 text-sm leading-relaxed text-muted-foreground">{workflow.description}</p>
                                    </div>
                                    <div className="mt-auto flex items-center justify-between px-4 pb-4 text-xs text-muted-foreground">
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
                                </Card>
                            </CarouselItem>
                        );
                    })}
                </CarouselContent> 
            </Carousel>
        </div>
    );
};

export default WorkflowCards;
