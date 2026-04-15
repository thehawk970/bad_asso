import { Head, Link } from '@inertiajs/react';
import { ChevronRight, ClipboardList, ShoppingCart, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const fmt = (amount: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);

type PendingOrder = {
    id: number;
    player_name: string;
    player_id: number;
    total: number;
    amount_paid: number;
    remaining: number;
    items_count: number;
    items_summary: string;
    created_at: string;
};

type Props = {
    pendingOrders: PendingOrder[];
};

export default function Welcome({ pendingOrders }: Props) {
    return (
        <>
            <Head title="Accueil" />

            {/* Header */}
            <div className="bg-background sticky top-0 z-10 border-b px-4 py-4">
                <h1 className="text-xl font-bold">BadManager</h1>
            </div>

            <div className="space-y-6 p-4">
                {/* Actions principales */}
                <div className="grid grid-cols-2 gap-3">
                    <Link href="/players">
                        <div className="hover:border-primary/60 hover:bg-primary/5 flex h-28 flex-col items-center justify-center gap-2 rounded-xl border-2 transition-all active:scale-95">
                            <Users className="text-primary h-8 w-8" />
                            <span className="text-sm font-semibold">Joueurs</span>
                        </div>
                    </Link>

                    <Link href="/companion/order">
                        <div className="hover:border-primary/60 hover:bg-primary/5 flex h-28 flex-col items-center justify-center gap-2 rounded-xl border-2 transition-all active:scale-95">
                            <ShoppingCart className="text-primary h-8 w-8" />
                            <span className="text-sm font-semibold">Commande</span>
                        </div>
                    </Link>
                </div>

                {/* Commandes en attente */}
                <div>
                    <div className="mb-3 flex items-center gap-2">
                        <ClipboardList className="text-muted-foreground h-4 w-4" />
                        <h2 className="text-sm font-semibold uppercase tracking-wide">
                            Relances en attente
                        </h2>
                        {pendingOrders.length > 0 && (
                            <Badge variant="secondary" className="ml-auto">
                                {pendingOrders.length}
                            </Badge>
                        )}
                    </div>

                    {pendingOrders.length === 0 ? (
                        <div className="rounded-xl border border-dashed p-6 text-center">
                            <p className="text-muted-foreground text-sm">
                                Aucune commande en attente.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-2">
                            {pendingOrders.map((order) => (
                                <Link
                                    key={order.id}
                                    href={`/players/${order.player_id}`}
                                    className="block"
                                >
                                    <div className="hover:bg-muted/40 flex items-center gap-3 rounded-xl border p-4 transition-colors active:scale-[0.99]">
                                        {/* Infos joueur + articles */}
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-semibold">
                                                {order.player_name}
                                            </p>
                                            <p className="text-muted-foreground truncate text-xs">
                                                {order.items_summary ||
                                                    `${order.items_count} article${order.items_count > 1 ? 's' : ''}`}
                                            </p>
                                            <p className="text-muted-foreground mt-1 text-xs">
                                                {order.created_at}
                                            </p>
                                        </div>

                                        {/* Montants */}
                                        <div className="shrink-0 text-right">
                                            <p className="text-sm font-bold text-orange-600">
                                                {fmt(order.remaining)}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                sur {fmt(order.total)}
                                            </p>
                                        </div>

                                        <ChevronRight className="text-muted-foreground h-4 w-4 shrink-0" />
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
