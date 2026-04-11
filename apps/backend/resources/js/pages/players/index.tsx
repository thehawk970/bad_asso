import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type LicenseSummary = {
    status: string;
    season: string;
};

type PaymentSummary = {
    status: string;
    amount: number;
};

type Player = {
    id: number;
    full_name: string;
    email: string | null;
    phone: string | null;
    has_valid_license: boolean;
    has_valid_payment: boolean;
    latest_license: LicenseSummary | null;
    latest_payment: PaymentSummary | null;
};

type Props = {
    players: Player[];
};

function LicenseBadge({ player }: { player: Player }) {
    if (!player.latest_license) {
        return <Badge variant="outline">Aucune</Badge>;
    }

    const variantMap: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        validated: 'default',
        in_progress: 'secondary',
        pending: 'outline',
    };

    const labelMap: Record<string, string> = {
        validated: 'Validée',
        in_progress: 'En cours',
        pending: 'En attente',
    };

    const status = player.latest_license.status;

    return (
        <Badge variant={variantMap[status] ?? 'outline'}>
            {labelMap[status] ?? status} — {player.latest_license.season}
        </Badge>
    );
}

function PaymentBadge({ player }: { player: Player }) {
    if (!player.latest_payment) {
        return <Badge variant="outline">Aucun</Badge>;
    }

    return (
        <Badge variant={player.has_valid_payment ? 'default' : 'outline'}>
            {player.has_valid_payment ? 'Validé' : 'En attente'} —{' '}
            {new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(
                Number(player.latest_payment.amount),
            )}
        </Badge>
    );
}

export default function PlayersIndex({ players }: Props) {
    return (
        <>
            <Head title="Joueurs" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Joueurs</h1>
                        <p className="text-muted-foreground text-sm">{players.length} joueur(s) enregistré(s)</p>
                    </div>
                </div>

                <div className="rounded-lg border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="px-4 py-3 text-left font-medium">Joueur</th>
                                <th className="px-4 py-3 text-left font-medium">Contact</th>
                                <th className="px-4 py-3 text-left font-medium">Licence</th>
                                <th className="px-4 py-3 text-left font-medium">Paiement</th>
                                <th className="px-4 py-3 text-left font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {players.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="text-muted-foreground px-4 py-8 text-center">
                                        Aucun joueur enregistré.
                                    </td>
                                </tr>
                            )}
                            {players.map((player) => (
                                <tr key={player.id} className="border-b last:border-0 hover:bg-muted/30">
                                    <td className="px-4 py-3 font-medium">{player.full_name}</td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        <div>{player.email ?? '—'}</div>
                                        <div>{player.phone ?? '—'}</div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <LicenseBadge player={player} />
                                    </td>
                                    <td className="px-4 py-3">
                                        <PaymentBadge player={player} />
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={`/players/${player.id}`}>Voir</Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
