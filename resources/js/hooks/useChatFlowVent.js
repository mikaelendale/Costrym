// Shared chat flow logic & state management for AnimatedAIChat* components.
// Provides a simple finite state machine with an optional initial classification step.
// Assumptions (can be refined later):
// 1. "Classification" means we first run the raw user input through a classifier before normal send.
// 2. The consumer may supply an async `onClassify(message)` that returns a classification object/string.
// 3. After classification we proceed to the normal input flow and call `onSend` with enriched payload.
// 4. If `onSend` is not provided we simulate a typing delay (demo mode) like the original component.

import { useState, useCallback } from "react";

export const FLOW_STEPS = {
    CLASSIFY: "classify",
    INPUT: "input",
};

/**
 * useChatFlow
 * @param {Object} params
 * @param {string} [params.initialStep="input"] - initial flow step ("classify" | "input")
 * @param {(message: string, meta?: any) => Promise<void>|void} [params.onSend] - final send handler
 * @param {(message: string) => Promise<any>|any} [params.onClassify] - classifier function
 * @param {(classification: any, message: string) => any} [params.transformClassified] - optional transform before onSend
 * @param {number} [params.demoDelayMs=3000] - fallback demo typing delay when no onSend provided
 */
export function useChatFlowVent({
    initialStep = FLOW_STEPS.INPUT,
    onSend,
    onClassify,
    transformClassified,
    demoDelayMs = 3000,
} = {}) {
    const [value, setValue] = useState("");
    const [isTyping, setIsTyping] = useState(false); // internal typing indicator (demo mode)
    const [flowStep, setFlowStep] = useState(initialStep);

    const resetInput = useCallback(() => {
        setValue("");
    }, []);

    // Basic placeholder heuristic classifier if none provided.
    const defaultClassifier = useCallback((message) => {
        const lower = message.toLowerCase();
        if (/[?]/.test(lower)) return { category: "question" };
        if (lower.includes("bug") || lower.includes("error"))
            return { category: "issue" };
        if (lower.includes("idea") || lower.includes("feature"))
            return { category: "idea" };
        return { category: "general" };
    }, []);

    const handleSend = useCallback(async () => {
        if (!value.trim()) return;
        const message = value.trim();

        // Classification step
        if (flowStep === FLOW_STEPS.CLASSIFY) {
            let classification;
            try {
                classification =
                    (await (onClassify || defaultClassifier)(message)) ?? null;
            } catch (err) {
                classification = {
                    category: "classification_error",
                    error: String(err),
                };
            }

            const meta = {
                phase: FLOW_STEPS.CLASSIFY,
                classification,
            };
            const maybeTransformed = transformClassified
                ? transformClassified(classification, message)
                : undefined;

            if (onSend) {
                // Keep first arg as the plain message for backward-compat; attach details in meta.
                await onSend(message, {
                    ...meta,
                    transformed: maybeTransformed,
                });
            } else {
                // demo fallback
                setIsTyping(true);
                setTimeout(() => setIsTyping(false), demoDelayMs);
            }

            resetInput();
            setFlowStep(FLOW_STEPS.INPUT); // advance to regular input phase
            return;
        }

        // Normal input phase
        if (onSend) {
            await onSend(message, { phase: FLOW_STEPS.INPUT });
            resetInput();
        } else {
            setIsTyping(true);
            setTimeout(() => {
                setIsTyping(false);
                resetInput();
            }, demoDelayMs);
        }
    }, [
        value,
        flowStep,
        onSend,
        onClassify,
        transformClassified,
        defaultClassifier,
        demoDelayMs,
        resetInput,
    ]);

    return {
        value,
        setValue,
        isTyping,
        flowStep,
        handleSend,
        setFlowStep,
        resetInput,
        FLOW_STEPS,
    };
}
