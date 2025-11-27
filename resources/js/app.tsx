import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { configureEcho } from '@laravel/echo-react';

// Configure and initialize Echo for Ably broadcasting
try {
    console.log('🚀 Initializing Echo with Ably (Pusher compatibility mode)...');
    
    // Get Ably public key from environment
    const ablyKey = import.meta.env.VITE_ABLY_PUBLIC_KEY;
    
    if (!ablyKey) {
        console.error('❌ VITE_ABLY_PUBLIC_KEY not found in environment variables');
        console.log('💡 Add VITE_ABLY_PUBLIC_KEY to your .env file');
        console.log('💡 Example: VITE_ABLY_PUBLIC_KEY="your-key-part-before-colon"');
    } else {
        // Configure Echo with Ably using Pusher protocol
        configureEcho({
            broadcaster: 'ably',
            key: ablyKey,
            wsHost: 'realtime-pusher.ably.io',
            wsPort: 443,
            disableStats: true,
            encrypted: true,
        });
        
        console.log('✅ Echo configured with Ably (Pusher compatibility mode)');
        
        // Check if Echo is available after configuration
        setTimeout(() => {
            const echo = (window as any).Echo;
            if (echo) {
                console.log('✅ Echo instance available globally');
            } else {
                console.warn('⚠️ Echo instance not found on window');
            }
        }, 100);
    }
} catch (error) {
    console.error('❌ Failed to initialize Echo:', error);
    // Don't throw - allow app to continue without real-time features
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
