type Step = { name: string; description: string; isCompleted: boolean };

export const WORKFLOW_STEPS: Record<number, Step[]> = {
    1: [
        { name: 'Connect Sources', description: 'User connects bank, Stripe, QuickBooks, or uploads data', isCompleted: true },
        { name: 'Ingest Data', description: 'System polls APIs/webhooks and stores raw transactions', isCompleted: false },
        { name: 'Persist Events', description: 'Append-only storage of all financial events', isCompleted: false },
    ],
    2: [
        { name: 'Categorize Transactions', description: 'Map raw data to cost categories using rules/ML', isCompleted: true },
        { name: 'Tag & Assess', description: 'Assign meta-tags and assess direct/indirect/fixed/variable costs', isCompleted: false },
        { name: 'Price Assessment', description: 'Determine pricing and cost center mapping', isCompleted: false },
    ],
    3: [
        { name: 'Aggregate Metrics', description: 'Compute rolling spend, burn rate, runway, etc.', isCompleted: true },
        { name: 'Pattern Recognition', description: 'Identify recurring vs one-time costs', isCompleted: false },
        { name: 'Store Baselines', description: 'Persist metrics for anomaly detection', isCompleted: false },
    ],
    4: [
        { name: 'Decompose Costs', description: 'Break down costs by product, function, resource', isCompleted: true },
        { name: 'Benchmark', description: 'Compare against industry standards or should-cost models', isCompleted: false },
        { name: 'Compute Efficiency', description: 'Calculate cost efficiency ratios and variances', isCompleted: false },
    ],
    5: [
        { name: 'Root-Cause Analysis', description: 'Trace inefficiencies to their source', isCompleted: true },
        { name: 'Simulate Impact', description: 'Run scenario-based cost reduction simulations', isCompleted: false },
        { name: 'Rank Recommendations', description: 'Prioritize optimizations by ROI, risk, effort', isCompleted: false },
    ],
    6: [
        { name: 'Map Value', description: 'Calculate value contribution of each cost', isCompleted: true },
        { name: 'Segment Costs', description: 'Classify costs as sustain, optimize, eliminate, reallocate', isCompleted: false },
        { name: 'Smart Cut Plan', description: 'Generate actionable reduction plan', isCompleted: false },
    ],
    7: [
        { name: 'Generate Actions', description: 'Transform recommendations into executable instructions', isCompleted: true },
        { name: 'Check Dependencies', description: 'Validate org rules and dependencies', isCompleted: false },
        { name: 'Prepare Execution', description: 'Draft API calls and user notifications', isCompleted: false },
    ],
    8: [
        { name: 'Present Actions', description: 'Show proposed actions to user for approval', isCompleted: true },
        { name: 'Collect Decision', description: 'User approves or rejects each action', isCompleted: false },
        { name: 'Escalate if Needed', description: 'Route to expert if user rejects or requests help', isCompleted: false },
    ],
    9: [
        { name: 'Execute Action', description: 'Carry out approved actions via APIs', isCompleted: true },
        { name: 'Log Outcome', description: 'Record execution results and update system state', isCompleted: false },
        { name: 'Notify User', description: 'Inform user of completion and impact', isCompleted: false },
    ],
};

export const WORKFLOWS = [
    {
        id: 1,
        title: 'Data Ingestion & Continuous Monitoring Layer',
        status: 'COMPLETED',
        description: 'Connects and ingests financial data from various sources on a schedule or in real-time.',
    },
    {
        id: 2,
        title: 'Categorization Engine',
        status: 'COMPLETED',
        description: 'Maps raw transactions to cost categories and tags using rules and ML.',
    },
    {
        id: 3,
        title: 'Historical Baselines & Pattern Recognition',
        status: 'COMPLETED',
        description: 'Analyzes spend patterns, computes baselines, and detects anomalies.',
    },
    {
        id: 4,
        title: 'Cost Decomposition & Benchmarking',
        status: 'COMPLETED',
        description: 'Breaks down costs and benchmarks them against industry standards.',
    },
    {
        id: 5,
        title: 'Cost Optimization',
        status: 'COMPLETED',
        description: 'Generates and simulates cost reduction strategies with quantified impact.',
    },
    {
        id: 6,
        title: 'Cost-to-Value Alignment',
        status: 'PENDING',
        description: 'Aligns costs with value contribution to prioritize high-ROI spending.',
    },
    {
        id: 7,
        title: 'Action Planning Layer',
        status: 'PENDING',
        description: 'Creates executable instructions for cost optimization actions.',
    },
    {
        id: 8,
        title: 'User Approval Layer',
        status: 'PENDING',
        description: 'Presents proposed actions to users for approval before execution.',
    },
    {
        id: 9,
        title: 'Autonomous Execution',
        status: 'PENDING',
        description: 'Executes approved actions automatically via integrated APIs.',
    },
];
