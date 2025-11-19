import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Send, Bot, User, Database } from 'lucide-react';
import { useState, useRef, useEffect } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Integration Ingestor',
        href: '/integration-ingestor',
    },
];

interface Message {
    role: 'user' | 'assistant';
    content: string;
    timestamp: Date;
}

export default function IntegrationIngestor() {
    const { auth } = usePage<SharedData>().props;
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [sessionId] = useState(() => `ingestor_${auth.user?.id}_${Date.now()}`);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    const getCsrfToken = (): string => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                     document.querySelector('input[name="_token"]')?.getAttribute('value') ||
                     document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '';
        return decodeURIComponent(token);
    };

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const handleSend = async () => {
        if (!input.trim() || isLoading) {
            return;
        }

        const userMessage: Message = {
            role: 'user',
            content: input.trim(),
            timestamp: new Date(),
        };

        setMessages((prev) => [...prev, userMessage]);
        setInput('');
        setIsLoading(true);

        try {
            const response = await fetch('/integration-ingestor/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    message: userMessage.content,
                    session_id: sessionId,
                }),
            });

            const data = await response.json();

            if (data.success) {
                const assistantMessage: Message = {
                    role: 'assistant',
                    content: data.response || 'No response received',
                    timestamp: new Date(),
                };
                setMessages((prev) => [...prev, assistantMessage]);
            } else {
                const errorMessage: Message = {
                    role: 'assistant',
                    content: `Error: ${data.error || 'Unknown error occurred'}`,
                    timestamp: new Date(),
                };
                setMessages((prev) => [...prev, errorMessage]);
            }
        } catch (error: any) {
            const errorMessage: Message = {
                role: 'assistant',
                content: `Error: ${error.message || 'Failed to send message'}`,
                timestamp: new Date(),
            };
            setMessages((prev) => [...prev, errorMessage]);
        } finally {
            setIsLoading(false);
        }
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Integration Ingestor" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex flex-col gap-2">
                    <h1 className="text-2xl font-bold">Integration Ingestor</h1>
                    <p className="text-muted-foreground">
                        Fetch and aggregate data from all your connected integrations (Notion, Slack, Gmail, etc.)
                    </p>
                </div>

                <div className="flex flex-1 flex-col gap-4 rounded-lg border bg-card p-4">
                    <div className="flex-1 overflow-y-auto space-y-4">
                        {messages.length === 0 ? (
                            <div className="flex h-full items-center justify-center text-center text-muted-foreground">
                                <div className="space-y-2">
                                    <Database className="mx-auto h-12 w-12" />
                                    <p className="text-lg font-medium">Start fetching data from your integrations</p>
                                    <p className="text-sm">Try asking: &quot;Get all my Notion pages and list my Slack channels&quot;</p>
                                </div>
                            </div>
                        ) : (
                            messages.map((message, index) => (
                                <div
                                    key={index}
                                    className={`flex gap-3 ${
                                        message.role === 'user' ? 'justify-end' : 'justify-start'
                                    }`}
                                >
                                    {message.role === 'assistant' && (
                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                            <Bot className="h-4 w-4" />
                                        </div>
                                    )}
                                    <div
                                        className={`max-w-[80%] rounded-lg px-4 py-2 ${
                                            message.role === 'user'
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted'
                                        }`}
                                    >
                                        <p className="whitespace-pre-wrap break-words">{message.content}</p>
                                        <p className="mt-1 text-xs opacity-70">
                                            {message.timestamp.toLocaleTimeString()}
                                        </p>
                                    </div>
                                    {message.role === 'user' && (
                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted">
                                            <User className="h-4 w-4" />
                                        </div>
                                    )}
                                </div>
                            ))
                        )}
                        {isLoading && (
                            <div className="flex gap-3 justify-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                    <Bot className="h-4 w-4" />
                                </div>
                                <div className="rounded-lg bg-muted px-4 py-2">
                                    <div className="flex gap-1">
                                        <span className="h-2 w-2 animate-bounce rounded-full bg-foreground [animation-delay:-0.3s]"></span>
                                        <span className="h-2 w-2 animate-bounce rounded-full bg-foreground [animation-delay:-0.15s]"></span>
                                        <span className="h-2 w-2 animate-bounce rounded-full bg-foreground"></span>
                                    </div>
                                </div>
                            </div>
                        )}
                        <div ref={messagesEndRef} />
                    </div>

                    <div className="flex gap-2 border-t pt-4">
                        <Input
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            onKeyPress={handleKeyPress}
                            placeholder="Ask to fetch data from your integrations..."
                            disabled={isLoading}
                            className="flex-1"
                        />
                        <Button onClick={handleSend} disabled={isLoading || !input.trim()}>
                            <Send className="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}


