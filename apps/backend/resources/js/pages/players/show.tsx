import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Check } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type License = {
    id: number;
    season: string;
    status: string;
    status_label: string;
    status_color: string;
    created_at: string;
};

type Payment = {
    id: number;
    amount: number;
    method: string;
    method_label: string;
    status: string;
    status_label: string;
    status_color: string;
    reference: string | null;
    created_at: string;
};

type Player = {
    id: number;
    full_name: string;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    has_valid_license: boolean;
    has_valid_payment: boolean;
    created_at: string;
};

type Props = {
    player: Player;
    licenses: License[];
    payments: Payment[];
};

const colorToVariant = (
    color: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    const map: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        success: 'default',
        info: 'secondary',
        warning: 'outline',
        danger: 'destructive',
    };
    return map[color] ?? 'outline';
};

const fmt = (amount: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);

export default function PlayersShow({ player, licenses, payments }: Props) {
    const handleValidatePayment = (paymentId: number) => {
        router.post(`/payments/${paymentId}/validate`, {}, { preserveScroll: true });
    };

    const handleValidateLicense = (licenseId: number) => {
        router.post(`/licenses/${licenseId}/validate`, {}, { preserveScroll: true });
    };

    const initials = player.full_name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();

    return (
        <>
            <Head title={player.full_name} />

            {/* Header sticky */}
            <div className="bg-background sticky top-14 z-10 border-b px-4 py-3">
                <div className="flex items-center gap-3">
                    <Link href="/players" className="text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="h-5 w-5" />
                    </Link>
                    <h1 className="truncate text-lg font-bold">{player.full_name}</h1>
                </div>
            </div>

            <div className="space-y-4 p-4">
                {/* Carte identité */}
                <div className="flex items-center gap-4 rounded-xl border p-4">
                    <div className="bg-primary text-primary-foreground flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-lg font-bold">
                        {initials}
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="font-semibold">{player.full_name}</p>
                        {player.email && (
                            <p className="text-muted-foreground truncate text-sm">{player.email}</p>
                        )}
                        {player.phone && (
                            <p className="text-muted-foreground text-sm">{player.phone}</p>
                        )}
                    </div>
                </div>

                {/* Statuts rapides */}
                <div className="grid grid-cols-2 gap-2">
                    <div className={`rounded-xl border p-3 text-center ${player.has_valid_license ? 'border-green-500 bg-green-50 dark:bg-green-950/20' : ''}`}>
                        <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Licence</p>
                        <p className={`mt-1 text-sm font-semibold ${player.has_valid_license ? 'text-green-600' : 'text-muted-foreground'}`}>
                            {player.has_valid_license ? '✓ Validée' : 'En attente'}
                        </p>
                    </div>
                    <div className={`rounded-xl border p-3 text-center ${player.has_valid_payment ? 'border-green-500 bg-green-50 dark:bg-green-950/20' : ''}`}>
                        <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Paiement</p>
                        <p className={`mt-1 text-sm font-semibold ${player.has_valid_payment ? 'text-green-600' : 'text-muted-foreground'}`}>
                            {player.has_valid_payment ? '✓ Validé' : 'En attente'}
                        </p>
                    </div>
                </div>

                {/* Licences */}
                {licenses.length > 0 && (
                    <div>
                        <h2 className="mb-2 px-1 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            Licences
                        </h2>
                        <div className="space-y-2">
                            {licenses.map((license) => (
                                <div
                                    key={license.id}
                                    className="flex items-center justify-between rounded-xl border p-4"
                                >
                                    <div>
                                        <p className="font-medium">Saison {license.season}</p>
                                        <p className="text-muted-foreground text-xs">{license.created_at}</p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge variant={colorToVariant(license.status_color)}>
                                            {license.status_label}
                                        </Badge>
                                        {license.status !== 'validated' && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => handleValidateLicense(license.id)}
                                            >
                                                <Check className="h-4 w-4" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Paiements */}
                {payments.length > 0 && (
                    <div>
                        <h2 className="mb-2 px-1 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            Paiements
                        </h2>
                        <div className="space-y-2">
                            {payments.map((payment) => (
                                <div
                                    key={payment.id}
                                    className="flex items-center justify-between rounded-xl border p-4"
                                >
                                    <div>
                                        <p className="font-medium">{fmt(payment.amount)}</p>
                                        <p className="text-muted-foreground text-xs">
                                            {payment.method_label || '—'} · {payment.created_at}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge variant={colorToVariant(payment.status_color)}>
                                            {payment.status_label}
                                        </Badge>
                                        {payment.status !== 'validated' && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => handleValidatePayment(payment.id)}
                                            >
                                                <Check className="h-4 w-4" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {licenses.length === 0 && payments.length === 0 && (
                    <div className="rounded-xl border border-dashed p-6 text-center">
                        <p className="text-muted-foreground text-sm">Aucune donnée pour ce joueur.</p>
                    </div>
                )}
            </div>
        </>
    );
}
