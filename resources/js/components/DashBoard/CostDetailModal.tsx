import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { OptCostItem } from '@/data/optimizedCosts';
import { BadgeCheck } from 'lucide-react';

type CostDetailModalProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    item: OptCostItem | null;
};

export default function CostDetailModal({ open, onOpenChange, item }: CostDetailModalProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-lg">
                {item && (
                    <>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <span>{item.name}</span>
                                {item.status === 'Completed' && <BadgeCheck className="h-4 w-4 text-green-500" />}
                            </DialogTitle>
                            <DialogDescription>Detailed breakdown of your optimized cost</DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 text-sm">
                            <div className="grid grid-cols-2 gap-3">
                                <div className="rounded-md border p-3">
                                    <div className="text-muted-foreground">Status</div>
                                    <div className="font-medium">{item.status}</div>
                                </div>
                                <div className="rounded-md border p-3">
                                    <div className="text-muted-foreground">Estimated Savings</div>
                                    <div className="font-medium">{item.savings}</div>
                                </div>
                                <div className="rounded-md border p-3">
                                    <div className="text-muted-foreground">Previous Expense</div>
                                    <div className="font-medium">{item.previousExpense}</div>
                                </div>
                                <div className="rounded-md border p-3">
                                    <div className="text-muted-foreground">Saved Per Month</div>
                                    <div className="font-medium">{item.savedPerMonth}</div>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <div className="text-muted-foreground">What was the cost?</div>
                                <p className="leading-relaxed">{item.costDescription}</p>
                            </div>

                            <div className="space-y-2">
                                <div className="text-muted-foreground">How it was saved</div>
                                <p className="leading-relaxed">{item.method}</p>
                            </div>
                        </div>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
