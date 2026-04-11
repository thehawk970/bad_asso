import { Head, Link, router } from '@inertiajs/react';
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

const formatMoney = (amount: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);

export default function PlayersShow({ player, licenses, payments }: Props) {
    const handleValidatePayment = (paymentId: number) => {
        router.post(`/payments/${paymentId}/validate`, {}, { preserveScroll: true });
    };

    const handleValidateLicense = (licenseId: number) => {
        router.post(`/licenses/${licenseId}/validate`, {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title={player.full_name} />

            <div className="space-y-8 p-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <Button variant="ghost" size="sm" asChild className="mb-2 -ml-2">
                            <Link href="/players">← Retour</Link>
                        </Button>
                        <h1 className="text-2xl font-bold">{player.full_name}</h1>
                        <p className="text-muted-foreground text-sm">Inscrit le {player.created_at}</p>
                    </div>
                    <div className="flex gap-2">
                        <Badge variant={player.has_valid_license ? 'default' : 'outline'}>
                            {player.has_valid_license ? 'Licence OK' : 'Sans licence valide'}
                        </Badge>
                        <Badge variant={player.has_valid_payment ? 'default' : 'outline'}>
                            {player.has_valid_payment ? 'Paiement OK' : 'Paiement en attente'}
                        </Badge>
                    </div>
                </div>

                {/* Informations */}
                <section className="rounded-lg border p-4">
                    <h2 className="mb-4 text-base font-semibold">Informations</h2>
                    <dl className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt className="text-muted-foreground">Email</dt>
                            <dd>{player.email ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Téléphone</dt>
                            <dd>{player.phone ?? '—'}</dd>
                        </div>
                    </dl>
                </section>

                {/* Licences */}
                <section className="rounded-lg border">
                    <div className="border-b px-4 py-3">
                        <h2 className="text-base font-semibold">Licences</h2>
                    </div>
                    {licenses.length === 0 ? (
                        <p className="text-muted-foreground px-4 py-6 text-sm">Aucune licence enregistrée.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-2 text-left font-medium">Saison</th>
                                    <th className="px-4 py-2 text-left font-medium">Statut</th>
                                    <th className="px-4 py-2 text-left font-medium">Date</th>
                                    <th className="px-4 py-2 text-left font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {licenses.map((license) => (
                                    <tr key={license.id} className="border-b last:border-0">
                                        <td className="px-4 py-3 font-medium">{license.season}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant={colorToVariant(license.status_color)}>
                                                {license.status_label}
                                            </Badge>
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">{license.created_at}</td>
                                        <td className="px-4 py-3">
                                            {license.status !== 'validated' && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => handleValidateLicense(license.id)}
                                                >
                                                    Valider
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </section>

                {/* Paiements */}
                <section className="rounded-lg border">
                    <div className="border-b px-4 py-3">
                        <h2 className="text-base font-semibold">Paiements</h2>
                    </div>
                    {payments.length === 0 ? (
                        <p className="text-muted-foreground px-4 py-6 text-sm">Aucun paiement enregistré.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-2 text-left font-medium">Montant</th>
                                    <th className="px-4 py-2 text-left font-medium">Méthode</th>
                                    <th className="px-4 py-2 text-left font-medium">Statut</th>
                                    <th className="px-4 py-2 text-left font-medium">Référence</th>
                                    <th className="px-4 py-2 text-left font-medium">Date</th>
                                    <th className="px-4 py-2 text-left font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {payments.map((payment) => (
                                    <tr key={payment.id} className="border-b last:border-0">
                                        <td className="px-4 py-3 font-medium">{formatMoney(payment.amount)}</td>
                                        <td className="text-muted-foreground px-4 py-3">{payment.method_label}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant={colorToVariant(payment.status_color)}>
                                                {payment.status_label}
                                            </Badge>
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">
                                            {payment.reference ?? '—'}
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">{payment.created_at}</td>
                                        <td className="px-4 py-3">
                                            {payment.status !== 'validated' && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => handleValidatePayment(payment.id)}
                                                >
                                                    Valider
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </section>
            </div>
        </>
    );
}
