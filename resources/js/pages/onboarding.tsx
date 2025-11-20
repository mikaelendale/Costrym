import { Button } from '@/components/ui/button';
import AuthSimpleLayout from '@/layouts/auth/auth-simple-layout';
import { type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { ArrowUpIcon, CaretLeftIcon, CaretRightIcon, CheckIcon, FileCsvIcon } from '@phosphor-icons/react';
import { PipedreamClient } from '@pipedream/sdk';
import { GoogleGmail, NotionIcon, Xero, Zoho } from 'brand-logos';
import { Bot, LoaderCircle, Trash2, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { PaddleCheckout } from '@/components/paddle-checkout';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { InputGroup, InputGroupButton, InputGroupTextarea } from '@/components/ui/input-group';

interface Tool {
    name: string;
    appId: string;
    icon?: React.ComponentType<{ className?: string }>;
    iconUrl?: string;
    requiresPipedream: boolean;
    required?: boolean; // Essential/required integration
}

// Essential accounting integrations that users must connect
const essentialTools: Tool[] = [
    {
        name: 'Xero',
        appId: 'xero_accounting_api',
        icon: Xero,
        requiresPipedream: true,
        required: true,
    },
    {
        name: 'Zoho Books',
        appId: 'zoho_books',
        icon: Zoho,
        requiresPipedream: true,
        required: true,
    },
    {
        name: 'QuickBooks Online',
        appId: 'quickbooks',
        iconUrl: 'https://freebiehive.com/wp-content/uploads/2024/06/Quickbooks-Logo-PNG-758x473.jpg',
        requiresPipedream: true,
        required: true,
    },
    {
        name: 'Sevdesk',
        appId: 'sevdesk',
        iconUrl: 'https://www.sevdesk.de/favicon.ico',
        requiresPipedream: true,
        required: true,
    },
];

// Additional expense management integrations (shown in modal)
const additionalAccountingTools: Tool[] = [
    {
        name: 'Expensify', 
        appId: 'expensify', 
        iconUrl: 'https://www.expensify.com/favicon.ico', 
        requiresPipedream: true,
        required: false 
    },
];

// Optional integrations (non-accounting)
const optionalTools: Tool[] = [
    { name: 'Gmail', appId: 'gmail', icon: GoogleGmail, requiresPipedream: true, required: false },
    { name: 'Notion', appId: 'notion', icon: NotionIcon, requiresPipedream: true, required: false },
];


interface ChatMessage {
    id: string;
    role: 'assistant' | 'user';
    content: string;
    timestamp: Date;
}

const ANIMATION_CONFIG = {
    messageEnter: 'animate-in fade-in slide-in-from-bottom-2 duration-300',
    stepTransition: 'animate-in fade-in duration-400',
} as const;

interface OnboardingProps {
    checkout_data?: { type: string; options: any };
    checkout_plan?: string;
    checkout_error?: string;
}

// localStorage helper with user ID prefix
const getStorageKey = (key: string, userId: string) => `onboarding_${userId}_${key}`;
const getStorage = (key: string, userId: string) => localStorage.getItem(getStorageKey(key, userId));
const setStorage = (key: string, value: string, userId: string) => localStorage.setItem(getStorageKey(key, userId), value);
const removeStorage = (key: string, userId: string) => localStorage.removeItem(getStorageKey(key, userId));

export default function Onboarding({ checkout_data, checkout_plan, checkout_error }: OnboardingProps = {}) {
    const { auth, paddle, subscription, customer } = usePage<SharedData>().props;
    const userId = auth.user?.id?.toString() || '1';
    const paddleToken = (paddle as any)?.client_side_token;
    const isSubscribed = (subscription as any)?.states?.subscribed || false;
    const currentPlan = (customer as any)?.plan || null;

    // State management
    const [step, setStep] = useState(() => {
        const saved = getStorage('step', userId);
        return saved ? parseInt(saved, 10) : 1;
    });
    const [organizedContent, setOrganizedContent] = useState(() => getStorage('organized', userId) || '');
    const [connectingApp, setConnectingApp] = useState<string | null>(null);
    const [connectedApps, setConnectedApps] = useState<Set<string>>(new Set());
    const [pipedreamClient, setPipedreamClient] = useState<PipedreamClient | null>(null);
    const [isClientReady, setIsClientReady] = useState(false);
    const [isFetchingToken, setIsFetchingToken] = useState(false);
    const [selectedPlan, setSelectedPlan] = useState<string | null>(() => {
        if (currentPlan && currentPlan !== 'free') return currentPlan;
        return getStorage('selected_plan', userId) || null;
    });
    const [estimationContent, setEstimationContent] = useState<string>(() => getStorage('estimation', userId) || '');
    const [isLoadingEstimation, setIsLoadingEstimation] = useState(false);
    const [isChatComplete, setIsChatComplete] = useState(() => {
        const saved = getStorage('chat_complete', userId);
        return saved === 'true';
    });

    // Modal state for additional integrations
    const [showMoreIntegrationsModal, setShowMoreIntegrationsModal] = useState(false);

    // File upload state
    const [uploadedFile, setUploadedFile] = useState<File | null>(null);
    const [isUploading, setIsUploading] = useState(false);
    const [uploadStatus, setUploadStatus] = useState<'idle' | 'uploading' | 'converting' | 'storing' | 'analyzing' | 'uploaded' | 'error'>('idle');
    const [uploadProgress, setUploadProgress] = useState(0);
    const [uploadMessage, setUploadMessage] = useState('');
    const [analysisResult, setAnalysisResult] = useState<any>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Paddle checkout state
    const [showCheckout, setShowCheckout] = useState(false);
    const [isLoadingCheckout, setIsLoadingCheckout] = useState(false);
    const [isVerifyingPayment, setIsVerifyingPayment] = useState(false);
    const subscriptionStatus = subscription ? {
        subscribed: isSubscribed,
        current_plan: currentPlan,
        status: (subscription as any).states,
    } : null;

    // Handle checkout data from backend
    useEffect(() => {
        if (checkout_data && checkout_plan) {
            setShowCheckout(true);
            toast.success('Opening secure checkout...');
        }
    }, [checkout_data, checkout_plan]);

    useEffect(() => {
        if (checkout_error) {
            setIsLoadingCheckout(false);
            setSelectedPlan(null);
            toast.error(checkout_error);
        }
    }, [checkout_error]);

    // Track subscription status changes
    const prevSubscribedRef = useRef(isSubscribed);
    useEffect(() => {
        if (currentPlan && currentPlan !== 'free') {
            setSelectedPlan(currentPlan);
            setStorage('selected_plan', currentPlan, userId);
            if (isSubscribed && !prevSubscribedRef.current) {
                setIsVerifyingPayment(false);
                toast.success('🎉 Subscription activated! Welcome to Costrym!');
            }
            prevSubscribedRef.current = isSubscribed;
        }
    }, [currentPlan, isSubscribed, userId]);

    // Listen for live subscription status updates via Ably
    useEffect(() => {
        if (!auth.user?.id) {
            return;
        }

        // Get Echo instance from window (configured globally in app.tsx)
        const echo = (window as any).Echo;
        if (!echo) {
            return;
        }

        const channel = echo.private(`private-user.${auth.user.id}`);

        channel.listen('.subscription.status.updated', (data: any) => {
            const subscriptionData = data.subscription || {};
            
            // Update subscription status from broadcast
            if (subscriptionData.subscribed && subscriptionData.current_plan) {
                const newPlan = subscriptionData.current_plan;
                
                // Update local state
                setSelectedPlan(newPlan);
                setStorage('selected_plan', newPlan, userId);
                setIsVerifyingPayment(false);
                
                // Show success message
                if (!isSubscribed) {
                    toast.success('🎉 Subscription activated! Welcome to Costrym!');
                }
                
                // Reload subscription data to sync with backend
                router.reload({ only: ['subscription', 'customer'] });
            }
        });

        return () => {
            channel.stopListening('.subscription.status.updated');
            echo.leave(`private-user.${auth.user.id}`);
        };
    }, [auth.user?.id, userId, isSubscribed]);

    // Handle Paddle checkout completion - reduced polling since we have live updates
    useEffect(() => {
        let pollInterval: NodeJS.Timeout | null = null;
        let pollAttempts = 0;
        const maxPollAttempts = 5; // Reduced since we have live updates

        const checkSubscriptionStatus = () => {
            router.reload({ only: ['subscription', 'customer'] });
        };

        const handleCheckoutComplete = () => {
            toast.success('Payment completed! Verifying subscription...');
            setShowCheckout(false);
            setIsVerifyingPayment(true);
            pollAttempts = 0;
            
            // Immediate check
            setTimeout(() => checkSubscriptionStatus(), 1000);
            
            // Reduced polling - live updates will handle most cases
            pollInterval = setInterval(() => {
                pollAttempts++;
                checkSubscriptionStatus();
                if (pollAttempts >= maxPollAttempts) {
                    if (pollInterval) {
                        clearInterval(pollInterval);
                        pollInterval = null;
                        setIsVerifyingPayment(false);
                    }
                }
            }, 2000);
        };

        const handleCheckoutClosed = () => {
            setShowCheckout(false);
            if (selectedPlan) {
                setIsVerifyingPayment(true);
                setTimeout(() => checkSubscriptionStatus(), 1000);
            }
        };

        window.addEventListener('paddle-checkout-complete', handleCheckoutComplete);
        window.addEventListener('paddle-checkout-closed', handleCheckoutClosed);

        return () => {
            window.removeEventListener('paddle-checkout-complete', handleCheckoutComplete);
            window.removeEventListener('paddle-checkout-closed', handleCheckoutClosed);
            if (pollInterval) clearInterval(pollInterval);
        };
    }, [selectedPlan]);

    // Chat state
    const [chatMessages, setChatMessages] = useState<ChatMessage[]>(() => {
        const saved = getStorage('chat_messages', userId);
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                return parsed.map((msg: any) => ({ ...msg, timestamp: new Date(msg.timestamp) }));
            } catch {
                return [];
            }
        }
        return [];
    });
    const [currentInput, setCurrentInput] = useState('');
    const [isSendingMessage, setIsSendingMessage] = useState(false);
    const [aiUnderstanding, setAiUnderstanding] = useState(() => getStorage('understanding', userId) || '');
    const [showClearDialog, setShowClearDialog] = useState(false);
    const chatEndRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    const getCsrfToken = (): string => {
        const name = 'XSRF-TOKEN=';
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            cookie = cookie.trim();
            if (cookie.indexOf(name) === 0) {
                return decodeURIComponent(cookie.substring(name.length));
            }
        }
        return '';
    };

    const loadConnectedAccounts = async () => {
        try {
            const response = await fetch('/connect/accounts', {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.accounts) {
                    const appNames = new Set<string>(data.accounts.map((acc: any) => acc.app.toLowerCase()));
                    setConnectedApps(appNames);
                }
            }
        } catch (error) {}
    };

    // Initialize Pipedream client
    useEffect(() => {
        let cachedToken: string | null = null;
        let tokenPromise: Promise<{ token: string; expiresAt: Date }> | null = null;

        const fetchToken = async (): Promise<{ token: string; expiresAt: Date }> => {
            if (cachedToken && typeof cachedToken === 'string' && cachedToken.length > 0) {
                const tokenToReturn = String(cachedToken).trim();
                if (tokenToReturn && tokenToReturn.length > 0) {
                    return {
                        token: tokenToReturn,
                        expiresAt: new Date(Date.now() + 60 * 60 * 1000),
                    };
                }
                cachedToken = null;
            }

            if (tokenPromise) {
                return tokenPromise;
            }

            setIsFetchingToken(true);
            tokenPromise = (async () => {
                try {
                    const response = await fetch('/connect/token', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-XSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ error: 'Failed to get token' }));
                        throw new Error(errorData.error || `HTTP ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success && data.token) {
                        const token = String(data.token).trim();

                        if (!token || token === 'undefined' || token === 'null' || token.length === 0) {
                            throw new Error('Invalid token: token is empty or invalid');
                        }

                        cachedToken = token;
                        const expiresAt = data.expires_at ? new Date(data.expires_at) : new Date(Date.now() + 60 * 60 * 1000);

                        setIsFetchingToken(false);
                        return { token, expiresAt };
                    }

                    throw new Error(data.error || 'Failed to get token');
                } catch (error) {
                    tokenPromise = null;
                    cachedToken = null;
                    setIsFetchingToken(false);
                    throw error;
                }
            })();

            return tokenPromise;
        };

        const initializeClient = async () => {
            try {
                await fetchToken();

                const wrappedTokenCallback = async (): Promise<{ token: string; expiresAt: Date }> => {
                    const result = await fetchToken();
                    if (!result || !result.token || typeof result.token !== 'string' || result.token.length === 0) {
                        throw new Error('Invalid token from fetchToken');
                    }
                    return result;
                };

                const clientOptions: any = {
                    projectEnvironment: 'development',
                    tokenCallback: wrappedTokenCallback,
                };

                if (userId) {
                    clientOptions.externalUserId = userId;
                }

                const client = new PipedreamClient(clientOptions);

                setPipedreamClient(client);
                setIsClientReady(true);
            } catch (error) {
                setIsClientReady(false);
                const errorMessage = error instanceof Error ? error.message : 'Unknown error';
                toast.error(`Failed to initialize connection service: ${errorMessage}`);
            }
        };

        initializeClient();
        loadConnectedAccounts();
    }, [userId]);

    // Persist state to localStorage with user ID
        useEffect(() => {
            setStorage('step', step.toString(), userId);
        }, [step, userId]);

        useEffect(() => {
        if (organizedContent) {
            setStorage('organized', organizedContent, userId);
        }
    }, [organizedContent, userId]);

    useEffect(() => {
        if (chatMessages.length > 0) {
            setStorage('chat_messages', JSON.stringify(chatMessages), userId);
        }
    }, [chatMessages, userId]);

    useEffect(() => {
        if (aiUnderstanding) {
            setStorage('understanding', aiUnderstanding, userId);
        }
    }, [aiUnderstanding, userId]);

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [chatMessages]);

    useEffect(() => {
        if (step === 2 && chatMessages.length === 0) {
            const welcomeMessage: ChatMessage = {
                id: Date.now().toString(),
                role: 'assistant',
                content:
                    "Hi! I'm here to help you get started. Let's begin by learning about your company. What's the name of your company and what industry are you in?",
                timestamp: new Date(),
            };
            setChatMessages([welcomeMessage]);
        }
    }, [step]);

    useEffect(() => {
        if (step === 3 && !estimationContent && !isLoadingEstimation) {
            const cachedEstimation = getStorage('estimation', userId);
            if (cachedEstimation) {
                setEstimationContent(cachedEstimation);
                return;
            }

            // If not cached, fetch from API
            setIsLoadingEstimation(true);
            fetch(route('onboarding.estimation'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    understanding: aiUnderstanding,
                    organized_content: organizedContent,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success && data.content) {
                        setEstimationContent(data.content);
                        setStorage('estimation', data.content, userId);
                    }
                })
                .catch((error) => {
                    console.error('Failed to fetch estimation:', error);
                })
                .finally(() => {
                    setIsLoadingEstimation(false);
                });
        }
    }, [step, estimationContent, isLoadingEstimation, aiUnderstanding, organizedContent]);

    // Navigation handlers
    const handleStepChange = (newStep: number) => {
        setStep(newStep);
    };

    const handleGetStarted = () => {
        handleStepChange(2);
    };

    const handleBack = () => {
        if (step > 1) {
            handleStepChange(step - 1);
        }
    };

    const handleSendMessage = async () => {
        const trimmedInput = currentInput.trim();
        if (!trimmedInput || isSendingMessage) return;

        // Optimistically add user message to UI
        const userMessage: ChatMessage = {
            id: Date.now().toString(),
            role: 'user',
            content: trimmedInput,
            timestamp: new Date(),
        };

        setChatMessages((prev) => [...prev, userMessage]);
        setCurrentInput('');
        setIsSendingMessage(true);

        try {
            const response = await fetch(route('onboarding.chat'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    message: trimmedInput,
                    conversation_history: chatMessages.map((msg) => ({
                        role: msg.role,
                        content: msg.content,
                    })),
                    previous_understanding: aiUnderstanding || '',
                }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'Failed to process message' }));
                throw new Error(errorData.error || errorData.message || `Server error: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                const aiMessage: ChatMessage = {
                    id: (Date.now() + 1).toString(),
                    role: 'assistant',
                    content: data.response || data.message || 'I understand. Tell me more!',
                    timestamp: new Date(),
                };

                setChatMessages((prev) => [...prev, aiMessage]);

                // Update understanding if provided
                if (data.understanding) {
                    setAiUnderstanding(data.understanding);
                }

                if (data.complete) {
                    setIsChatComplete(true);
                    setStorage('chat_complete', 'true', userId);
                    
                    if (data.organized_content) {
                        setOrganizedContent(data.organized_content);
                    }

                    // Proceed to next step immediately when AI confirms completion
                    toast.success('Information gathered! Proceeding...');
                    setTimeout(() => {
                        handleStepChange(3);
                    }, 1000);
                }
            } else {
                throw new Error(data.error || data.message || 'Failed to get response');
            }
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Failed to send message. Please try again.');
            // Remove user message on error to allow retry
            setChatMessages((prev) => prev.filter((msg) => msg.id !== userMessage.id));
        } finally {
            setIsSendingMessage(false);
            inputRef.current?.focus();
        }
    };

    const completeOnboarding = () => {
        router.post(
            route('onboarding.complete'),
            {
                understanding: aiUnderstanding,
                organized_content: organizedContent,
            },
            {
                onSuccess: () => {
                    // toast.success('Onboarding completed!');
                },
                onError: () => {
                    toast.error('Failed to complete onboarding. Please try again.');
                },
            },
        );
    };

    const handleProceedToIntegrations = () => {
        if (isChatComplete) {
            handleStepChange(3);
        } else {
            toast.info('Please complete the conversation with the AI first');
        }
    };

    const handlePlanSelect = (plan: string) => {
        // Don't allow selection if already subscribed to this plan
        if (subscriptionStatus?.subscribed && subscriptionStatus?.current_plan === plan) {
            return;
        }

        // Optimistically update UI - this will disable other plan buttons
        setSelectedPlan(plan);
        setIsLoadingCheckout(true);

        // Save plan preference and create checkout session in one request (non-reloading)
        router.post(
            route('onboarding.select-plan'),
            { plan },
            {
                onSuccess: () => {
                    setIsLoadingCheckout(false);
                    // Checkout overlay will open automatically via useEffect watching checkout_data
                },
                onError: (errors) => {
                    setIsLoadingCheckout(false);
                    setSelectedPlan(null);
                    
                    const errorMessage =
                        errors?.error ||
                        errors?.message ||
                        errors?.checkout_error ||
                        'Failed to process plan selection. Please try again.';
                    
                    toast.error(errorMessage);
                },
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const handleClearClick = () => {
        setShowClearDialog(true);
    };

    const handleClearChat = () => {
        setChatMessages([]);
        setAiUnderstanding('');
        setCurrentInput('');
        setEstimationContent('');
        setIsChatComplete(false);
        removeStorage('chat_messages', userId);
        removeStorage('understanding', userId);
        removeStorage('estimation', userId);
        removeStorage('chat_complete', userId);

        // Re-initialize with welcome message
        const welcomeMessage: ChatMessage = {
            id: Date.now().toString(),
            role: 'assistant',
            content:
                "Hi! I'm here to help you get started. Let's begin by learning about your company. What's the name of your company and what industry are you in?",
            timestamp: new Date(),
        };
        setChatMessages([welcomeMessage]);
        setShowClearDialog(false);
        toast.success('Chat cleared successfully');
    };

    const handleFileUpload = async (file: File) => {
        // Validate file type
        const validExtensions = ['.csv', '.xlsx', '.xls'];
        const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();
        
        if (!validExtensions.includes(fileExtension)) {
            toast.error('Please upload a CSV or Excel file (.csv, .xlsx, .xls)');
            return;
        }

        // Validate file size (max 100MB)
        const maxSize = 100 * 1024 * 1024; // 100MB
        if (file.size > maxSize) {
            toast.error('File size must be less than 100MB');
            return;
        }

        setIsUploading(true);
        setUploadStatus('uploading');
        setUploadProgress(0);
        setUploadMessage('Uploading file...');
        setUploadedFile(file);

        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'financial_data');

            const response = await fetch(route('onboarding.upload-financial-data'), {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: formData,
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'Upload failed' }));
                throw new Error(errorData.error || errorData.message || 'Failed to upload file');
            }

            const data = await response.json();

            if (data.success) {
                // Start polling for progress updates
                const sessionId = data.session_id;
                
                // Poll for progress updates
                const pollProgress = async () => {
                    const maxAttempts = 120; // 2 minutes max (120 * 1 second)
                    let attempts = 0;
                    
                    const poll = async () => {
                        if (attempts >= maxAttempts) {
                            setUploadStatus('error');
                            setUploadMessage('Processing timed out. Please try again.');
                            setIsUploading(false);
                            return;
                        }

                        try {
                            const statusResponse = await fetch(route('onboarding.upload-financial-data.status', { sessionId }), {
                                method: 'GET',
                                headers: {
                                    'X-XSRF-TOKEN': getCsrfToken(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });

                            if (statusResponse.ok) {
                                const statusData = await statusResponse.json();
                                
                                if (statusData.success && statusData.progress) {
                                    const progress = statusData.progress;
                                    setUploadProgress(progress.progress || 0);
                                    setUploadMessage(progress.message || 'Processing...');
                                    
                                    // Map status to our state
                                    if (progress.status === 'uploading') {
                                        setUploadStatus('uploading');
                                    } else if (progress.status === 'converting') {
                                        setUploadStatus('converting');
                                    } else if (progress.status === 'storing') {
                                        setUploadStatus('storing');
                                    } else if (progress.status === 'analyzing') {
                                        setUploadStatus('analyzing');
                                    } else if (progress.status === 'completed') {
                                        setUploadStatus('uploaded');
                                        setUploadProgress(100);
                                        setAnalysisResult(progress.analysis);
                                        
                                        if (progress.analysis?.meets_requirement) {
                                            toast.success('✅ File analyzed! Your company meets the transaction requirements.');
                                        } else {
                                            toast.warning('⚠️ File analyzed. Your company may not meet the minimum transaction requirements ($1000+/month).');
                                        }
                                        
                                        setIsUploading(false);
                                        return; // Stop polling
                                    } else if (progress.status === 'error') {
                                        setUploadStatus('error');
                                        setUploadMessage(progress.message || 'An error occurred');
                                        setIsUploading(false);
                                        toast.error(progress.message || 'Processing failed');
                                        return; // Stop polling
                                    }
                                    
                                    // Continue polling if not completed
                                    if (progress.status !== 'completed' && progress.status !== 'error') {
                                        attempts++;
                                        setTimeout(poll, 1000); // Poll every second
                                    }
                                }
                            }
                        } catch (error) {
                            console.error('Error polling progress:', error);
                            attempts++;
                            if (attempts < maxAttempts) {
                                setTimeout(poll, 1000);
                            } else {
                                setUploadStatus('error');
                                setUploadMessage('Failed to get progress updates');
                                setIsUploading(false);
                            }
                        }
                    };
                    
                    poll();
                };
                
                pollProgress();
            } else {
                throw new Error(data.error || data.message || 'Upload failed');
            }
        } catch (error) {
            setUploadStatus('error');
            setUploadProgress(0);
            setUploadMessage('');
            setUploadedFile(null);
            toast.error(error instanceof Error ? error.message : 'Failed to upload file. Please try again.');
            setIsUploading(false);
        }
    };

    const handleToolClick = async (tool: Tool) => {
        if (!tool.requiresPipedream) {
            return;
        }

        if (!isClientReady || !pipedreamClient) {
            toast.info('Connection service is initializing. Please wait a moment and try again.');
            return;
        }

        const appId = tool.appId.toLowerCase();
        setConnectingApp(appId);

        try {
            (pipedreamClient as any).connectAccount({
                app: appId,
                onSuccess: async (account: any) => {
                    if (account?.id) {
                        try {
                            const saveResponse = await fetch(`/connect/${appId}/save`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    'X-XSRF-TOKEN': getCsrfToken(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({
                                    connection_id: account.id,
                                    external_user_id: account.external_user_id || userId,
                                    metadata: account,
                                }),
                            });

                            const saveData = await saveResponse.json();
                            if (saveData.success) {
                                setConnectedApps((prev) => new Set(prev).add(appId));
                                setConnectingApp(null);
                                toast.success(`${tool.name} connected successfully!`);
                                loadConnectedAccounts();
                            } else {
                                throw new Error(saveData.error || 'Failed to save connection');
                            }
                        } catch (saveError) {
                            setConnectingApp(null);
                            toast.error('Connected but failed to save. Please refresh the page and try again.');
                        }
                    }
                },
                onError: (err: Error) => {
                    setConnectingApp(null);
                    toast.error(`Failed to connect ${tool.name}: ${err.message || 'Please try again.'}`);
                },
            });
        } catch (error) {
            setConnectingApp(null);
            toast.error(`Failed to start connection for ${tool.name}. Please try again.`);
        }
    };

    const getTitle = () => {
        switch (step) {
            case 1:
                return "Welcome! Let's get started";
            case 2:
                return 'Tell us about your company';
            case 3:
                return 'What Costrym Can Do For You';
            case 4:
                return 'Choose Your Plan';
            case 5:
                return 'Connect Essential Accounting Tools';
            case 6:
                return 'Connect Optional Integrations';
            default:
                return 'Onboarding';
        }
    };

    /**
     * Returns appropriate description for current onboarding step.
     */
    const getDescription = () => {
        switch (step) {
            case 1:
                return 'Complete a few quick steps to set up your account';
            case 2:
                return 'Help us personalize your experience';
            case 3:
                return 'Discover how we can optimize your costs and save money';
            case 4:
                return 'Select the perfect plan for your business needs';
            case 5:
                return 'Connect at least one essential accounting system to analyze your expenses.';
            case 6:
                return 'Connect additional tools to enhance your experience (optional).';
            default:
                return '';
        }
    };

    return (
        <>
            <Head title={getTitle()} />

            {/* Step 1: Welcome Screen */}
            <div className={`${step !== 1 ? 'hidden' : ''} ${ANIMATION_CONFIG.stepTransition}`}>
                {step === 1 && (
                    <AuthSimpleLayout title={getTitle()} description={getDescription()}>
                        <div className="space-y-6">
                            <div className="mx-auto flex w-full max-w-sm items-center justify-center pt-4">
                                <Button className="mx-auto" size="sm" onClick={handleGetStarted}>
                                    Get Started
                                </Button>
                            </div>
                        </div>
                    </AuthSimpleLayout>
                )}
            </div>

            {/* Step 2: Chat Onboarding */}
            <div className={`${step !== 2 ? 'hidden' : ''} ${ANIMATION_CONFIG.stepTransition}`}>
                {step === 2 && (
                    <AuthSimpleLayout title={getTitle()} description={getDescription()}>
                        <div className="mx-auto w-full max-w-3xl px-1 sm:px-4">
                            <div className="flex h-full w-full flex-col gap-3 sm:gap-4 lg:flex-row lg:gap-6">
                                {/* Left Section: Chat Messages - Fixed height and scrollable */}
                                <div className="flex flex-col rounded-lg lg:flex-1">
                                    <div className="flex h-[350px] flex-col sm:h-[450px] lg:h-[600px]">
                                        {/* Chat Messages Container - Scrollable */}
                                        <div className="scrollbar-thin min-h-0 flex-1 space-y-2 overflow-y-auto p-2 sm:space-y-3 sm:p-4">
                                            {chatMessages.map((message) => (
                                                <div
                                                    key={message.id}
                                                    className={`flex gap-1.5 sm:gap-3 ${message.role === 'user' ? 'justify-end' : 'justify-start'} ${ANIMATION_CONFIG.messageEnter}`}
                                                >
                                                    <div
                                                        className={`max-w-[85%] rounded-lg px-2.5 py-1.5 text-xs break-words sm:max-w-[80%] sm:px-4 sm:py-2 sm:text-sm ${
                                                            message.role === 'user'
                                                                ? 'bg-primary text-primary-foreground'
                                                                : 'bg-muted text-foreground'
                                                        }`}
                                                    >
                                                        <p className="leading-relaxed whitespace-pre-wrap">{message.content}</p>
                                                    </div>
                                                </div>
                                            ))}
                                            {isSendingMessage && (
                                                <div className={`flex justify-start gap-1.5 sm:gap-3 ${ANIMATION_CONFIG.messageEnter}`}>
                                                    <div className="rounded-lg bg-muted px-2.5 py-1.5 sm:px-4 sm:py-2">
                                                        <LoaderCircle className="size-3.5 animate-spin text-muted-foreground sm:size-4" />
                                                    </div>
                                                </div>
                                            )}
                                            <div ref={chatEndRef} />
                                        </div>

                                        {/* Chat Input - Fixed at bottom */}
                                        <div className="flex-shrink-0 p-2 sm:p-4">
                                            <div className="w-full">
                                                <InputGroup className="border-accent bg-muted/70 px-1.5 shadow-sm ring-1 ring-muted-foreground/5 transition focus-within:ring-ring sm:px-2">
                                                    <InputGroupTextarea
                                                        ref={(el) => {
                                                            if (inputRef && typeof inputRef !== 'function') {
                                                                // @ts-ignore
                                                                inputRef.current = el;
                                                            }
                                                        }}
                                                        value={currentInput}
                                                        onChange={(e) => setCurrentInput(e.target.value)}
                                                        onKeyDown={(e) => {
                                                            if (e.key === 'Enter' && !e.shiftKey) {
                                                                e.preventDefault();
                                                                handleSendMessage();
                                                            }
                                                        }}
                                                        placeholder="Type your message…"
                                                        disabled={isSendingMessage}
                                                        rows={1}
                                                        autoFocus
                                                        className="max-h-32 min-h-[34px] flex-1 resize-none overflow-auto border-0 bg-transparent px-1.5 py-1.5 text-xs leading-relaxed focus-visible:ring-0 sm:max-h-40 sm:min-h-[38px] sm:px-3 sm:py-2 sm:text-base"
                                                        style={{
                                                            resize: 'none',
                                                        }}
                                                        onInput={(e) => {
                                                            // Auto-expand textarea based on content
                                                            const target = e.target as HTMLTextAreaElement;
                                                            target.style.height = 'auto';
                                                            target.style.height = Math.min(target.scrollHeight, 180) + 'px';
                                                        }}
                                                    />
                                                    <InputGroupButton
                                                        type="button"
                                                        size="sm"
                                                        onClick={handleSendMessage}
                                                        disabled={!currentInput.trim() || isSendingMessage}
                                                        variant="ghost"
                                                        className="ml-0.5 h-8 w-8 flex-shrink-0 rounded-full sm:ml-2 sm:h-9 sm:w-9"
                                                        aria-label="Send message"
                                                    >
                                                        {isSendingMessage ? (
                                                            <LoaderCircle className="size-3.5 animate-spin sm:size-4" />
                                                        ) : (
                                                            <ArrowUpIcon className="size-3.5 sm:size-4" />
                                                        )}
                                                    </InputGroupButton>
                                                </InputGroup>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Right Section: AI Understanding Panel - Responsive width */}
                                <div className="flex w-full flex-col rounded-lg border bg-primary-foreground/50 lg:w-80 lg:flex-shrink-0">
                                    <div className="flex h-[300px] flex-col sm:h-[450px] lg:h-[600px]">
                                        {/* Header */}
                                        <div className="flex flex-shrink-0 items-center justify-between gap-1 p-2 sm:p-4">
                                            <h3 className="line-clamp-1 flex items-center gap-1.5 text-xs font-medium text-foreground sm:gap-2 sm:text-sm">
                                                <span className="">Noted</span>
                                            </h3>
                                            {(chatMessages.length > 1 || aiUnderstanding) && (
                                                <>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={handleClearClick}
                                                        className="h-6 w-6 flex-shrink-0 p-0 sm:h-7 sm:w-7"
                                                        title="Clear chat and understanding"
                                                    >
                                                        <Trash2 className="size-3 text-muted-foreground transition-colors hover:text-destructive sm:size-3.5" />
                                                    </Button>
                                                    <AlertDialog open={showClearDialog} onOpenChange={setShowClearDialog}>
                                                        <AlertDialogContent className="w-[90%] sm:w-full">
                                                            <AlertDialogHeader>
                                                                <AlertDialogTitle>Clear Chat History?</AlertDialogTitle>
                                                                <AlertDialogDescription>
                                                                    This will permanently delete all chat messages and the understanding data. This
                                                                    action cannot be undone.
                                                                </AlertDialogDescription>
                                                            </AlertDialogHeader>
                                                            <AlertDialogFooter className="flex-col-reverse gap-2 sm:flex-row">
                                                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                                <AlertDialogAction
                                                                    onClick={handleClearChat}
                                                                    className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                                >
                                                                    Clear Chat
                                                                </AlertDialogAction>
                                                            </AlertDialogFooter>
                                                        </AlertDialogContent>
                                                    </AlertDialog>
                                                </>
                                            )}
                                        </div>
                                        {/* Content - Scrollable */}
                                        <div className="scrollbar-thin min-h-0 flex-1 overflow-y-auto p-2 sm:p-4">
                                            {aiUnderstanding ? (
                                                <div className="space-y-2 text-xs sm:space-y-3 sm:text-sm">
                                                    <div className="prose prose-sm dark:prose-invert max-w-none">
                                                        <p className="leading-relaxed whitespace-pre-wrap text-muted-foreground">{aiUnderstanding}</p>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="flex h-full items-center justify-center p-2 text-center text-xs text-muted-foreground sm:p-4 sm:text-sm">
                                                    <p>I'll start understanding you as we chat...</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Navigation - Optimized for mobile */}
                            <div className="flex w-full justify-between gap-2 pt-3 sm:gap-3 sm:pt-4">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleBack}
                                    disabled={isSendingMessage}
                                    className="h-8 flex-1 gap-1 text-xs sm:h-9 sm:flex-none sm:gap-2 sm:text-sm"
                                >
                                    <CaretLeftIcon className="size-3.5 flex-shrink-0 sm:size-4" />
                                    <span className="hidden sm:inline">Back</span>
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={handleProceedToIntegrations}
                                    disabled={isSendingMessage || !isChatComplete}
                                    variant="outline"
                                    className="h-8 flex-1 gap-1 text-xs sm:h-9 sm:flex-none sm:gap-2 sm:text-sm"
                                    title={!isChatComplete ? 'Please complete the conversation with the AI first' : ''}
                                >
                                    {isSendingMessage ? (
                                        <>
                                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                            <span className="hidden sm:inline">Processing...</span>
                                        </>
                                    ) : !isChatComplete ? (
                                        <>
                                            <span className="hidden sm:inline">Waiting for AI...</span>
                                            <span className="sm:hidden">Waiting...</span>
                                        </>
                                    ) : (
                                        <>
                                            <span className="hidden sm:inline">Continue</span>
                                            <span className="sm:hidden">Next</span>
                                            <CaretRightIcon className="size-3.5 flex-shrink-0 sm:size-4" />
                                        </>
                                    )}
                                </Button>
                            </div>
                        </div>
                    </AuthSimpleLayout>
                )}
            </div>

            {/* Step 3: Value Proposition - AI Generated */}
            {step === 3 && (
                <AuthSimpleLayout title={getTitle()} description={getDescription()}>
                    <div className="mx-auto w-full max-w-4xl space-y-8 px-2 sm:px-4">
                        <div className="p-6 sm:p-8 lg:p-12">
                            {isLoadingEstimation ? (
                                <div className="flex flex-col items-center justify-center space-y-4 py-16">
                                    <LoaderCircle className="size-10 animate-spin text-primary" />
                                    <p className="text-base text-muted-foreground">Analyzing your business and generating personalized insights...</p>
                                </div>
                            ) : estimationContent ? (
                                <div className="mx-auto max-w-3xl">
                                    <div className="space-y-5 sm:space-y-6">
                                        {estimationContent
                                            .split('\n')
                                            .filter((line) => line.trim())
                                            .map((line, index) => (
                                                <p
                                                    key={index}
                                                    className="text-center text-lg leading-relaxed font-normal text-foreground sm:text-xl lg:text-xl"
                                                >
                                                    {line.trim()}
                                                </p>
                                            ))}
                                    </div>
                                </div>
                            ) : (
                                <div className="py-16 text-center text-muted-foreground">
                                    <p className="text-base">Unable to generate estimation. Please try again. or you can skip this. </p>
                                </div>
                            )}
                        </div>

                        {/* Navigation */}
                        <div className="flex w-full justify-between gap-2 pt-4 sm:gap-3">
                            <Button variant="ghost" size="sm" onClick={handleBack} className="flex-1 sm:flex-none">
                                <CaretLeftIcon className="size-4" />
                                <span className="hidden sm:inline">Back</span>
                            </Button>
                            <Button size="sm" onClick={() => handleStepChange(4)} variant="default" className="flex-1 sm:flex-none">
                                <span className="hidden sm:inline">Continue to Plans</span>
                                <span className="sm:hidden">Continue</span>
                                <CaretRightIcon className="size-4" />
                            </Button>
                        </div>
                    </div>
                </AuthSimpleLayout>
            )}

            {/* Step 4: Pricing Plans */}
            {step === 4 && (
                <AuthSimpleLayout title={getTitle()} description={getDescription()}>
                    {isVerifyingPayment ? (
                        <div className="mx-auto w-full max-w-2xl space-y-6 px-2 sm:px-4">
                            <div className="flex flex-col items-center justify-center rounded-xl border bg-primary-foreground p-8 sm:p-12">
                                <LoaderCircle className="mb-4 h-12 w-12 animate-spin text-primary" />
                                <h3 className="mb-2 text-lg font-semibold sm:text-xl">Verifying Your Payment</h3>
                                <p className="mb-4 text-center text-sm text-muted-foreground sm:text-base">
                                    We're confirming your subscription. This usually takes just a few seconds...
                                </p>
                                <div className="mt-4 flex items-center gap-2 text-xs text-muted-foreground sm:text-sm">
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                    <span>Checking subscription status...</span>
                                </div>
                            </div>
                        </div>
                    ) : (
                    <div className="mx-auto w-full max-w-6xl space-y-8 px-2 sm:px-4">
                        <div className="grid gap-4 sm:gap-6 lg:grid-cols-3">
                            {/* Startup Monthly Plan */}
                            <div
                                className={`group relative rounded-xl border bg-primary-foreground p-5 transition-all duration-300 sm:p-6 ${selectedPlan === 'startup-monthly' ? 'scale-105 border-primary shadow-lg shadow-primary/20' : 'border-border hover:shadow-sm'}`}
                            >
                                <div className="absolute -top-3 left-4 rounded-full bg-accent px-3 py-1.5 text-xs font-bold text-primary ">
                                    62% OFF First 2 Months
                                </div>
                                <div className="space-y-4 pt-4">
                                    <div>
                                        <h3 className="text-lg font-normal font-spirax sm:text-3xl">Startup</h3>
                                        <p className="text-xs text-muted-foreground sm:text-sm">Monthly plan</p>
                                    </div>

                                    <div className="space-y-2 rounded-lg bg-accent p-3 ">
                                        <div className="flex items-baseline gap-1">
                                            <span className="text-2xl text-muted-foreground line-through opacity-60">$79.99</span>
                                            <span className="text-3xl font-bold text-primary sm:text-4xl ">$29.99</span>
                                            <span className="text-sm text-muted-foreground">/mo</span>
                                        </div>
                                        <p className="text-xs font-bold text-primary">Then $79.99/mo • Limited time offer</p>
                                    </div>

                                    <ul className="space-y-2.5 text-xs sm:text-sm">
                                        <li className="flex items-start gap-2">
                                            <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                            <span>Save costs from day one—guaranteed</span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                            <span>If we don’t save you at least $100 in your first month, you aren’t charged for that month</span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                            <span>Right for companies with $1,000–$50,000/month in expenses</span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                            <span>No monthly subscription—only pay for months we deliver real savings</span>
                                        </li>
                                    </ul>

                                    <Button
                                        className="mt-4 w-full"
                                        variant={selectedPlan === 'startup-monthly' ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => handlePlanSelect('startup-monthly')}
                                        disabled={
                                            isLoadingCheckout || 
                                            (subscriptionStatus?.subscribed && subscriptionStatus?.current_plan === 'startup-monthly') ||
                                            (selectedPlan !== null && selectedPlan !== 'startup-monthly')
                                        }
                                    >
                                        {isLoadingCheckout && selectedPlan === 'startup-monthly' ? (
                                            <>
                                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                                Loading...
                                            </>
                                        ) : subscriptionStatus?.subscribed && subscriptionStatus?.current_plan === 'startup-monthly' ? (
                                            'Current Plan'
                                        ) : selectedPlan === 'startup-monthly' ? (
                                            'Selected'
                                        ) : (
                                            'Select'
                                        )}
                                    </Button>
                                </div>
                            </div>

                            {/* Startup Annual Plan */}
                            <div
                                className={`group relative rounded-xl border bg-primary-foreground p-5 transition-all duration-300 sm:p-6 ${selectedPlan === 'startup-annual' ? 'scale-105 border-primary shadow-lg shadow-primary/20' : 'border-border hover:shadow-sm'}`}
                            >
                                <div className="absolute -top-3 left-4 rounded-full bg-accent px-3 py-1.5 text-xs font-bold text-primary ">
                                    3 Months Discount
                                </div>
                                <div className="space-y-4 pt-4">
                                    <div>
                                        <h3 className="text-lg font-normal font-spirax sm:text-3xl">Startup</h3>
                                        <p className="text-xs text-muted-foreground sm:text-sm">Annual plan</p>
                                    </div>

                                    <div className="space-y-2 rounded-lg bg-accent p-3 ">
                                        <div className="flex items-baseline gap-1">
                                            <span className="text-2xl text-muted-foreground line-through opacity-60">$960</span>
                                            <span className="text-3xl font-bold text-primary sm:text-4xl ">$500</span>
                                            <span className="text-sm text-muted-foreground">/yr</span>
                                        </div>
                                        <p className="text-xs font-bold text-primary">Save 52% vs monthly • Best Value</p>
                                    </div>

                                    <ul className="space-y-2.5 text-xs sm:text-sm">
                                        <li className="flex items-start gap-2">
                                            <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                            <span>All features included in the Monthly plan</span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                            <span>3 months free—our best value plan</span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                            <span>Quarterly strategy sessions with a human cost expert (over $800M in savings delivered across industries)</span>
                                        </li>
                                    </ul>

                                    <div className="absolute inset-x-0 bottom-0 left-0 p-5">
                                        <Button
                                            className="w-full"
                                            variant={selectedPlan === 'startup-annual' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => handlePlanSelect('startup-annual')}
                                            disabled={
                                                isLoadingCheckout || 
                                                (subscriptionStatus?.subscribed && subscriptionStatus?.current_plan === 'startup-annual') ||
                                                (selectedPlan !== null && selectedPlan !== 'startup-annual')
                                            }
                                        >
                                            {isLoadingCheckout && selectedPlan === 'startup-annual' ? (
                                                <>
                                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                                    Loading...
                                                </>
                                            ) : subscriptionStatus?.subscribed && subscriptionStatus?.current_plan === 'startup-annual' ? (
                                                'Current Plan'
                                            ) : selectedPlan === 'startup-annual' ? (
                                                'Selected'
                                            ) : (
                                                'Select'
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            {/* Enterprise Annual Plan */}
                            <div
                                    className={`group relative rounded-xl border bg-primary-foreground p-5 transition-all duration-300 sm:p-6 ${selectedPlan === 'enterprise-annual' ? 'scale-105 border-primary shadow-lg shadow-primary/20' : 'border-border hover:shadow-sm'}`}
                            >
                                {selectedPlan === 'enterprise-annual' && (
                                    <div className="absolute -top-3 right-4 rounded-full bg-accent px-3 py-1.5 text-xs font-bold text-primary ">
                                        SAVE 43% NOW
                                    </div>
                                )}
                                {!selectedPlan && (
                                    <div className="absolute -top-3 right-4 rounded-full bg-accent px-3 py-1.5 text-xs font-bold text-primary ">
                                        SAVE 43%
                                    </div>
                                )}
                                <div className="space-y-4 pt-4">
                                    <div>
                                        <h3 className="text-lg font-normal font-spirax sm:text-3xl">Enterprise</h3>
                                        <p className="text-xs text-muted-foreground sm:text-sm">Annual plan</p>
                                    </div>

                                    <div className="space-y-2 rounded-lg bg-accent p-3 ">
                                        <div className="flex items-baseline gap-1">
                                            <span className="text-2xl text-muted-foreground line-through opacity-60">$7,000</span>
                                            <span className="text-3xl font-bold text-primary sm:text-4xl ">$3,999</span>
                                            <span className="text-sm text-muted-foreground">/yr</span>
                                        </div>
                                        <p className="text-xs font-bold text-primary">Save $3,001/year • Most Popular</p>
                                    </div>

                                    <ul className="space-y-2.5 text-xs sm:text-sm">
                                        <li className="flex items-start gap-2">
                                            <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                            <span>
                                                Begin saving thousands from day one — guaranteed.
                                            </span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                            <span>
                                                Receive monthly cost audits led by seasoned experts.
                                            </span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                            <span>
                                                Unlock expected savings of <span className="font-semibold">$1,000–$10,000</span> every month.
                                            </span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <CheckIcon className="mt-0.5 size-4 flex-shrink-0 text-primary" />
                                            <span>
                                                Ideal for companies with $50,000+ in monthly expenses.
                                            </span>
                                        </li>
                                    </ul>

                                    <Button
                                        className="mt-4 w-full"
                                        variant={selectedPlan === 'enterprise-annual' ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => handlePlanSelect('enterprise-annual')}
                                        disabled={
                                            isLoadingCheckout || 
                                            (subscriptionStatus?.subscribed && subscriptionStatus?.current_plan === 'enterprise-annual') ||
                                            (selectedPlan !== null && selectedPlan !== 'enterprise-annual')
                                        }
                                    >
                                        {isLoadingCheckout && selectedPlan === 'enterprise-annual' ? (
                                            <>
                                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                                Loading...
                                            </>
                                        ) : subscriptionStatus?.subscribed && subscriptionStatus?.current_plan === 'enterprise-annual' ? (
                                            'Current Plan'
                                        ) : selectedPlan === 'enterprise-annual' ? (
                                            'Selected'
                                        ) : (
                                            'Select'
                                        )}
                                    </Button>
                                </div>
                            </div>
                        </div> 

                        {/* Navigation */}
                        <div className="flex w-full justify-between gap-2 pt-4 sm:gap-3">
                            <Button variant="ghost" size="sm" onClick={handleBack} className="flex-1 sm:flex-none">
                                <CaretLeftIcon className="size-4" />
                                <span className="hidden sm:inline">Back</span>
                            </Button>
                            {isSubscribed ? (
                                <Button 
                                    size="sm" 
                                    onClick={() => handleStepChange(5)} 
                                    variant="default" 
                                    className="flex-1 sm:flex-none"
                                >
                                    <span className="hidden sm:inline">Continue to Integrations</span>
                                    <span className="sm:hidden">Continue</span>
                                    <CaretRightIcon className="size-4" />
                                </Button>
                            ) : selectedPlan ? (
                                <Button 
                                    size="sm" 
                                    onClick={() => handleStepChange(5)} 
                                    variant="outline" 
                                    className="flex-1 sm:flex-none"
                                    disabled={isLoadingCheckout}
                                >
                                    {isLoadingCheckout ? (
                                        <>
                                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                            <span className="hidden sm:inline">Processing...</span>
                                            <span className="sm:hidden">Processing</span>
                                        </>
                                    ) : (
                                        <>
                                            <span className="hidden sm:inline">Continue (Checkout Required)</span>
                                            <span className="sm:hidden">Continue</span>
                                            <CaretRightIcon className="size-4" />
                                        </>
                                    )}
                                </Button>
                            ) : (
                                <div className="flex flex-1 items-center justify-center text-center text-sm text-muted-foreground">
                                    Please select a plan
                                </div>
                            )}
                        </div>
                    </div>
                    )}
                </AuthSimpleLayout>
            )}

            {/* Step 5: Essential Integrations */}
            <div className={`${step !== 5 ? 'hidden' : ''} ${ANIMATION_CONFIG.stepTransition}`}>
                {step === 5 && (
                    <AuthSimpleLayout title={getTitle()} description={getDescription()}>
                        <div className="mx-auto w-full max-w-4xl space-y-6 px-2 sm:px-4">
                            {isFetchingToken && (
                                <div className="flex animate-pulse items-center justify-center gap-2 py-2 text-sm text-muted-foreground">
                                    <LoaderCircle className="size-4 animate-spin" />
                                    <span>Wait a moment...</span>
                                </div>
                            )}

                            {/* Essential Integrations Section */}
                            <div className="space-y-4">


                                <div className="flex flex-wrap justify-center gap-2 sm:gap-3">
                                    {essentialTools.map((tool) => {
                                        const appId = tool.appId.toLowerCase();
                                        const isConnecting = connectingApp === appId;
                                        const isConnected = connectedApps.has(appId);

                                        return (
                                            <Button
                                                key={tool.name}
                                                variant={isConnected ? 'default' : 'outline'}
                                                className={`relative flex items-center gap-2 px-3 py-2 transition-all duration-200 hover:bg-accent hover:shadow-sm active:scale-95 sm:px-4 ${
                                                    tool.required && !isConnected
                                                        ? 'border-2 border-primary/50 bg-primary/5'
                                                        : ''
                                                }`}
                                                onClick={() => handleToolClick(tool)}
                                                disabled={isConnecting || isConnected || !isClientReady || isFetchingToken}
                                            >
                                                {isConnecting ? (
                                                    <LoaderCircle className="size-4 flex-shrink-0 animate-spin sm:size-5" />
                                                ) : isConnected ? (
                                                    <CheckIcon className="size-4 flex-shrink-0 text-green-600 sm:size-5 dark:text-green-400" />
                                                ) : tool.iconUrl ? (
                                                    <img
                                                        src={tool.iconUrl || '/placeholder.svg'}
                                                        alt={tool.name}
                                                        className="size-4 flex-shrink-0 object-contain sm:size-5"
                                                    />
                                                ) : tool.icon ? (
                                                    (() => {
                                                        const IconComponent = tool.icon;
                                                        return <IconComponent className="size-4 flex-shrink-0 sm:size-5" />;
                                                    })()
                                                ) : null}
                                                <span className="line-clamp-1 text-xs font-medium sm:text-sm">
                                                    {isConnecting ? 'Connecting...' : isConnected ? tool.name : tool.name}
                                                </span>
                                            </Button>
                                        );
                                    })}
                                    {/* More Button */}
                                    <Button
                                        variant="outline"
                                        className="flex items-center gap-2 px-3 py-2 transition-all duration-200 hover:bg-accent hover:shadow-sm active:scale-95 sm:px-4 border-dashed"
                                        onClick={() => setShowMoreIntegrationsModal(true)}
                                        disabled={!isClientReady || isFetchingToken}
                                    >
                                        <span className="line-clamp-1 text-xs font-medium sm:text-sm">More...</span>
                                    </Button>
                                </div>
                                <div className="relative"> 
                                    <div className="relative flex justify-center text-xs uppercase">
                                        <span className=" px-2 text-muted-foreground">Or Upload Financial Data</span>
                                    </div>
                                </div>
                                {/* File Upload Option */}
                                <div className="rounded-lg mx-auto w-full max-w-xl border-2 border-dashed border-primary/30 bg-primary/5 p-6 text-center">
                                    <div className="flex flex-col items-center gap-3">
                                        <div className="flex size-12 items-center justify-center rounded-full bg-primary/10">
                                            <FileCsvIcon className="size-6 text-primary" />
                                        </div>
                                        <div className="space-y-1">
                                            <h3 className="text-sm font-semibold">Upload Financial Data</h3>
                                            <p className="text-xs text-muted-foreground">
                                                Upload your Excel or CSV file with financial data. Our team will review and approve it.
                                            </p>
                                        </div>
                                        <div className="flex flex-col items-center gap-2 sm:flex-row">
                                            <input
                                                ref={fileInputRef}
                                                type="file"
                                                accept=".csv,.xlsx,.xls"
                                                className="hidden"
                                                onChange={(e) => {
                                                    const file = e.target.files?.[0];
                                                    if (file) {
                                                        handleFileUpload(file);
                                                    }
                                                }}
                                            />
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => fileInputRef.current?.click()}
                                                disabled={isUploading || uploadStatus === 'uploaded'}
                                                className="gap-2"
                                            >
                                                {isUploading ? (
                                                    <>
                                                        <LoaderCircle className="size-4 animate-spin" />
                                                        {uploadMessage || 'Processing...'}
                                                    </>
                                                ) : uploadStatus === 'uploaded' ? (
                                                    <>
                                                        <CheckIcon className="size-4 text-green-600" />
                                                        Analysis Complete
                                                    </>
                                                ) : (
                                                    <>
                                                        <Upload className="size-4" />
                                                        Choose File
                                                    </>
                                                )}
                                            </Button>
                                            {uploadedFile && (
                                                <span className="text-xs text-muted-foreground">
                                                    {uploadedFile.name}
                                                </span>
                                            )}
                                        </div>
                                        
                                        {/* Progress Bar */}
                                        {isUploading && uploadProgress > 0 && (
                                            <div className="w-full space-y-1">
                                                <div className="flex items-center justify-between text-xs text-muted-foreground">
                                                    <span>{uploadMessage}</span>
                                                    <span>{uploadProgress}%</span>
                                                </div>
                                                <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full bg-primary transition-all duration-300"
                                                        style={{ width: `${uploadProgress}%` }}
                                                    />
                                                </div>
                                            </div>
                                        )}
                                        
                                        {/* Analysis Results */}
                                        {uploadStatus === 'uploaded' && analysisResult && (
                                            <div className={`mt-2 rounded-lg border p-3 ${
                                                analysisResult.meets_requirement 
                                                    ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' 
                                                    : 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800'
                                            }`}>
                                                <p className={`text-xs font-medium ${
                                                    analysisResult.meets_requirement 
                                                        ? 'text-green-800 dark:text-green-200' 
                                                        : 'text-amber-800 dark:text-amber-200'
                                                }`}>
                                                    {analysisResult.meets_requirement 
                                                        ? '✓ Analysis Complete - Requirements Met' 
                                                        : '⚠ Analysis Complete - Requirements Not Met'}
                                                </p>
                                                <p className={`mt-1 text-xs ${
                                                    analysisResult.meets_requirement 
                                                        ? 'text-green-700 dark:text-green-300' 
                                                        : 'text-amber-700 dark:text-amber-300'
                                                }`}>
                                                    {analysisResult.analysis_summary || 'Your financial data has been analyzed.'}
                                                </p>
                                                {analysisResult.monthly_transaction_amount && (
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        Monthly Transaction Amount: ${analysisResult.monthly_transaction_amount.toLocaleString()}
                                                    </p>
                                                )}
                                            </div>
                                        )}
                                        
                                        {/* Error State */}
                                        {uploadStatus === 'error' && (
                                            <div className="mt-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                                                <p className="text-xs font-medium text-red-800 dark:text-red-200">
                                                    ✗ Processing Failed
                                                </p>
                                                <p className="mt-1 text-xs text-red-700 dark:text-red-300">
                                                    {uploadMessage || 'An error occurred while processing your file. Please try again.'}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Connection Status */}
                            {(connectedApps.size > 0 || uploadStatus === 'uploaded') && (
                                <div className="rounded-lg border bg-primary-foreground max-w-xl mx-auto p-4 text-center">
                                    <p className="text-sm font-medium">
                                        {connectedApps.size} {connectedApps.size === 1 ? 'integration' : 'integrations'} connected
                                        {uploadStatus === 'uploaded' && ' • File uploaded'}
                                    </p>
                                    {(() => {
                                        const hasEssentialIntegration = essentialTools.some((tool) => connectedApps.has(tool.appId.toLowerCase()));
                                        const fileMeetsRequirement = uploadStatus === 'uploaded' && analysisResult?.meets_requirement === true;
                                        const canProceed = hasEssentialIntegration || fileMeetsRequirement;
                                        
                                        if (canProceed) {
                                            return (
                                                <p className="mt-1 text-xs text-green-600 dark:text-green-400">
                                                    ✓ Ready to continue
                                                </p>
                                            );
                                        } else if (uploadStatus === 'uploaded' && analysisResult?.meets_requirement === false) {
                                            return (
                                                <p className="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                                    ⚠ File uploaded but does not meet minimum transaction requirements ($1000+/month). Please connect an integration or contact support.
                                                </p>
                                            );
                                        } else {
                                            return (
                                                <p className="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                                    ⚠ Please connect at least one essential integration or upload a file
                                                </p>
                                            );
                                        }
                                    })()}
                                </div>
                            )}

                            {/* Navigation */}
                            <div className="flex w-full justify-between gap-2 pt-4 sm:gap-3">
                                <Button variant="ghost" size="sm" onClick={handleBack} className="flex-1 sm:flex-none">
                                    <CaretLeftIcon className="size-4" />
                                    <span className="hidden sm:inline">Back</span>
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={() => handleStepChange(6)}
                                    variant="default"
                                    className="flex-1 sm:flex-none"
                                    disabled={
                                        (() => {
                                            const hasEssentialIntegration = essentialTools.some((tool) => connectedApps.has(tool.appId.toLowerCase()));
                                            const fileMeetsRequirement = uploadStatus === 'uploaded' && analysisResult?.meets_requirement === true;
                                            return !hasEssentialIntegration && !fileMeetsRequirement;
                                        })()
                                    }
                                >
                                    <span className="hidden sm:inline">Continue to Optional Integrations</span>
                                    <span className="sm:hidden">Continue</span>
                                    <CaretRightIcon className="size-4" />
                                </Button>
                            </div> 
                        </div>
                    </AuthSimpleLayout>
                )}
            </div>

            {/* Step 6: Optional Integrations */}
            <div className={`${step !== 6 ? 'hidden' : ''} ${ANIMATION_CONFIG.stepTransition}`}>
                {step === 6 && (
                    <AuthSimpleLayout title={getTitle()} description={getDescription()}>
                        <div className="mx-auto w-full max-w-4xl space-y-6 px-2 sm:px-4">
                            {isFetchingToken && (
                                <div className="flex animate-pulse items-center justify-center gap-2 py-2 text-sm text-muted-foreground">
                                    <LoaderCircle className="size-4 animate-spin" />
                                    <span>Wait a moment...</span>
                                </div>
                            )}

                            {/* Optional Integrations Section */}
                            <div className="space-y-4">
                                <div className="flex flex-wrap justify-center gap-2 sm:gap-3">
                                    {optionalTools.map((tool) => {
                                        const appId = tool.appId.toLowerCase();
                                        const isConnecting = connectingApp === appId;
                                        const isConnected = connectedApps.has(appId);

                                        return (
                                            <Button
                                                key={tool.name}
                                                variant={isConnected ? 'default' : 'outline'}
                                                className="relative flex items-center gap-2 px-3 py-2 transition-all duration-200 hover:bg-accent hover:shadow-sm active:scale-95 sm:px-4"
                                                onClick={() => handleToolClick(tool)}
                                                disabled={isConnecting || isConnected || !isClientReady || isFetchingToken}
                                            >
                                                {isConnecting ? (
                                                    <LoaderCircle className="size-4 flex-shrink-0 animate-spin sm:size-5" />
                                                ) : isConnected ? (
                                                    <CheckIcon className="size-4 flex-shrink-0 text-green-600 sm:size-5 dark:text-green-400" />
                                                ) : tool.iconUrl ? (
                                                    <img
                                                        src={tool.iconUrl || '/placeholder.svg'}
                                                        alt={tool.name}
                                                        className="size-4 flex-shrink-0 object-contain sm:size-5"
                                                    />
                                                ) : tool.icon ? (
                                                    (() => {
                                                        const IconComponent = tool.icon;
                                                        return <IconComponent className="size-4 flex-shrink-0 sm:size-5" />;
                                                    })()
                                                ) : null}
                                                <span className="line-clamp-1 text-xs font-medium sm:text-sm">
                                                    {isConnecting ? 'Connecting...' : isConnected ? tool.name : tool.name}
                                                </span>
                                            </Button>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Connection Status */}
                            {connectedApps.size > 0 && (
                                <div className="rounded-lg border bg-primary-foreground p-4 text-center">
                                    <p className="text-sm font-medium">
                                        {connectedApps.size} {connectedApps.size === 1 ? 'integration' : 'integrations'} connected
                                    </p>
                                </div>
                            )}

                            {/* Navigation */}
                            <div className="flex w-full justify-between gap-2 pt-4 sm:gap-3">
                                <Button variant="ghost" size="sm" onClick={handleBack} className="flex-1 sm:flex-none">
                                    <CaretLeftIcon className="size-4" />
                                    <span className="hidden sm:inline">Back</span>
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={completeOnboarding}
                                    variant="default"
                                    className="flex-1 sm:flex-none"
                                >
                                    <span className="hidden sm:inline">Complete Onboarding</span>
                                    <span className="sm:hidden">Complete</span>
                                    <CaretRightIcon className="size-4" />
                                </Button>
                            </div>
                        </div>
                    </AuthSimpleLayout>
                )}
            </div>

            {/* Paddle Checkout Overlay - Opens Paddle's overlay directly, no modal needed */}
            <PaddleCheckout
                open={showCheckout}
                onOpenChange={setShowCheckout}
                checkoutData={checkout_data || null}
                paddleToken={paddleToken}
                onError={(error) => {
                    toast.error(error);
                    setShowCheckout(false);
                }}
            />

            {/* More Integrations Modal */}
            <Dialog open={showMoreIntegrationsModal} onOpenChange={setShowMoreIntegrationsModal}>
                <DialogContent className="max-w-4xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>All Available Accounting Integrations</DialogTitle>
                        <DialogDescription>
                            Connect additional accounting and payment systems to analyze your expenses
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-6 py-4">
                        {/* Additional Accounting Tools */}
                        <div className="space-y-3">
                            <h3 className="text-sm font-semibold text-muted-foreground">Payment & Accounting Systems</h3>
                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3">
                                {additionalAccountingTools.map((tool) => {
                                    const appId = tool.appId.toLowerCase();
                                    const isConnecting = connectingApp === appId;
                                    const isConnected = connectedApps.has(appId);

                                    return (
                                        <Button
                                            key={tool.name}
                                            variant={isConnected ? 'default' : 'outline'}
                                            className="relative flex flex-col items-center gap-2 px-3 py-3 transition-all duration-200 hover:bg-accent hover:shadow-sm active:scale-95 sm:px-4 h-auto"
                                            onClick={() => {
                                                handleToolClick(tool);
                                            }}
                                            disabled={isConnecting || (!isConnected && connectingApp !== null) || !isClientReady || isFetchingToken}
                                        >
                                            {isConnecting ? (
                                                <LoaderCircle className="size-5 flex-shrink-0 animate-spin" />
                                            ) : isConnected ? (
                                                <CheckIcon className="size-5 flex-shrink-0 text-green-600 dark:text-green-400" />
                                            ) : tool.iconUrl ? (
                                                <img
                                                    src={tool.iconUrl || '/placeholder.svg'}
                                                    alt={tool.name}
                                                    className="size-5 flex-shrink-0 object-contain"
                                                />
                                            ) : tool.icon ? (
                                                (() => {
                                                    const IconComponent = tool.icon;
                                                    return <IconComponent className="size-5 flex-shrink-0" />;
                                                })()
                                            ) : null}
                                            <span className="line-clamp-2 text-xs font-medium text-center">
                                                {isConnecting ? 'Connecting...' : isConnected ? tool.name : tool.name}
                                            </span>
                                        </Button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

        </>
    );
}
