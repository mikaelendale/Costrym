export type OptCostItem = {
    id: string;
    name: string;
    costDescription: string;
    estimatedSavings?: string;
    date: string;
    savings: string;
    status: 'In Progress' | 'Completed';
    previousExpense: string;
    savedPerMonth: string;
    method: string;
};

// Mock data with dates spanning several weeks
export const optimizedCosts: OptCostItem[] = [
    {
        id: 'oc-1',
        name: 'Reduce idle compute hours',
        costDescription: 'Identify and stop idle cloud instances running outside business hours.',
        estimatedSavings: '$120/mo',
        date: '2025-11-03',
        savings: '$120',
        status: 'In Progress',
        previousExpense: '$300',
        savedPerMonth: '$120',
        method: 'Auto-shutdown scripts',
    },
    {
        id: 'oc-2',
        name: 'Archive cold data',
        costDescription: 'Move rarely accessed storage to a cheaper archival tier.',
        estimatedSavings: '$85/mo',
        date: '2025-11-05',
        savings: '$85',
        status: 'In Progress',
        previousExpense: '$200',
        savedPerMonth: '$85',
        method: 'Lifecycle policies',
    },
    {
        id: 'oc-22',
        name: 'Archive cold data',
        costDescription: 'Move rarely accessed storage to a cheaper archival tier.',
        estimatedSavings: '$85/mo',
        date: '2025-11-05',
        savings: '$85',
        status: 'In Progress',
        previousExpense: '$200',
        savedPerMonth: '$85',
        method: 'Lifecycle policies',
    },
    {
        id: 'oc-3',
        name: 'Right-size DB instances',
        costDescription: 'Downsize overprovisioned database instances after analyzing load patterns.',
        estimatedSavings: '$230/mo',
        date: '2025-11-21',
        savings: '$230',
        status: 'In Progress',
        previousExpense: '$600',
        savedPerMonth: '$230',
        method: 'Performance monitoring',
    },
    {
        id: 'oc-4',
        name: 'Consolidate licenses',
        costDescription: 'Audit and eliminate unused software licenses and subscriptions.',
        estimatedSavings: '$60/mo',
        date: '2025-11-13',
        savings: '$60',
        status: 'In Progress',
        previousExpense: '$150',
        savedPerMonth: '$60',
        method: 'License audits',
    },
    {
        id: 'oc-5',
        name: 'Optimize CDN rules',
        costDescription: 'Cache more static assets and reduce origin requests.',
        estimatedSavings: '$45/mo',
        date: '2025-11-18',
        savings: '$45',
        status: 'In Progress',
        previousExpense: '$100',
        savedPerMonth: '$45',
        method: 'Caching strategies',
    },
    {
        id: 'oc-6',
        name: 'Reduce log retention',
        costDescription: 'Keep logs for only the required retention period and aggregate them.',
        estimatedSavings: '$70/mo',
        date: '2025-11-19',
        savings: '$70',
        status: 'In Progress',
        previousExpense: '$180',
        savedPerMonth: '$70',
        method: 'Log aggregation',
    },
    {
        id: 'oc-7',
        name: 'Use reserved instances',
        costDescription: 'Purchase reserved capacity for predictable workloads.',
        estimatedSavings: '$400/mo',
        date: '2025-11-25',
        savings: '$400',
        status: 'In Progress',
        previousExpense: '$1000',
        savedPerMonth: '$400',
        method: 'Reserved instance purchases',
    },
    {
        id: 'oc-8',
        name: 'Remove unused snapshots',
        costDescription: 'Find and delete orphaned storage snapshots.',
        estimatedSavings: '$30/mo',
        date: '2025-11-26',
        savings: '$30',
        status: 'In Progress',
        previousExpense: '$80',
        savedPerMonth: '$30',
        method: 'Snapshot management',
    },
];
