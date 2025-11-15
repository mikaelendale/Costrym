import { usePage } from '@inertiajs/react';

const Index = () => {
    // usePage().props holds the data passed from the server via Inertia
    const page = usePage();
    // A minimal typed shape for our mock data so we don't use `any`.
    type MockData = {
        notification_title?: string;
        body?: string;
        update_summary?: string;
        details?: {
            what_to_do?: string;
            why?: string;
            impact?: string;
            dependencies?: string;
            risk?: string;
        };
    };

    const { mockData } = (page.props as unknown as { mockData?: MockData }) ?? {};

    if (!mockData) {
        return (
            <div className="mx-auto max-w-3xl p-6">
                <div className="rounded-md border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">No mock data was passed from the server.</div>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-3xl px-4 py-8">
            <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div className="px-6 py-5 sm:px-8 sm:py-6">
                    <h1 className="text-2xl font-semibold text-slate-900 dark:text-slate-100">{mockData.notification_title}</h1>
                    <p className="mt-2 text-slate-700 dark:text-slate-300">{mockData.body}</p>

                    <p className="mt-4 text-sm text-slate-600 dark:text-slate-400">
                        <strong>Summary:</strong> {mockData.update_summary}
                    </p>

                    {mockData.details && (
                        <div className="mt-5">
                            <h2 className="text-lg font-medium text-slate-900 dark:text-slate-100">Details</h2>
                            <ul className="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <li className="text-sm text-slate-700 dark:text-slate-300">
                                    <span className="font-semibold">What to do:</span> {mockData.details.what_to_do}
                                </li>
                                <li className="text-sm text-slate-700 dark:text-slate-300">
                                    <span className="font-semibold">Why:</span> {mockData.details.why}
                                </li>
                                <li className="text-sm text-slate-700 dark:text-slate-300">
                                    <span className="font-semibold">Impact:</span> {mockData.details.impact}
                                </li>
                                <li className="text-sm text-slate-700 dark:text-slate-300">
                                    <span className="font-semibold">Dependencies:</span> {mockData.details.dependencies}
                                </li>
                                <li className="col-span-full text-sm text-slate-700 dark:text-slate-300">
                                    <span className="font-semibold">Risk:</span> {mockData.details.risk}
                                </li>
                            </ul>
                        </div>
                    )}
                </div>

                <div className="border-t border-slate-100 bg-slate-50 p-4 sm:px-6 dark:border-slate-800 dark:bg-slate-950">
                    <h3 className="mb-2 text-sm font-medium text-slate-700 dark:text-slate-300">Raw data</h3>
                    <pre className="overflow-auto rounded bg-slate-100 p-3 text-xs whitespace-pre-wrap text-slate-800 dark:bg-slate-900 dark:text-slate-100">
                        {JSON.stringify(mockData, null, 2)}
                    </pre>
                </div>
            </div>
        </div>
    );
};

export default Index;
