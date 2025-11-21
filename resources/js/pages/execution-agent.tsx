import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function ExecutionAgentPage() {
    const { auth } = usePage().props;
    const [tool, setTool] = useState('notion');
    const [instruction, setInstruction] = useState('');
    const [params, setParams] = useState('{}');
    const [isLoading, setIsLoading] = useState(false);
    const [result, setResult] = useState<string | null>(null);

    const getCsrfToken = (): string => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) return token;
        // Fallback to cookie used by Laravel for XSRF-TOKEN
        const cookie = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];
        return cookie ? decodeURIComponent(cookie) : '';
    };

    const handleSubmit = async () => {
        setIsLoading(true);
        setResult(null);

        let parsedParams = {};
        try {
            parsedParams = JSON.parse(params || '{}');
        } catch (e) {
            setResult('Invalid JSON in params');
            setIsLoading(false);
            return;
        }

        try {
            const res = await fetch('/execution-agent/execute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify({
                    tool,
                    instruction,
                    params: parsedParams,
                }),
            });

            const data = await res.json();

            if (data.success) {
                setResult(data.response || 'No response');
            } else {
                setResult('Error: ' + (data.error || 'Unknown'));
            }
        } catch (err: any) {
            setResult('Request failed: ' + (err.message || String(err)));
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Execution Agent" />

            <div className="p-6">
                <h1 className="mb-2 text-2xl font-bold">Execution Agent Test</h1>
                <p className="mb-4 text-sm text-muted-foreground">Send a structured instruction to the ExecutionAgent.</p>

                <div className="max-w-2xl space-y-3">
                    <div>
                        <label className="mb-1 block text-sm font-medium">Tool (app)</label>
                        <Input value={tool} onChange={(e) => setTool((e.target as HTMLInputElement).value)} />
                        <p className="mt-1 text-xs text-muted-foreground">
                            Example: <code>notion</code> or <code>xero_accounting_api</code>
                        </p>
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium">Instruction</label>
                        <textarea
                            value={instruction}
                            onChange={(e) => setInstruction(e.target.value)}
                            rows={4}
                            className="w-full rounded-md border px-3 py-2"
                            placeholder="Describe what you want the agent to do"
                        />
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium">Params (JSON)</label>
                        <textarea
                            value={params}
                            onChange={(e) => setParams(e.target.value)}
                            rows={4}
                            className="w-full rounded-md border px-3 py-2"
                            placeholder='{ "database_id": "..." }'
                        />
                    </div>

                    <div className="flex gap-2">
                        <Button onClick={handleSubmit} disabled={isLoading || !instruction.trim()}>
                            {isLoading ? 'Running...' : 'Run'}
                        </Button>
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium">Result</label>
                        <pre className="rounded-md border bg-muted p-3 whitespace-pre-wrap">{result ?? 'No result yet'}</pre>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
