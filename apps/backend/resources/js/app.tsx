import { registerSW } from 'virtual:pwa-register';
import { createInertiaApp } from '@inertiajs/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AuthLayout from '@/layouts/auth-layout';
import MobileLayout from '@/layouts/mobile-layout';

const queryClient = new QueryClient();

function AppWrapper({ children }: { children: React.ReactNode }) {
    return (
        <QueryClientProvider client={queryClient}>
            <TooltipProvider delayDuration={0}>
                {children}
                <Toaster />
            </TooltipProvider>
        </QueryClientProvider>
    );
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        if (name.startsWith('auth/')) return AuthLayout;
        return MobileLayout;
    },
    strictMode: true,
    withApp(app) {
        return <AppWrapper>{app}</AppWrapper>;
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();

registerSW({ immediate: true });
