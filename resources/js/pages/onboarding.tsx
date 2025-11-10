import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import AuthSimpleLayout from '@/layouts/auth/auth-simple-layout';
import { Head } from '@inertiajs/react';
import { CaretLeftIcon, CaretRightIcon, GithubLogoIcon, StripeLogoIcon, X } from '@phosphor-icons/react';
import { LoaderCircle } from 'lucide-react';
import { useState, useEffect, useMemo } from 'react';
import { GoogleIcon, GithubIcon, SlackIcon, MicrosoftIcon, NotionIcon, Stripe, GoogleGmail, Xero, Zoho, Paypal } from 'brand-logos';
import { createFrontendClient } from '@pipedream/sdk/browser';
import { FrontendClientProvider, useAccounts } from '@pipedream/connect-react';

interface Tool {
    name: string;
    icon?: React.ComponentType<{ className?: string }>;
    iconUrl?: string;
    redirectUrl: string;
}

const tools: Tool[] = [
    { name: 'Quickbooks', iconUrl: 'https://img.icons8.com/?size=100&id=70533&format=png&color=000000', redirectUrl: 'https://accounts.google.com' },
    { name: 'Plaid', iconUrl: '', redirectUrl: 'https://accounts.google.com' },
    { name: 'Gmail', icon: GoogleGmail, redirectUrl: 'https://accounts.google.com' },
    { name: 'Stripe', icon: Stripe, redirectUrl: 'https://github.com' },
    { name: 'Xero', icon: Xero, redirectUrl: 'https://login.microsoftonline.com' },
    { name: 'Zoho Books', icon: Zoho, redirectUrl: 'https://login.microsoftonline.com' },
    { name: 'PayPal', icon: Paypal, redirectUrl: 'https://login.microsoftonline.com' },
    { name: 'Notion', icon: NotionIcon, redirectUrl: 'https://notion.so' },
];

const agenticMessages = [
    "Analyzing...",
    "Organizing...",
    "Structuring...",
    "Extracting...",
    "Formatting...",
    "Almost done...",
];

