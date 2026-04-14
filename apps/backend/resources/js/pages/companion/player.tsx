import { Head, Link } from '@inertiajs/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ArrowLeft, Check, FileText, Heart, ScrollText, ShieldCheck } from 'lucide-react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { companionApi, type LicenseData, type LicenseConditions } from '@/lib/companion-api';

// ─── Types ────────────────────────────────────────────────────────────────────

type PlayerData = {
    id: number;
    full_name: string;
    first_name: string;
    last_name: string;
    ffbad_license_number: string | null;
    ffbad_category: string | null;
    birth_date: string | null;
    email: string | null;
    phone: string | null;
};

type Props = {
    player: PlayerData;
    license: LicenseData | null;
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function statusVariant(status: LicenseData['status']) {
    if (status === 'validated') return 'default';
    if (status === 'in_progress') return 'secondary';
    return 'outline';
}

function statusLabel(status: LicenseData['status']) {
    if (status === 'validated') return 'Validée';
    if (status === 'in_progress') return 'En cours';
    return 'En attente';
}

function getInitials(fullName: string) {
    return fullName
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
}

// ─── Composant de condition ────────────────────────────────────────────────────

type ConditionRowProps = {
    icon: React.ReactNode;
    label: string;
    checked: boolean;
    readOnly?: boolean;
    onToggle?: () => void;
    isPending?: boolean;
};

function ConditionRow({ icon, label, checked, readOnly = false, onToggle, isPending }: ConditionRowProps) {
    return (
        <button
            onClick={readOnly ? undefined : onToggle}
            disabled={readOnly || isPending}
            className={cn(
                'flex w-full items-center gap-3 rounded-xl border-2 p-4 text-left transition-all',
                checked ? 'border-green-500 bg-green-50 dark:bg-green-950/20' : 'border-border bg-card',
                !readOnly && 'active:scale-95',
                readOnly && 'cursor-default opacity-80',
                isPending && 'opacity-60',
            )}
        >
            <div className={cn('text-muted-foreground', checked && 'text-green-600')}>{icon}</div>
            <span className="flex-1 text-sm font-medium">{label}</span>
            <div
                className={cn(
                    'flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 transition-all',
                    checked ? 'border-green-500 bg-green-500' : 'border-muted-foreground/40',
                    readOnly && checked && 'border-green-400 bg-green-400',
                )}
            >
                {checked && <Check className="h-3.5 w-3.5 text-white" />}
            </div>
        </button>
    );
}

// ─── Page principale ──────────────────────────────────────────────────────────

export default function CompanionPlayer({ player: initialPlayer, license: initialLicense }: Props) {
    const queryClient = useQueryClient();

    const queryKey = ['companion', 'player', initialPlayer.id] as const;

    // TanStack Query — hydraté par les props Inertia, refetch après mutation
    const { data } = useQuery({
        queryKey,
        queryFn: () => companionApi.getPlayer(initialPlayer.id),
        initialData: { player: initialPlayer, license: initialLicense },
        staleTime: 30_000,
    });

    const license = data.license;

    const { mutate: updateCondition, isPending } = useMutation({
        mutationFn: ({ field, value }: { field: keyof LicenseConditions; value: boolean }) =>
            companionApi.updateLicenseConditions(license!.id, { [field]: value }),
        onSuccess: (updatedLicense) => {
            // Mise à jour optimiste dans le cache
            queryClient.setQueryData(queryKey, (old: typeof data) => ({
                ...old,
                license: updatedLicense,
            }));

            if (updatedLicense.status === 'validated') {
                toast.success('Licence validée !');
            }
        },
        onError: (err: Error) => {
            toast.error(err.message ?? 'Erreur lors de la mise à jour');
            // Refetch pour annuler l'optimisme en cas d'erreur
            void queryClient.invalidateQueries({ queryKey });
        },
    });

    const toggle = (field: keyof LicenseConditions) => {
        if (!license) return;
        updateCondition({ field, value: !license[field] });
    };

    return (
        <>
            <Head title={data.player.full_name} />

            <div className="flex flex-col">
                {/* En-tête */}
                <div className="bg-background sticky top-14 z-10 border-b px-4 pb-4 pt-4">
                    <div className="mb-3 flex items-center gap-2">
                        <Link
                            href="/companion/order"
                            className="text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <span className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                            Fiche joueur
                        </span>
                    </div>

                    <div className="flex items-center gap-4">
                        <div className="bg-primary text-primary-foreground flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-lg font-bold">
                            {getInitials(data.player.full_name)}
                        </div>
                        <div>
                            <h1 className="text-xl font-bold leading-tight">{data.player.full_name}</h1>
                            {data.player.ffbad_category && (
                                <p className="text-muted-foreground text-sm">
                                    {data.player.ffbad_category}
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                {/* Contenu */}
                <div className="space-y-5 p-4">
                    {/* Infos */}
                    <div className="rounded-xl border p-4">
                        <h2 className="mb-3 text-sm font-semibold">Informations</h2>
                        <dl className="space-y-2 text-sm">
                            {data.player.ffbad_license_number && (
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Licence FFBad</dt>
                                    <dd className="font-medium font-mono">{data.player.ffbad_license_number}</dd>
                                </div>
                            )}
                            {data.player.birth_date && (
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Naissance</dt>
                                    <dd className="font-medium">{data.player.birth_date}</dd>
                                </div>
                            )}
                            {data.player.email && (
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground shrink-0">Email</dt>
                                    <dd className="truncate font-medium">{data.player.email}</dd>
                                </div>
                            )}
                            {data.player.phone && (
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Téléphone</dt>
                                    <dd className="font-medium">{data.player.phone}</dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    {/* Licence */}
                    {!license ? (
                        <div className="rounded-xl border border-dashed p-6 text-center">
                            <p className="text-muted-foreground text-sm">Aucune licence pour la saison en cours.</p>
                        </div>
                    ) : (
                        <div>
                            <div className="mb-3 flex items-center justify-between">
                                <h2 className="text-sm font-semibold">Licence {license.season}</h2>
                                <Badge variant={statusVariant(license.status)}>
                                    {statusLabel(license.status)}
                                </Badge>
                            </div>

                            <div className="space-y-2">
                                <ConditionRow
                                    icon={<ShieldCheck className="h-5 w-5" />}
                                    label="Paiement confirmé"
                                    checked={license.payment_confirmed}
                                    readOnly
                                />
                                <ConditionRow
                                    icon={<Heart className="h-5 w-5" />}
                                    label="Fiche santé remise"
                                    checked={license.health_form_filled}
                                    onToggle={() => toggle('health_form_filled')}
                                    isPending={isPending}
                                />
                                <ConditionRow
                                    icon={<FileText className="h-5 w-5" />}
                                    label="Fiche d'inscription remise"
                                    checked={license.info_form_filled}
                                    onToggle={() => toggle('info_form_filled')}
                                    isPending={isPending}
                                />
                                <ConditionRow
                                    icon={<ScrollText className="h-5 w-5" />}
                                    label="Règlement signé"
                                    checked={license.rules_signed}
                                    onToggle={() => toggle('rules_signed')}
                                    isPending={isPending}
                                />
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
