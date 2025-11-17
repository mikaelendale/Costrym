import React, { useState } from 'react';
import { Button } from '../ui/button';

type OptionId = 'large' | 'sme';

const OPTIONS: { id: OptionId; title: string; description: string[] }[] = [
    {
        id: 'large',
        title: 'Large Scale Enterprises',
        description: [
            'Our experts have collectively saved 1bn+ in manufacturing costs for their clients and Costrym was trained on their thinking process.',
            'Expose spend leaks your finance team doesn’t have time to chase or the ability to understand',
            'Kill tiny costs that could save you millions',
            'Gain a 24/7 surgical layer over every department’s costs.',
            'Deliver measurable savings at scale — automatically.',
        ],
    },
    {
        id: 'sme',
        title: 'SMEs',
        description: [
            'Get Fortune-500 cost discipline without hiring analysts.',
            'Automatically detect waste you never knew existed.',
            'Extend runway instantly — no extra headcount.',
            'Only pay when Costrym saves you money.',
        ],
    },
];

const SelectSector: React.FC = () => {
    const [selected, setSelected] = useState<OptionId>('large');

    const active = (id: OptionId) => selected === id;

    const current = OPTIONS.find((o) => o.id === selected)!;

    return (
        <div className="select-sector">
            <div className="flex gap-3" role="tablist" aria-label="Select sector">
                {OPTIONS.map((opt) => (
                    <Button
                        key={opt.id}
                        type="button"
                        role="tab"
                        aria-selected={active(opt.id)}
                        data-state={active(opt.id) ? 'active' : 'inactive'}
                        onClick={() => setSelected(opt.id)}
                        variant={active(opt.id) ? 'default' : 'outline'}
                        size="sm"
                        className="group relative rounded-full px-4 transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none data-[state=inactive]:text-muted-foreground hover:data-[state=inactive]:bg-accent hover:data-[state=inactive]:text-foreground"
                    >
                        {opt.title}
                        <span
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-x-3 -bottom-1 h-0.5 rounded-full bg-primary/80 opacity-0 transition-opacity group-data-[state=active]:opacity-100"
                        />
                    </Button>
                ))}
            </div>

            <div className="mt-4 rounded-md border bg-white p-4 shadow-sm">
                <h3 className="text-lg font-semibold">{current.title}</h3>
                <div className="mt-2 space-y-2 text-sm text-gray-700">
                    {current.description.map((line, idx) => (
                        <p key={idx}>{line}</p>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default SelectSector;