export default function Onboarding() {
    // Load state from localStorage on mount
    const [step, setStep] = useState(() => {
        const saved = localStorage.getItem('onboarding_step');
        return saved ? parseInt(saved, 10) : 1;
    });
    const [content, setContent] = useState(() => {
        return localStorage.getItem('onboarding_content') || '';
    });
    const [organizedContent, setOrganizedContent] = useState(() => {
        return localStorage.getItem('onboarding_organized') || '';
    });
    const [isLoading, setIsLoading] = useState(false);
    const [loadingMessage, setLoadingMessage] = useState(agenticMessages[0]);
    const [selectedTool, setSelectedTool] = useState<Tool | null>(null);
    const [isModalOpen, setIsModalOpen] = useState(false);

    // Create Pipedream frontend client
    const pipedreamClient = useMemo(() => {
        const getCsrfToken = () => {
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

        return createFrontendClient({
            environment: 'development' as any,
            externalUserId: '1', // TODO: Get actual user ID
            tokenCallback: async () => {
                const response = await fetch('/connect/token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await response.json();
                if (data.success && data.token) {
                    return data.token;
                }
                throw new Error(data.error || 'Failed to get token');
            },
        });
    }, []);

    // Save to localStorage whenever state changes
    useEffect(() => {
        localStorage.setItem('onboarding_step', step.toString());
    }, [step]);

    useEffect(() => {
        localStorage.setItem('onboarding_content', content);
    }, [content]);

    useEffect(() => {
        if (organizedContent) {
            localStorage.setItem('onboarding_organized', organizedContent);
        }
    }, [organizedContent]);

    const handleGetStarted = () => {
        setStep(2);
    };

    const handleBack = () => {
        if (step > 1) {
            setStep(step - 1);
        }
    };

    const handleSubmit = async () => {
        const trimmedContent = content.trim();
        if (!trimmedContent || trimmedContent.length < 100 || trimmedContent.length > 200) return;
        
        setIsLoading(true);
        setLoadingMessage(agenticMessages[0]);
        
        // Rotate through agentic messages while loading
        let messageIndex = 0;
        let messageInterval: NodeJS.Timeout | null = null;
        
        messageInterval = setInterval(() => {
            messageIndex = (messageIndex + 1) % agenticMessages.length;
            setLoadingMessage(agenticMessages[messageIndex]);
        }, 2000); // Change message every 2 seconds
        
        try {
            // Get CSRF token from cookie (Laravel sets this automatically)
            const getCsrfToken = () => {
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

            const response = await fetch(route('onboarding.process'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ content }),
            });

            // Check if response is ok
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'Failed to process request' }));
                throw new Error(errorData.error || errorData.message || `Server error: ${response.status}`);
            }

            const data = await response.json();

            // Only use the API response if it's successful
            if (data.success && data.organized_content) {
                setOrganizedContent(data.organized_content);
                setStep(3);
            } else {
                // Show error message if API returns unsuccessful response
                throw new Error(data.error || data.message || 'Failed to organize content');
            }
        } catch (error) {
            console.error('Error processing company info:', error);
            // Show error to user instead of silently falling back
            alert(`Error: ${error instanceof Error ? error.message : 'Failed to process company information. Please try again.'}`);
            // Don't proceed to next step on error - let user try again
        } finally {
            // Clear the interval
            if (messageInterval) {
                clearInterval(messageInterval);
            }
            setIsLoading(false);
            setLoadingMessage(agenticMessages[0]);
        }
    };

    const handleApprove = () => {
        setStep(4);
    };

    const handleToolClick = async (tool: Tool) => {
        // For Gmail, use Pipedream Connect SDK
        if (tool.name.toLowerCase() === 'gmail') {
            try {
                // Use Pipedream SDK to connect - this opens OAuth flow
                const account: any = await (pipedreamClient as any).connectAccount('gmail');
                
                if (account?.id) {
                    // Save connection to backend
                    const getCsrfToken = () => {
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

                    try {
                        const saveResponse = await fetch(`/connect/gmail/save`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-XSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                connection_id: account.id,
                                external_user_id: account.external_user_id,
                                metadata: account,
                            }),
                        });

                        const saveData = await saveResponse.json();
                        if (saveData.success) {
                            alert('Gmail connected successfully!');
                        } else {
                            alert(saveData.error || 'Failed to save connection');
                        }
                    } catch (saveError) {
                        console.error('Save error:', saveError);
                        alert('Connected but failed to save. Please refresh.');
                    }
                }
            } catch (error) {
                console.error('Connection error:', error);
                alert('Failed to connect. Please try again.');
            }
        } else {
            // For other tools, show modal (keep existing behavior)
            setSelectedTool(tool);
            setIsModalOpen(true);
        }
    };

    const handleConfirmRedirect = () => {
        if (selectedTool) {
            window.location.href = selectedTool.redirectUrl;
        }
    };

    // Close modal on Escape key
    useEffect(() => {
        const handleEscape = (e: KeyboardEvent) => {
            if (e.key === 'Escape' && isModalOpen) {
                setIsModalOpen(false);
            }
        };
        document.addEventListener('keydown', handleEscape);
        return () => document.removeEventListener('keydown', handleEscape);
    }, [isModalOpen]);

    const getTitle = () => {
        switch (step) {
            case 1: return "Welcome! Let's get started";
            case 2: return "Tell us about your company";
            case 3: return "Review your information";
            case 4: return "Integrate your tools";
            default: return "Onboarding";
        }
    };

    const getDescription = () => {
        switch (step) {
            case 1: return "Complete a few quick steps to set up your account";
            case 2: return "Help us personalize your experience";
            case 3: return "Please review and approve the organized information";
            case 4: return "Connect your favorite tools for seamless integration";
            default: return "";
        }
    };

    return (
        <FrontendClientProvider client={pipedreamClient}>
            <Head title={getTitle()} />
            <AuthSimpleLayout
                title={getTitle()}
                description={getDescription()}
            >
                <div className="relative w-full min-h-[200px]">
                    {/* Step 1 */}
                    <div
                        className={`transition-all duration-500 ease-in-out ${
                            step === 1
                                ? 'opacity-100 translate-x-0'
                                : 'opacity-0 -translate-x-4 absolute inset-0 pointer-events-none'
                        }`}
                    >
                        <div className="space-y-6">
                            <div className="pt-4 w-full flex">
                                <Button className="mx-auto" size="sm" onClick={handleGetStarted}>
                                    Get Started
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Step 2 */}
                    <div
                        className={`transition-all duration-500 ease-in-out ${
                            step === 2
                                ? 'opacity-100 translate-x-0'
                                : 'opacity-0 translate-x-4 absolute inset-0 pointer-events-none'
                        }`}
                    >
                        <div className="space-y-6 w-full">
                            <div className="space-y-2">
                                <Textarea
                                    placeholder="Tell us about your company..."
                                    value={content}
                                    onChange={(e) => {
                                        const newValue = e.target.value;
                                        if (newValue.length <= 200) {
                                            setContent(newValue);
                                        }
                                    }}
                                    disabled={isLoading}
                                    maxLength={200}
                                    className="w-full min-h-[100px] rounded-2xl bg-primary-foreground border"
                                    style={{
                                        resize: 'none',
                                    }}
                                />
                                <div className="flex justify-between items-center text-xs text-muted-foreground px-1">
                                    <span>
                                        {content.trim().length < 100 
                                            ? `${100 - content.trim().length} more characters needed`
                                            : content.trim().length > 200
                                            ? 'Too many characters'
                                            : 'Ready to continue'}
                                    </span>
                                    <span className={
                                        content.trim().length >= 100 && content.trim().length <= 200 
                                            ? 'text-green-600 dark:text-green-400' 
                                            : content.trim().length > 200
                                            ? 'text-red-600 dark:text-red-400'
                                            : ''
                                    }>
                                        {content.trim().length}/200
                                    </span>
                                </div>
                            </div>

                            <div className="pt-4 w-full flex gap-3 justify-between">
                                <Button variant="ghost" size="sm" onClick={handleBack} disabled={isLoading}>
                                    <CaretLeftIcon className="size-4" />Back
                                </Button>
                                <Button size="sm" onClick={handleSubmit} disabled={isLoading || content.trim().length < 100 || content.trim().length > 200}>
                                    {isLoading ? (
                                        <>
                                            <LoaderCircle className="size-4 animate-spin" />
                                            {loadingMessage}
                                        </>
                                    ) : (
                                        <>
                                            Continue <CaretRightIcon className="size-4" />
                                        </>
                                    )}
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Step 3 - Organized Output */}
                    <div
                        className={`transition-all duration-500 ease-in-out ${
                            step === 3
                                ? 'opacity-100 translate-x-0'
                                : 'opacity-0 translate-x-4 absolute inset-0 pointer-events-none'
                        }`}
                    >
                        <div className="space-y-6 w-full">
                            <div className="space-y-4">
                                <Textarea
                                    value={organizedContent}
                                    onChange={(e) => setOrganizedContent(e.target.value)}
                                    className="w-full min-h-[100px] rounded-2xl bg-primary-foreground border font-mono text-sm whitespace-pre-wrap"
                                    style={{
                                        resize: 'none',
                                    }}
                                />
                            </div>

                            <div className="pt-4 w-full flex gap-3 justify-between">
                                <Button variant="ghost" size="sm" onClick={handleBack}>
                                    <CaretLeftIcon className="size-4" />Back
                                </Button>
                                <Button size="sm" onClick={handleApprove}>
                                    Approve <CaretRightIcon className="size-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Step 4 - Tool Integration */}
                    <div
                        className={`transition-all duration-500 ease-in-out ${
                            step === 4
                                ? 'opacity-100 translate-x-0'
                                : 'opacity-0 translate-x-4 absolute inset-0 pointer-events-none'
                        }`}
                    >
                        <div className="space-y-6 w-full">
                            <div className="flex flex-wrap gap-3 justify-center">
                                {tools.map((tool) => {
                                    return (
                                        <Button
                                            key={tool.name}
                                            variant="outline"
                                            className="flex items-center gap-2 px-4 py-2 hover:bg-accent transition-colors"
                                            onClick={() => handleToolClick(tool)}
                                        >
                                            {tool.iconUrl ? (
                                                <img src={tool.iconUrl} alt={tool.name} className="size-5 object-contain" />
                                            ) : tool.icon ? (
                                                (() => {
                                                    const IconComponent = tool.icon;
                                                    return <IconComponent className="size-5" />;
                                                })()
                                            ) : null}
                                            <span className="text-sm">{tool.name}</span>
                                        </Button>
                                    );
                                })}
                            </div>

                            <div className="pt-4 w-full flex gap-3 justify-between">
                                <Button variant="ghost" size="sm" onClick={handleBack}>
                                    <CaretLeftIcon className="size-4" />Back
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthSimpleLayout>

            {/* Custom Branded Modal */}
            {isModalOpen && selectedTool && (
                <>
                    {/* Backdrop */}
                    <div
                        className="fixed inset-0 z-50 bg-black/10  animate-in fade-in-0"
                        onClick={() => setIsModalOpen(false)}
                    />
                    
                    {/* Modal */}
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <div
                            className="relative w-full max-w-md bg-background border border-border rounded-3xl shadow-sm p-8 animate-in zoom-in-95 fade-in-0 duration-300"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {/* Close Button */}
                            <button
                                onClick={() => setIsModalOpen(false)}
                                className="absolute top-4 right-4 p-2 rounded-full hover:bg-accent transition-colors text-muted-foreground hover:text-foreground"
                                aria-label="Close"
                            >
                                <X className="size-5" />
                            </button>

                            {/* Content */}
                            <div className="flex flex-col items-center text-center space-y-6">
                                {/* Tool Icon */}
                                <div className="flex items-center justify-center w-20 h-20 rounded-2xl bg-accent/50 p-4">
                                    {selectedTool.iconUrl ? (
                                        <img src={selectedTool.iconUrl} alt={selectedTool.name} className="size-12 object-contain" />
                                    ) : selectedTool.icon ? (
                                        (() => {
                                            const IconComponent = selectedTool.icon;
                                            return <IconComponent className="size-12" />;
                                        })()
                                    ) : null}
                                </div>

                                {/* Title */}
                                <div className="space-y-2">
                                    <h2 className="text-2xl font-normal font-sans text-foreground">
                                        Connect to {selectedTool.name}
                                    </h2>
                                    <p className="text-sm text-muted-foreground max-w-sm">
                                        You will be redirected to {selectedTool.name} to complete the integration and authorize access to your account.
                                    </p>
                                </div>
 
                                <div className="flex gap-3 pt-4 w-full ">
                                    <Button
                                        variant="outline"
                                        className="flex-1 rounded-2xl"
                                        onClick={() => setIsModalOpen(false)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        className="flex-1 rounded-2xl"
                                        onClick={handleConfirmRedirect}
                                    >
                                        Continue to {selectedTool.name}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </>
            )}
        </FrontendClientProvider>
    );
}

