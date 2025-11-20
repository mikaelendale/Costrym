import { Button as UIButton } from '@/components/ui/button';
import { FLOW_STEPS, useChatFlowVent } from '@/hooks/useChatFlowVent';
import { cn } from '@/lib/utils';
import { AnimatePresence, motion } from 'framer-motion';
import { LoaderIcon, SendIcon } from 'lucide-react';
import * as React from 'react';
import { useState } from 'react';

const Textarea = React.forwardRef(({ className, containerClassName, showRing = true, ...props }, ref) => {
    const [isFocused, setIsFocused] = React.useState(false);

    return (
        <div className={cn('relative', containerClassName)}>
            <textarea
                className={cn(
                    'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                    'transition-all duration-200 ease-in-out',
                    'placeholder:text-muted-foreground',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    showRing ? 'focus-visible:ring-0 focus-visible:ring-offset-0 focus-visible:outline-none' : '',
                    className,
                )}
                ref={ref}
                onFocus={() => setIsFocused(true)}
                onBlur={() => setIsFocused(false)}
                {...props}
            />
            {showRing && isFocused && (
                <motion.span
                    className="pointer-events-none absolute inset-0 rounded-md ring-2 ring-violet-500/30 ring-offset-0"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.2 }}
                />
            )}
            {props.onChange && (
                <div
                    className="absolute right-2 bottom-2 h-2 w-2 rounded-full bg-violet-500 opacity-0"
                    style={{
                        animation: 'none',
                    }}
                    id="textarea-ripple"
                />
            )}
        </div>
    );
});
Textarea.displayName = 'Textarea';

