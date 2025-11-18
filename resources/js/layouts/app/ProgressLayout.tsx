import { OrderTracking } from '@/components/RightBar/order-tracking';
import { steps } from '@/data/ProgressList';
import { PropsWithChildren } from 'react';

type Step = {
    name: string;
    description: string;
    isCompleted: boolean;
};

type SelectedWorkflow = {
    title: string;
    steps: Step[];
} | null;

const ProgressLayout = ({
    children,
    selectedWorkflow,
    onResetSelection,
}: PropsWithChildren<{ selectedWorkflow?: SelectedWorkflow; onResetSelection?: () => void }>) => {
    return (
        <div className="flex w-full max-w-full flex-1 items-start gap-6 p-6">
            <div className="min-w-0 flex-1">{children}</div>
            <aside className="sticky top-6 h-[calc(100vh-3rem)] w-100 shrink-0 self-start border-l px-4 pt-0 pb-6">
                {selectedWorkflow ? (
                    <div>
                        <div className="mb-3 flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">Workflow: {selectedWorkflow.title}</p>
                            <button
                                type="button"
                                className="inline-flex h-7 items-center rounded-md border px-2 text-xs text-foreground hover:bg-accent"
                                onClick={() => onResetSelection?.()}
                            >
                                Reset
                            </button>
                        </div>
                        <OrderTracking steps={selectedWorkflow.steps} />
                    </div>
                ) : (
                    <OrderTracking steps={steps} />
                )}
            </aside>
        </div>
    );
};

export default ProgressLayout;
