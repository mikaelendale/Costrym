import Modal from '@/components/ui/modal';
// import { buildAuthHeaders } from '@/utils/authHeaders';
import { useMemo, useState } from 'react';

// add props
type ServiceBadgeProps = {
    src: string;
    alt: string;
    onConnect: (src: string) => void;
    loading: boolean;
    status: string;
};

const ServiceBadge = ({ src, alt, onConnect, loading, status }: ServiceBadgeProps) => {
    const label = loading ? 'Connecting...' : status === 'success' ? 'Connected' : status === 'error' ? 'Retry' : 'Connect';

    return (
        <div className="flex items-center gap-3 rounded-lg border border-border bg-background/50 p-3">
            <span className="inline-flex h-8 w-8 items-center justify-center rounded-md bg-background ring-1 ring-border">
                <img src={src} alt={alt} className="h-4 w-4" />
            </span>
            <button
                type="button"
                className="ml-auto inline-flex items-center justify-center rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:ring-offset-background focus:outline-none disabled:opacity-60"
                onClick={() => onConnect?.(src)}
                disabled={!!loading}
            >
                {label}
            </button>
        </div>
    );
};

/**
 * ConnectServiceModal
 * Props:
 * - open: boolean
 * - onClose: () => void
 * - title: string (e.g., the card title)
 * - services: string (single icon URL)
 */
const ConnectServiceModal = ({ open, onClose, title, services = '' }: { open: boolean; onClose: () => void; title: string; services?: string }) => {
    // `services` is a single string (icon URL). Convert to an array for rendering.
    const unique = useMemo(() => (services ? [services] : []), [services]);

    // Track per-service connection status and in-flight state
    const [connecting, setConnecting] = useState<string | null>(null); // holds current src when connecting
    const [statuses, setStatuses] = useState<{ [key: string]: string }>({});

    const handleClose = () => {
        setConnecting(null);
        setStatuses({});
        onClose?.();
    };

    const handleConnect = async (src: string) => {
        setConnecting(src);
        setStatuses((prev) => ({ ...prev, [src]: 'loading' }));

        try {
            const res = await fetch('', {
                method: 'POST',
                // headers: buildAuthHeaders(props),
            });

            if (!res.ok) {
                throw new Error(`Request failed with status ${res.status}`);
            }
            // redirect to a new window to complete oauth
            const data = await res.json();
            window.location.href = data.authUrl;
            setStatuses((prev) => ({ ...prev, [src]: 'success' }));
        } catch (e) {
            console.error('Connect error', e);
            setStatuses((prev) => ({ ...prev, [src]: 'error' }));
        } finally {
            setConnecting(null);
        }
    };

    return (
        <Modal
            open={open}
            onClose={handleClose}
            title={`Connect services for: ${title}`}
            description="Choose a service to connect. You can manage connections later in settings."
            footer={
                <>
                    <button
                        type="button"
                        onClick={handleClose}
                        className="inline-flex items-center justify-center rounded-md bg-secondary px-3 py-1.5 text-sm font-medium text-secondary-foreground shadow-sm transition-colors hover:bg-secondary/80 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:ring-offset-background focus:outline-none"
                    >
                        Close
                    </button>
                </>
            }
        >
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                {unique.map((src, idx) => (
                    <ServiceBadge key={idx} src={src} alt="service" onConnect={handleConnect} loading={connecting === src} status={statuses[src]} />
                ))}
            </div>
        </Modal>
    );
};

export default ConnectServiceModal;
