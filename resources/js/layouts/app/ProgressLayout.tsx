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
        <div className="relative flex w-full max-w-full flex-1 items-start gap-6 p-6">
            {/* Use no right padding on small screens; reserve space for right sidebar only on lg+ */}
            <div className="min-w-0 flex-1 pr-0 lg:pr-80">{children}</div>
            {/* Right sidebar hidden on small screens to prevent overlay and overflow */}
            <aside className="fixed top-24 right-6 z-50 hidden h-[calc(100vh-3rem)] w-80 shrink-0 border-l px-4 pt-0 pb-6 lg:block">
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

            {/* Mobile: show a bottom sheet when a workflow is selected so users can view order-tracking on small screens */}
            {selectedWorkflow && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label={`Workflow ${selectedWorkflow.title} status`}
                    className="fixed inset-x-0 bottom-0 z-50 max-h-[75vh] w-full overflow-auto border-t bg-primary-foreground p-4 shadow-lg lg:hidden"
                >
                    <div className="mb-2 flex items-center justify-between">
                        <p className="text-sm font-medium">Workflow: {selectedWorkflow.title}</p>
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                className="inline-flex h-8 items-center rounded-md border px-3 text-sm text-foreground hover:bg-accent"
                                onClick={() => onResetSelection?.()}
                            >
                                Close
                            </button>
                        </div>
                    </div>
                    <div className="mb-4">
                        <OrderTracking steps={selectedWorkflow.steps} />
                    </div>
                </div>
            )}
        </div>
    );
};

export default ProgressLayout;
