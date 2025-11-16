import { useEffect, useRef } from 'react';

/**
 * PaddleCheckout Component
 * 
 * Opens Paddle checkout in overlay mode (no modal needed).
 * Handles Paddle.js initialization and opens overlay checkout using checkout options.
 * 
 * @param open - Controls when to open the checkout overlay
 * @param onOpenChange - Callback when checkout state changes
 * @param checkoutData - Paddle checkout options from backend
 * @param paddleToken - Paddle client-side token from backend
 */
interface PaddleCheckoutProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    checkoutData?: {
        type: string;
        options: any;
    } | null;
    paddleToken?: string;
    onError?: (error: string) => void;
}

export function PaddleCheckout({ open, onOpenChange, checkoutData, paddleToken, onError }: PaddleCheckoutProps) {
    const paddleInitialized = useRef(false);
    const checkoutOpened = useRef(false);

    /**
     * Initialize Paddle.js SDK and open overlay checkout when open is true
     */
    useEffect(() => {
        if (!open || !checkoutData) {
            return;
        }

        const handleCheckout = () => {
            if (checkoutOpened.current) {
                return;
            }

            try {
                const paddle = (window as any).Paddle;
                
                if (!paddle) {
                    throw new Error('Paddle SDK not loaded');
                }

                if (!checkoutData || checkoutData.type !== 'paddle_checkout' || !checkoutData.options) {
                    throw new Error('Invalid checkout data');
                }

                // Update options to use overlay mode (no container needed)
                const optionsWithOverlay = {
                    ...checkoutData.options,
                    settings: {
                        ...checkoutData.options.settings,
                        displayMode: 'overlay', // Use overlay instead of inline
                    },
                };

                // Set up Paddle checkout event listeners
                const handleCheckoutComplete = () => {
                    window.dispatchEvent(new CustomEvent('paddle-checkout-complete'));
                    checkoutOpened.current = false;
                    onOpenChange(false);
                };

                const handleCheckoutClosed = () => {
                    window.dispatchEvent(new CustomEvent('paddle-checkout-closed'));
                    checkoutOpened.current = false;
                    onOpenChange(false);
                };

                // Listen for Paddle checkout events
                if (typeof window !== 'undefined') {
                    window.addEventListener('paddle:checkout:complete', handleCheckoutComplete);
                    window.addEventListener('paddle:checkout:closed', handleCheckoutClosed);
                }

                // Open overlay checkout (Paddle handles the overlay UI)
                paddle.Checkout.open(optionsWithOverlay);
                checkoutOpened.current = true;
            } catch (err) {
                const errorMessage = err instanceof Error ? err.message : 'Failed to open checkout. Please try again.';
                onError?.(errorMessage);
                checkoutOpened.current = false;
                onOpenChange(false);
            }
        };

        const initializePaddle = async () => {
            // Check if Paddle is already loaded
            if (typeof window !== 'undefined' && (window as any).Paddle) {
                paddleInitialized.current = true;
                handleCheckout();
                return;
            }

            // Load Paddle.js SDK dynamically
            const script = document.createElement('script');
            script.src = 'https://cdn.paddle.com/paddle/v2/paddle.js';
            script.async = true;

            script.onload = () => {
                try {
                    if (!paddleToken) {
                        throw new Error('Paddle client-side token is not configured');
                    }

                    // Initialize Paddle
                    (window as any).Paddle.Setup({
                        token: paddleToken,
                        environment: import.meta.env.VITE_APP_ENV === 'production' ? 'production' : 'sandbox',
                    });

                    paddleInitialized.current = true;
                    handleCheckout();
                } catch (err) {
                    const errorMessage = err instanceof Error ? err.message : 'Failed to initialize Paddle';
                    onError?.(errorMessage);
                    onOpenChange(false);
                }
            };

            script.onerror = () => {
                const errorMessage = 'Failed to load Paddle checkout. Please check your connection.';
            };

            document.head.appendChild(script);
        };

        // Small delay to ensure everything is ready
        const timeout = setTimeout(() => {
            if (paddleInitialized.current) {
                handleCheckout();
            } else {
                initializePaddle();
            }
        }, 100);

        // Cleanup function
        return () => {
            clearTimeout(timeout);
            checkoutOpened.current = false;
        };
    }, [open, checkoutData, paddleToken, onError, onOpenChange]);

    // Overlay checkout doesn't need a modal - Paddle handles the overlay
    // This component just triggers the checkout opening
    return null;
}

