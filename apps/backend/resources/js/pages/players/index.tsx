import { Head, Link } from '@inertiajs/react';
import { ChevronRight, Search } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';

type LicenseSummary = {
    status: string;
    season: string;
};

type Player = {
    id: number;
    full_name: string;
    email: string | null;
    phone: string | null;
    has_valid_license: boolean;
    has_valid_payment: boolean;
    latest_license: LicenseSummary | null;
};

type Props = {
    players: Player[];
};

const licenseVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    validated: 'default',
    in_progress: 'secondary',
    pending: 'outline',
};

const licenseLabel: Record<string, string> = {
    validated: 'Validée',
    in_progress: 'En cours',
    pending: 'En attente',
};

export default function PlayersIndex({ players }: Props) {
    const [search, setSearch] = useState('');

    const filtered = players.filter((p) =>
        p.full_name.toLowerCase().includes(search.toLowerCase()),
    );

    return (
        <>
            <Head title="Joueurs" />

            {/* Header sticky */}
            <div className="bg-background sticky top-0 z-10 border-b px-4 pb-3 pt-4">
                <div className="mb-3 flex items-center justify-between">
                    <h1 className="text-xl font-bold">Joueurs</h1>
                    <span className="text-muted-foreground text-sm">{players.length} inscrits</span>
                </div>
                <div className="relative">
                    <Search className="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                    <Input
                        placeholder="Rechercher un joueur…"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="h-10 pl-9"
                        autoComplete="off"
                    />
                </div>
            </div>

            {/* Liste */}
            <div className="p-4">
                {filtered.length === 0 && (
                    <p className="text-muted-foreground py-10 text-center text-sm">
                        Aucun joueur trouvé.
                    </p>
                )}

                <div className="space-y-2">
                    {filtered.map((player) => {
                        const license = player.latest_license;
                        const status = license?.status ?? null;

                        return (
                            <Link key={player.id} href={`/players/${player.id}`} className="block">
                                <div className="hover:bg-muted/40 flex items-center gap-3 rounded-xl border p-4 transition-colors active:scale-[0.99]">
                                    {/* Avatar initiales */}
                                    <div className="bg-primary text-primary-foreground flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold">
                                        {player.full_name
                                            .split(' ')
                                            .slice(0, 2)
                                            .map((n) => n[0])
                                            .join('')
                                            .toUpperCase()}
                                    </div>

                                    {/* Infos */}
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-semibold">{player.full_name}</p>
                                        <div className="mt-1 flex items-center gap-1.5">
                                            {status ? (
                                                <Badge
                                                    variant={licenseVariant[status] ?? 'outline'}
                                                    className="text-xs"
                                                >
                                                    {licenseLabel[status] ?? status}
                                                    {license?.season ? ` — ${license.season}` : ''}
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-xs">
                                                    Aucune licence
                                                </Badge>
                                            )}
                                        </div>
                                    </div>

                                    <ChevronRight className="text-muted-foreground h-4 w-4 shrink-0" />
                                </div>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </>
    );
}
