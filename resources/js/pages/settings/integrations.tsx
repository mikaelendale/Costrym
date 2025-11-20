import { Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { PipedreamClient } from '@pipedream/sdk';
import { CheckCircle2, XCircle, Loader2, RefreshCw, CheckIcon, LoaderCircle } from 'lucide-react';
import { GoogleGmail, NotionIcon, Xero, Zoho } from 'brand-logos';
import { toast } from 'sonner';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItem, type SharedData } from '@/types';

interface Tool {
    name: string;
    appId: string;
    icon?: React.ComponentType<{ className?: string }>;
    iconUrl?: string;
    requiresPipedream: boolean;
    required?: boolean;
}

// Map available integrations to tools with icons
const getToolForAppId = (appId: string, name: string, required: boolean): Tool | null => {
    const toolMap: Record<string, Omit<Tool, 'name' | 'required'>> = {
        xero_accounting_api: {
            appId: 'xero_accounting_api',
            icon: Xero,
            requiresPipedream: true,
        },
        zoho_books: {
            appId: 'zoho_books',
            icon: Zoho,
            requiresPipedream: true,
        },
        quickbooks: {
            appId: 'quickbooks',
            iconUrl: 'https://freebiehive.com/wp-content/uploads/2024/06/Quickbooks-Logo-PNG-758x473.jpg',
            requiresPipedream: true,
        },
        sevdesk: {
            appId: 'sevdesk',
            iconUrl: 'https://www.sevdesk.de/favicon.ico',
            requiresPipedream: true,
        },
        expensify: {
            appId: 'expensify',
            iconUrl: 'https://www.expensify.com/favicon.ico',
            requiresPipedream: true,
        },
        gmail: {
            appId: 'gmail',
            icon: GoogleGmail,
            requiresPipedream: true,
        },
        notion: {
            appId: 'notion',
            icon: NotionIcon,
            requiresPipedream: true,
        },
    };

    const toolConfig = toolMap[appId.toLowerCase()];
    if (!toolConfig) return null;

    return {
        ...toolConfig,
        name,
        required,
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Integrations',
        href: '/settings/integrations',
    },
];

interface ConnectedAccount {
    id: number;
    pipedream_account_id: string;
    app: string;
    external_user_id: string;
    metadata: any;
    is_active: boolean;
    connected_at: string;
    updated_at: string;
}

interface AvailableIntegration {
    app_id: string;
    name: string;
    category: string;
    required: boolean;
}

interface IntegrationsPageProps {
    connectedAccounts: ConnectedAccount[];
    availableIntegrations: AvailableIntegration[];
}

