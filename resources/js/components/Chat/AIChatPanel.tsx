import { AnimatedAIChatVent } from '@/components/Chat/animated-ai-chat-vent';
import { useEffect, useRef, useState } from 'react';

type ChatMessageType = { sender: 'user' | 'agent'; content: string | string[] };

const ChatMessage = ({ message }: { message: ChatMessageType }) => {
    const isUser = message.sender === 'user';
    const bubbleClass = isUser ? 'bg-primary text-primary-foreground' : 'bg-secondary text-secondary-foreground';
    const alignment = isUser ? 'ml-auto' : 'mr-auto';
    return (
        <div className={`max-w-[85%] rounded-lg p-3 shadow md:max-w-[70%] ${bubbleClass} ${alignment}`}>
            {Array.isArray(message.content) ? (
                <ul className="list-inside list-disc">
                    {message.content.map((item, index) => (
                        <li key={index}>{item}</li>
                    ))}
                </ul>
            ) : (
                <p>{message.content}</p>
            )}
        </div>
    );
};

export default function AIChatPanel({ userName }: { userName?: string }) {
    const [shouldAutoScroll, setShouldAutoScroll] = useState(true);
    const [chatHistory, setChatHistory] = useState<ChatMessageType[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [agentActions, setAgentActions] = useState<{ type: 'suggested_answers'; data: string[] } | null>(null);
    const [mounted, setMounted] = useState(false);

    const chatEndRef = useRef<HTMLDivElement | null>(null);
    const messagesContainerRef = useRef<HTMLDivElement | null>(null);

    // Helper to decide if we should auto-scroll (user near bottom before update)
    const computeShouldAutoScroll = () => {
        if (!messagesContainerRef.current) return true;
        const { scrollTop, scrollHeight, clientHeight } = messagesContainerRef.current;
        const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);
        return distanceFromBottom < 80;
    };

    // Auto-scroll only if user was near the bottom prior to the update
    useEffect(() => {
        if (!shouldAutoScroll) return;
        if (chatHistory.length === 0) return;
        chatEndRef.current?.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
        });
    }, [chatHistory, shouldAutoScroll]);

    // mount animation: expand on Y axis from a line (bottom origin)
    useEffect(() => {
        const t = setTimeout(() => setMounted(true), 50);
        return () => clearTimeout(t);
    }, []);

    const callAgent = async (userPrompt: string) => {
        setIsLoading(true);
        try {
            await new Promise((r) => setTimeout(r, 700));

            // Placeholder for processing real agent response
            setAgentActions(null);
            setChatHistory((prev) => [...prev, { sender: 'agent', content: `Echo: ${userPrompt}` }]);
        } catch (error) {
            console.error('Mock agent failed:', error);
            setChatHistory((prev) => [...prev, { sender: 'agent', content: 'Something went wrong locally.' }]);
        } finally {
            setIsLoading(false);
        }
    };

    const handleSendFromAnimated = (text: string) => {
        if (!text?.trim() || isLoading) return;
        // capture scroll state before adding a new message
        setShouldAutoScroll(computeShouldAutoScroll());
        setChatHistory((prev) => [...prev, { sender: 'user', content: text }]);
        callAgent(text);
    };

    const handleSuggestedAnswerClick = (answer: string) => {
        if (isLoading) return;

        setChatHistory((prev) => [...prev, { sender: 'user', content: answer }]);
        setAgentActions(null);
        callAgent(answer);
    };

    const suggestionItems = (agentActions?.type === 'suggested_answers' ? agentActions.data : []) as unknown as never[];

    return (
        <div
            style={{
                transform: mounted ? 'scaleY(1)' : 'scaleY(0)',
                transformOrigin: 'bottom',
                transition: 'transform 1000ms cubic-bezier(.2,.8,.2,1), opacity 10ms ease',
                opacity: mounted ? 1 : 0,
            }}
            className="mx-auto flex h-[40vh] w-full max-w-4xl flex-col items-center justify-center py-8"
        >
            <div className="h-full max-w-4xl text-center">
                <h1 className="bg-gradient-to-r from-foreground to-foreground/60 bg-clip-text pb-2 text-4xl font-semibold tracking-tight text-transparent md:text-5xl">
                    Time to reduce your cost, {userName}
                </h1>
                <div className="h-px w-full bg-gradient-to-r from-transparent via-border to-transparent" />
            </div>

            <div
                ref={messagesContainerRef}
                className="m-4 mx-auto flex min-h-0 w-full max-w-3xl flex-1 flex-col gap-3 overflow-y-auto pr-2"
                onScroll={() => {
                    if (!messagesContainerRef.current) return;
                    const { scrollTop, scrollHeight, clientHeight } = messagesContainerRef.current;
                    const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);
                    // If user scrolls up far enough, disable auto-scroll; re-enable when near bottom
                    setShouldAutoScroll(distanceFromBottom < 80);
                }}
            >
                {chatHistory.map((msg, index) => (
                    <ChatMessage key={index} message={msg} />
                ))}
                {isLoading && (
                    <ChatMessage
                        message={{
                            sender: 'agent',
                            content: 'Thinking...',
                        }}
                    />
                )}
                <div ref={chatEndRef} />
            </div>

            <div className="flex w-full max-w-3xl justify-center">
                <AnimatedAIChatVent
                    compact
                    onSend={handleSendFromAnimated}
                    onDone={() => {}}
                    isSending={isLoading}
                    disabled={isLoading}
                    placeholder={`Create a workflow ...`}
                    suggestions={suggestionItems}
                    onSelectSuggestion={handleSuggestedAnswerClick}
                />
            </div>
        </div>
    );
}