export function AnimatedAIChatVent({
    onSend,
    isSending,
    disabled,
    placeholder,
    compact = false,
    showDoneAction = false,
    onDone,
    doneLabel = 'Done',
    suggestions = [],
    onSelectSuggestion,
}) {
    // Start from classification step per requirement
    const { value, setValue, isTyping, flowStep, setFlowStep, handleSend } = useChatFlowVent({ initialStep: FLOW_STEPS.CLASSIFY, onSend });
    const [inputFocused, setInputFocused] = useState(false);

    const handleSendMessage = async () => {
        if (!value.trim()) return;
        await handleSend();
    };

    const effectiveIsTyping = isSending ?? isTyping;

    return (
        <div
            className={cn(
                compact
                    ? 'w-full'
                    : 'relative flex h-[55vh] w-full flex-col items-center justify-center overflow-hidden bg-transparent p-6 text-foreground',
            )}
        >
            <div className={cn('relative mx-auto w-full', compact ? 'max-w-none' : 'max-w-2xl')}>
                <motion.div
                    className={cn('relative z-10', compact ? 'space-y-4' : 'space-y-12')}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.6, ease: 'easeOut' }}
                >
                    <motion.div
                        className="relative rounded-2xl border border-border bg-card shadow-2xl backdrop-blur-2xl"
                        initial={{ scale: 0.98 }}
                        animate={{ scale: 1 }}
                        transition={{ delay: 0.1 }}
                    >
                        {Array.isArray(suggestions) && suggestions.length > 0 && (
                            <div className="px-4 pt-4">
                                <div className="no-scrollbar flex gap-2 overflow-x-auto">
                                    {suggestions.map((answer, i) => (
                                        <UIButton
                                            key={i}
                                            type="button"
                                            variant="secondary"
                                            className="rounded-full whitespace-nowrap"
                                            onClick={() => !disabled && onSelectSuggestion && onSelectSuggestion(answer)}
                                            disabled={disabled}
                                        >
                                            {answer}
                                        </UIButton>
                                    ))}
                                </div>
                            </div>
                        )}

                        <div className="p-4">
                            <Textarea
                                value={value}
                                onChange={(e) => {
                                    setValue(e.target.value);
                                }}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        if (!disabled && value.trim()) {
                                            handleSendMessage();
                                        }
                                    }
                                }}
                                onFocus={() => setInputFocused(true)}
                                onBlur={() => setInputFocused(false)}
                                placeholder={placeholder || 'What are you thinking about...'}
                                containerClassName="w-full"
                                className={cn(
                                    'w-full px-4 py-3',
                                    'resize-none',
                                    'bg-transparent',
                                    'border-none',
                                    'text-sm text-foreground',
                                    'focus:outline-none',
                                    'placeholder:text-muted-foreground',
                                    'min-h-[60px]',
                                )}
                                style={{ overflow: 'hidden' }}
                                showRing={false}
                                disabled={disabled}
                            />
                        </div>

                        <div className="flex items-center justify-between gap-4 border-t border-border p-4">
                            {showDoneAction && (
                                <Card className="">
                                    <CardContent className="">
                                        <UIButton onClick={onDone} className="w-full">
                                            {doneLabel}
                                        </UIButton>
                                    </CardContent>
                                </Card>
                            )}

                            <motion.button
                                type="button"
                                onClick={handleSendMessage}
                                whileHover={{ scale: 1.01 }}
                                whileTap={{ scale: 0.98 }}
                                disabled={effectiveIsTyping || disabled || !value.trim()}
                                className={cn(
                                    'rounded-lg px-4 py-2 text-sm font-medium transition-all',
                                    'flex items-center gap-2',
                                    value.trim() && !disabled ? 'bg-primary text-primary-foreground shadow-lg' : 'bg-muted text-muted-foreground',
                                )}
                            >
                                {effectiveIsTyping ? (
                                    <LoaderIcon className="h-4 w-4 animate-[spin_2s_linear_infinite]" />
                                ) : (
                                    <SendIcon className="h-4 w-4" />
                                )}
                                <span>{flowStep === FLOW_STEPS.CLASSIFY ? 'Start' : 'Send'}</span>
                            </motion.button>
                        </div>
                    </motion.div>
                </motion.div>
            </div>
            {!compact && (
                <AnimatePresence>
                    {effectiveIsTyping && (
                        <motion.div
                            className="fixed bottom-8 mx-auto -translate-x-1/2 transform rounded-full border border-white/[0.05] bg-white/[0.02] px-4 py-2 shadow-lg backdrop-blur-2xl"
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: 20 }}
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex h-7 w-8 items-center justify-center rounded-full bg-white/[0.05] text-center">
                                    <span className="mb-0.5 text-xs font-medium text-white/90">zap</span>
                                </div>
                                <div className="flex items-center gap-2 text-sm text-white/70">
                                    <span>Thinking</span>
                                    <TypingDots />
                                </div>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>
            )}
            {!compact && inputFocused && (
                <motion.div
                    className="pointer-events-none fixed z-0 h-[50rem] w-[50rem] rounded-full bg-gradient-to-b from-violet-500 via-fuchsia-500 to-indigo-500 opacity-[0.02] blur-[96px]"
                    transition={{
                        type: 'spring',
                        damping: 25,
                        stiffness: 150,
                        mass: 0.5,
                    }}
                />
            )}
        </div>
    );
}

function TypingDots() {
    return (
        <div className="ml-1 flex items-center">
            {[1, 2, 3].map((dot) => (
                <motion.div
                    key={dot}
                    className="mx-0.5 h-1.5 w-1.5 rounded-full bg-white/90"
                    initial={{ opacity: 0.3 }}
                    animate={{
                        opacity: [0.3, 0.9, 0.3],
                        scale: [0.85, 1.1, 0.85],
                    }}
                    transition={{
                        duration: 1.2,
                        repeat: Infinity,
                        delay: dot * 0.15,
                        ease: 'easeInOut',
                    }}
                    style={{
                        boxShadow: '0 0 4px rgba(255, 255, 255, 0.3)',
                    }}
                />
            ))}
        </div>
    );
}

const rippleKeyframes = `
@keyframes ripple {
  0% { transform: scale(0.5); opacity: 0.6; }
  100% { transform: scale(2); opacity: 0; }
}
`;

const hideScrollbarCSS = `
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
`;

if (typeof document !== 'undefined') {
    const style = document.createElement('style');
    style.innerHTML = rippleKeyframes + hideScrollbarCSS;
    document.head.appendChild(style);
}