export default function Integrations({ connectedAccounts: initialAccounts, availableIntegrations }: IntegrationsPageProps) {
    const { auth } = usePage<SharedData>().props;
    const userId = auth.user?.id?.toString() || '1';
    
    const [connectedAccounts, setConnectedAccounts] = useState<ConnectedAccount[]>(initialAccounts);
    const [connectedApps, setConnectedApps] = useState<Set<string>>(
        new Set(initialAccounts.map((acc) => acc.app.toLowerCase()))
    );
    const [pipedreamClient, setPipedreamClient] = useState<PipedreamClient | null>(null);
    const [isClientReady, setIsClientReady] = useState(false);
    const [isFetchingToken, setIsFetchingToken] = useState(false);
    const [connectingApp, setConnectingApp] = useState<string | null>(null);
    const [refreshing, setRefreshing] = useState(false);

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
                    setConnectedAccounts(data.accounts.map((acc: any) => ({
                        id: acc.id,
                        pipedream_account_id: acc.pipedream_account_id,
                        app: acc.app,
                        external_user_id: acc.external_user_id,
                        metadata: acc.metadata,
                        is_active: acc.is_active,
                        connected_at: acc.connected_at,
                        updated_at: acc.updated_at,
                    })));
                }
            }
        } catch (error) {
            console.error('Error loading connected accounts:', error);
        }
    };

    // Initialize Pipedream client - same as onboarding
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

    const handleDisconnect = async (appId: string) => {
        if (!confirm(`Are you sure you want to disconnect ${appId}?`)) {
            return;
        }

        try {
            const response = await fetch(`/connect/${appId}/disconnect`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    toast.success(`${appId} disconnected successfully`);
                    setConnectedApps((prev) => {
                        const newSet = new Set(prev);
                        newSet.delete(appId.toLowerCase());
                        return newSet;
                    });
                    await loadConnectedAccounts();
                } else {
                    throw new Error(data.error || 'Failed to disconnect');
                }
            } else {
                const errorData = await response.json().catch(() => ({ error: 'Failed to disconnect' }));
                throw new Error(errorData.error || 'Failed to disconnect');
            }
        } catch (error: any) {
            toast.error(`Failed to disconnect ${appId}: ${error.message}`);
        }
    };

    const handleRefresh = async () => {
        setRefreshing(true);
        try {
            await loadConnectedAccounts();
            toast.success('Integrations refreshed');
        } catch (error) {
            toast.error('Failed to refresh integrations');
        } finally {
            setRefreshing(false);
        }
    };

    const getConnectedApp = (appId: string) => {
        return connectedAccounts.find((account) => account.app.toLowerCase() === appId.toLowerCase());
    };

    // Convert available integrations to tools with icons
    const tools = availableIntegrations
        .map((integration) => getToolForAppId(integration.app_id, integration.name, integration.required))
        .filter((tool): tool is Tool => tool !== null);

    const groupedTools = tools.reduce((acc, tool) => {
        const integration = availableIntegrations.find((i) => i.app_id === tool.appId);
        const category = integration?.category || 'other';
        if (!acc[category]) {
            acc[category] = [];
        }
        acc[category].push(tool);
        return acc;
    }, {} as Record<string, Tool[]>);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Integrations" />

            <SettingsLayout>
                <div className="space-y-8">
                    <div className="flex items-start justify-between gap-4">
                        <div className="space-y-1">
                            <h1 className="text-2xl font-semibold tracking-tight">Integrations</h1>
                            <p className="text-sm text-muted-foreground">
                                Connect your favorite apps and services to streamline your workflow
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleRefresh}
                            disabled={refreshing}
                        >
                            <RefreshCw className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} />
                            <span className="ml-2 hidden sm:inline">Refresh</span>
                        </Button>
                    </div>

                    {!isClientReady && isFetchingToken && (
                        <Card>
                            <CardContent className="py-8">
                                <div className="flex items-center justify-center gap-3">
                                    <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                                    <span className="text-sm text-muted-foreground">Initializing connection service...</span>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {connectedAccounts.length > 0 && (
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="text-lg">Connected Apps</CardTitle>
                                        <CardDescription>Apps you've successfully connected</CardDescription>
                                    </div>
                                    <Badge variant="secondary" className="text-xs">
                                        {connectedAccounts.length}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                    {connectedAccounts.map((account) => {
                                        const tool = getToolForAppId(account.app, account.app, false);
                                        return (
                                            <div key={account.id} className="group relative">
                                                <div className="flex flex-col items-center gap-3 rounded-lg border bg-card p-4 transition-all hover:border-primary/50 hover:shadow-sm">
                                                    <div className="relative">
                                                        {tool?.iconUrl ? (
                                                            <img
                                                                src={tool.iconUrl || "/placeholder.svg"}
                                                                alt={tool.name}
                                                                className="h-10 w-10 object-contain"
                                                            />
                                                        ) : tool?.icon ? (
                                                            (() => {
                                                                const IconComponent = tool.icon;
                                                                return <IconComponent className="h-10 w-10" />;
                                                            })()
                                                        ) : null}
                                                        <div className="absolute -bottom-1 -right-1 rounded-full bg-green-500 p-0.5">
                                                            <CheckCircle2 className="h-3 w-3 text-white" />
                                                        </div>
                                                    </div>
                                                    <span className="line-clamp-1 text-center text-xs font-medium capitalize">
                                                        {(tool?.name || account.app).length > 16 
                                                            ? (tool?.name || account.app).slice(0, 5) + '…' 
                                                            : (tool?.name || account.app)}
                                                    </span>
                                                </div>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => handleDisconnect(account.app)}
                                                    className="absolute -right-2 -top-2 h-6 w-6 rounded-full bg-destructive opacity-0 transition-opacity hover:bg-destructive/90 group-hover:opacity-100"
                                                    title={`Disconnect ${account.app}`}
                                                >
                                                    <XCircle className="h-3.5 w-3.5 text-white" />
                                                </Button>
                                            </div>
                                        );
                                    })}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {Object.entries(groupedTools).map(([category, categoryTools]) => (
                        <div key={category} className="space-y-4">
                            <div className="flex items-center gap-3">
                                <h3 className="text-lg font-semibold capitalize">{category.replace('_', ' ')}</h3>
                                <Badge variant="outline" className="text-xs">
                                    {categoryTools.length}
                                </Badge>
                            </div>
                            
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                {categoryTools.map((tool) => {
                                    const appId = tool.appId.toLowerCase();
                                    const isConnecting = connectingApp === appId;
                                    const isConnected = connectedApps.has(appId);

                                    return (
                                        <button
                                            key={tool.appId}
                                            onClick={() => handleToolClick(tool)}
                                            disabled={isConnecting || isConnected || !isClientReady || isFetchingToken}
                                            className={`group relative flex flex-col items-center gap-3 rounded-lg border p-4 transition-all hover:border-primary/50 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-60 ${
                                                isConnected 
                                                    ? 'border-green-500/30 bg-green-50/50 dark:bg-green-950/20' 
                                                    : tool.required 
                                                    ? 'border-orange-500/50 bg-orange-50/50 dark:bg-orange-950/20'
                                                    : 'bg-card hover:bg-accent'
                                            }`}
                                        >
                                            {tool.required && !isConnected && (
                                                <div
                                                    className="absolute -right-1 -top-1 h-5 px-1.5 flex items-center justify-center rounded border border-accent bg-primary-foreground text-primary text-[10px] font-medium"
                                                >
                                                    Required
                                                </div>
                                            )}
                                            
                                            <div className="relative">
                                                {isConnecting ? (
                                                    <LoaderCircle className="h-10 w-10 animate-spin text-muted-foreground" />
                                                ) : tool.iconUrl ? (
                                                    <img
                                                        src={tool.iconUrl || "/placeholder.svg"}
                                                        alt={tool.name}
                                                        className="h-10 w-10 object-contain"
                                                    />
                                                ) : tool.icon ? (
                                                    (() => {
                                                        const IconComponent = tool.icon;
                                                        return <IconComponent className="h-10 w-10" />;
                                                    })()
                                                ) : null}
                                                
                                                {isConnected && (
                                                    <div className="absolute -bottom-1 -right-1 rounded-full bg-green-500 p-0.5">
                                                        <CheckCircle2 className="h-3 w-3 text-white" />
                                                    </div>
                                                )}
                                            </div>
                                            
                                            <span className="line-clamp-2 text-center text-xs font-medium leading-tight">
                                                {isConnecting ? 'Connecting...' : tool.name}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    ))}

                    {connectedAccounts.length === 0 && tools.length === 0 && (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center py-16 text-center">
                                <div className="mb-4 rounded-full bg-muted p-3">
                                    <XCircle className="h-8 w-8 text-muted-foreground" />
                                </div>
                                <h3 className="mb-2 text-lg font-semibold">No integrations available</h3>
                                <p className="text-sm text-muted-foreground">
                                    Check back later for available integrations
                                </p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
