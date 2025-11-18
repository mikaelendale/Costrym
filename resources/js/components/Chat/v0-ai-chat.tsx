'use client';

import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { ArrowUpIcon, Paperclip } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface UseAutoResizeTextareaProps {
    minHeight: number;
    maxHeight?: number;
}

function useAutoResizeTextarea({ minHeight, maxHeight }: UseAutoResizeTextareaProps) {
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    const adjustHeight = useCallback(
        (reset?: boolean) => {
            const textarea = textareaRef.current;
            if (!textarea) return;

            if (reset) {
                textarea.style.height = `${minHeight}px`;
                return;
            }

            // Temporarily shrink to get the right scrollHeight
            textarea.style.height = `${minHeight}px`;

            // Calculate new height
            const newHeight = Math.max(minHeight, Math.min(textarea.scrollHeight, maxHeight ?? Number.POSITIVE_INFINITY));

            textarea.style.height = `${newHeight}px`;
        },
        [minHeight, maxHeight],
    );

    useEffect(() => {
        // Set initial height
        const textarea = textareaRef.current;
        if (textarea) {
            textarea.style.height = `${minHeight}px`;
        }
    }, [minHeight]);

    // Adjust height on window resize
    useEffect(() => {
        const handleResize = () => adjustHeight();
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, [adjustHeight]);

    return { textareaRef, adjustHeight };
}

export function VercelV0Chat({ userName }: { userName?: string }) {
    const [value, setValue] = useState('');
    const { textareaRef, adjustHeight } = useAutoResizeTextarea({
        minHeight: 60,
        maxHeight: 200,
    });

    useEffect(() => {
        // Adjust height to fit the prefilled content
        setTimeout(() => adjustHeight(), 0);
    }, [adjustHeight]);

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (value.trim()) {
                setValue('');
                adjustHeight(true);
            }
        }
    };

    return (
        <div className="mx-auto flex w-full max-w-4xl flex-col items-center space-y-8 p-4">
            <h1 className="text-4xl font-bold text-black dark:text-white">{`Welcome${userName ? `, ${userName}` : ''}.`}</h1>

            <div className="w-full">
                <div className="relative rounded-xl border border-neutral-800 bg-neutral-900">
                    <div className="overflow-y-auto">
                        <Textarea
                            ref={textareaRef}
                            value={value}
                            onChange={(e) => {
                                setValue(e.target.value);
                                adjustHeight();
                            }}
                            onKeyDown={handleKeyDown}
                            placeholder="Ask v0 a question..."
                            className={cn(
                                'w-full px-4 py-3',
                                'resize-none',
                                'bg-transparent',
                                'border-none',
                                'text-sm text-white',
                                'focus:outline-none',
                                'focus-visible:ring-0 focus-visible:ring-offset-0',
                                'placeholder:text-sm placeholder:text-neutral-500',
                                'min-h-[60px]',
                            )}
                            style={{
                                overflow: 'hidden',
                            }}
                        />
                    </div>

                    <div className="flex items-center justify-between p-3">
                        <div className="flex items-center gap-2">
                            <button type="button" className="group flex items-center gap-1 rounded-lg p-2 transition-colors hover:bg-neutral-800">
                                <Paperclip className="h-4 w-4 text-white" />
                                <span className="hidden text-xs text-zinc-400 transition-opacity group-hover:inline">Attach</span>
                            </button>
                        </div>
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                className={cn(
                                    'flex items-center justify-between gap-1 rounded-lg border border-zinc-700 px-1.5 py-1.5 text-sm transition-colors hover:border-zinc-600 hover:bg-zinc-800',
                                    value.trim() ? 'bg-white text-black' : 'text-zinc-400',
                                )}
                            >
                                <ArrowUpIcon className={cn('h-4 w-4', value.trim() ? 'text-black' : 'text-zinc-400')} />
                                <span className="sr-only">Send</span>
                            </button>
                        </div>
                    </div>
                </div>

                {/* <div className="mt-4 flex items-center justify-center gap-3">
                    <ActionButton icon={<ImageIcon className="h-4 w-4" />} label="Clone a Screenshot" />
                    <ActionButton icon={<Figma className="h-4 w-4" />} label="Import from Figma" />
                    <ActionButton icon={<FileUp className="h-4 w-4" />} label="Upload a Project" />
                    <ActionButton icon={<MonitorIcon className="h-4 w-4" />} label="Landing Page" />
                    <ActionButton icon={<CircleUserRound className="h-4 w-4" />} label="Sign Up Form" />
                </div> */}
            </div>
        </div>
    );
}
