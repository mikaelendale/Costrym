import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Initialize Echo with Ably
const initEcho = () => {
    const ablyKey = import.meta.env.VITE_ABLY_PUBLIC_KEY;
    
    if (!ablyKey) {
        console.error('❌ VITE_ABLY_PUBLIC_KEY not set');
        return;
    }
    
    try {
        const pusherClient = new Pusher(ablyKey, {
            cluster: 'mt1',
            wsHost: 'realtime-pusher.ably.io',
            wsPort: 443,
            wssPort: 443,
            disableStats: true,
            authEndpoint: '/broadcasting/auth',
        });
        
        (window as any).Echo = new Echo({
            broadcaster: 'pusher',
            client: pusherClient,
        });
        
        console.log('✅ Echo initialized with Ably');
    } catch (error) {
        console.error('❌ Failed to initialize Echo:', error);
    }
};

// Initialize Echo after DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEcho);
} else {
    initEcho();
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
