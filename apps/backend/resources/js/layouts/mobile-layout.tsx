import { Link } from '@inertiajs/react';
import { Home, ShoppingCart, Users } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useCurrentUrl } from '@/hooks/use-current-url';

type NavItemProps = {
    href: string;
    icon: React.ReactNode;
    label: string;
    active: boolean;
};

function NavItem({ href, icon, label, active }: NavItemProps) {
    return (
        <Link
            href={href}
            className={cn(
                'flex flex-1 flex-col items-center justify-center gap-1 py-2 text-xs font-medium transition-colors',
                active ? 'text-primary' : 'text-muted-foreground hover:text-foreground',
            )}
        >
            <span className={cn('flex h-6 w-6 items-center justify-center', active && '[&>svg]:stroke-[2.5]')}>
                {icon}
            </span>
            {label}
        </Link>
    );
}

export default function MobileLayout({ children }: { children: React.ReactNode }) {
    const { isCurrentOrParentUrl, isCurrentUrl } = useCurrentUrl();

    return (
        <>
            {/* Header fixe */}
            <header className="bg-background fixed left-0 right-0 top-0 z-50 flex h-14 items-center border-b px-4">
                <span className="text-base font-bold tracking-tight">BadManager</span>
            </header>

            {/* Contenu : poussé sous le header, padding bas pour la nav */}
            <main className="min-h-screen pt-14 pb-16">
                {children}
            </main>

            {/* Bottom nav fixe */}
            <nav className="bg-background fixed bottom-0 left-0 right-0 z-50 border-t">
                <div className="flex h-16">
                    <NavItem
                        href="/"
                        icon={<Home className="h-5 w-5" />}
                        label="Accueil"
                        active={isCurrentUrl('/')}
                    />
                    <NavItem
                        href="/players"
                        icon={<Users className="h-5 w-5" />}
                        label="Joueurs"
                        active={isCurrentOrParentUrl('/players')}
                    />
                    <NavItem
                        href="/companion/order"
                        icon={<ShoppingCart className="h-5 w-5" />}
                        label="Commande"
                        active={isCurrentOrParentUrl('/companion')}
                    />
                </div>
            </nav>
        </>
    );
}
