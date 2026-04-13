import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import { ArrowLeft, Check, Minus, Plus, ShoppingCart } from 'lucide-react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { companionApi, type CreateOrderPayload, type CreatedOrder } from '@/lib/companion-api';

// ─── Types ────────────────────────────────────────────────────────────────────

type CompanionPlayer = {
    id: number;
    full_name: string;
    ffbad_category: string | null;
    license_status: 'validated' | 'in_progress' | 'pending' | null;
};

type CompanionProduct = {
    id: number;
    name: string;
    price: number;
    description: string | null;
    is_license_product: boolean;
};

type PaymentMethodOption = { value: string; label: string };

type CartItem = { product: CompanionProduct; quantity: number };

type Props = {
    players: CompanionPlayer[];
    products: CompanionProduct[];
    paymentMethods: PaymentMethodOption[];
};

type Step = 'player' | 'products' | 'payment' | 'success';

// ─── Helpers ─────────────────────────────────────────────────────────────────

const fmt = (amount: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);

function licenseVariant(status: CompanionPlayer['license_status']) {
    if (status === 'validated') return 'default';
    if (status === 'in_progress') return 'secondary';
    return 'outline';
}

function licenseLabel(status: CompanionPlayer['license_status']) {
    if (status === 'validated') return 'Validée';
    if (status === 'in_progress') return 'En cours';
    if (status === 'pending') return 'En attente';
    return 'Aucune';
}

// ─── Étape 1 : Sélection joueur ───────────────────────────────────────────────

function PlayerStep({
    players,
    onSelect,
}: {
    players: CompanionPlayer[];
    onSelect: (p: CompanionPlayer) => void;
}) {
    const [search, setSearch] = useState('');
    const filtered = players.filter((p) =>
        p.full_name.toLowerCase().includes(search.toLowerCase()),
    );

    return (
        <div className="flex h-screen flex-col">
            <div className="bg-background sticky top-0 z-10 border-b px-4 pb-3 pt-4">
                <p className="text-muted-foreground mb-2 text-xs font-medium uppercase tracking-wide">
                    Étape 1 / 3
                </p>
                <h1 className="mb-3 text-xl font-bold">Sélectionner un joueur</h1>
                <Input
                    placeholder="Rechercher..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="h-11"
                    autoComplete="off"
                />
            </div>

            <div className="flex-1 overflow-y-auto p-4">
                {filtered.length === 0 && (
                    <p className="text-muted-foreground py-12 text-center text-sm">Aucun joueur trouvé.</p>
                )}
                <div className="grid grid-cols-2 gap-3">
                    {filtered.map((player) => (
                        <button
                            key={player.id}
                            onClick={() => onSelect(player)}
                            className={cn(
                                'flex min-h-[5.5rem] flex-col items-start justify-between rounded-xl border-2 p-3',
                                'text-left transition-all active:scale-95',
                                'border-border bg-card hover:border-primary/60 hover:bg-primary/5',
                            )}
                        >
                            <span className="line-clamp-2 text-sm font-semibold leading-tight">
                                {player.full_name}
                            </span>
                            <div className="mt-2 flex flex-col gap-1">
                                {player.ffbad_category && (
                                    <span className="text-muted-foreground text-xs">
                                        {player.ffbad_category}
                                    </span>
                                )}
                                <Badge variant={licenseVariant(player.license_status)} className="text-xs">
                                    {licenseLabel(player.license_status)}
                                </Badge>
                            </div>
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}

// ─── Étape 2 : Sélection produits ─────────────────────────────────────────────

function ProductsStep({
    player,
    products,
    cart,
    onAdd,
    onRemove,
    onBack,
    onContinue,
}: {
    player: CompanionPlayer;
    products: CompanionProduct[];
    cart: Map<number, CartItem>;
    onAdd: (p: CompanionProduct) => void;
    onRemove: (id: number) => void;
    onBack: () => void;
    onContinue: () => void;
}) {
    const totalItems = [...cart.values()].reduce((s, i) => s + i.quantity, 0);
    const totalPrice = [...cart.values()].reduce((s, i) => s + i.product.price * i.quantity, 0);

    return (
        <div className="flex h-screen flex-col">
            <div className="bg-background sticky top-0 z-10 border-b px-4 pb-3 pt-4">
                <div className="mb-2 flex items-center gap-2">
                    <button onClick={onBack} className="text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="h-5 w-5" />
                    </button>
                    <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                        Étape 2 / 3
                    </p>
                </div>
                <h1 className="text-xl font-bold">{player.full_name}</h1>
                {player.ffbad_category && (
                    <p className="text-muted-foreground text-sm">{player.ffbad_category}</p>
                )}
            </div>

            <div className="flex-1 overflow-y-auto p-4 pb-28">
                <div className="grid grid-cols-2 gap-3">
                    {products.map((product) => {
                        const item = cart.get(product.id);
                        const qty = item?.quantity ?? 0;

                        return (
                            <div
                                key={product.id}
                                className={cn(
                                    'relative flex min-h-[7rem] flex-col rounded-xl border-2 p-3 transition-all',
                                    qty > 0
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border bg-card',
                                )}
                            >
                                {qty > 0 && (
                                    <span className="bg-primary text-primary-foreground absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold">
                                        {qty}
                                    </span>
                                )}
                                <span className="pr-7 text-sm font-semibold leading-tight">
                                    {product.name}
                                </span>
                                <span className="text-muted-foreground mt-1 text-xs">
                                    {fmt(product.price)}
                                </span>
                                <div className="mt-auto flex items-center gap-1 pt-2">
                                    {qty > 0 ? (
                                        <>
                                            <button
                                                onClick={() => onRemove(product.id)}
                                                className="bg-muted hover:bg-muted/80 flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                                            >
                                                <Minus className="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                onClick={() => onAdd(product)}
                                                className="bg-primary text-primary-foreground hover:bg-primary/90 flex h-8 flex-1 items-center justify-center gap-1 rounded-lg text-xs font-medium transition-colors"
                                            >
                                                <Plus className="h-3.5 w-3.5" />
                                                Ajouter
                                            </button>
                                        </>
                                    ) : (
                                        <button
                                            onClick={() => onAdd(product)}
                                            className="bg-secondary hover:bg-secondary/80 flex h-8 w-full items-center justify-center gap-1 rounded-lg text-xs font-medium transition-colors"
                                        >
                                            <Plus className="h-3.5 w-3.5" />
                                            Ajouter
                                        </button>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Barre de panier fixe */}
            <div className="bg-background fixed bottom-0 left-0 right-0 border-t px-4 py-3 shadow-lg">
                <div className="flex items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <ShoppingCart className="text-muted-foreground h-5 w-5" />
                        <div>
                            <p className="text-sm font-semibold">
                                {totalItems === 0
                                    ? 'Panier vide'
                                    : `${totalItems} article${totalItems > 1 ? 's' : ''}`}
                            </p>
                            {totalItems > 0 && (
                                <p className="text-muted-foreground text-xs">{fmt(totalPrice)}</p>
                            )}
                        </div>
                    </div>
                    <Button onClick={onContinue} disabled={totalItems === 0} className="px-6">
                        Continuer →
                    </Button>
                </div>
            </div>
        </div>
    );
}

// ─── Étape 3 : Paiement ───────────────────────────────────────────────────────

function PaymentStep({
    player,
    cart,
    paymentMethods,
    onBack,
    onConfirm,
    isPending,
}: {
    player: CompanionPlayer;
    cart: Map<number, CartItem>;
    paymentMethods: PaymentMethodOption[];
    onBack: () => void;
    onConfirm: (method: string, isPickedUp: boolean) => void;
    isPending: boolean;
}) {
    const [selectedMethod, setSelectedMethod] = useState<string | null>(null);
    const [isPickedUp, setIsPickedUp] = useState(false);

    const totalPrice = [...cart.values()].reduce((s, i) => s + i.product.price * i.quantity, 0);
    const items = [...cart.values()];

    return (
        <div className="flex h-screen flex-col">
            <div className="bg-background sticky top-0 z-10 border-b px-4 pb-3 pt-4">
                <div className="mb-2 flex items-center gap-2">
                    <button onClick={onBack} className="text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="h-5 w-5" />
                    </button>
                    <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                        Étape 3 / 3
                    </p>
                </div>
                <h1 className="text-xl font-bold">Règlement</h1>
                <p className="text-muted-foreground text-sm">{player.full_name}</p>
            </div>

            <div className="flex-1 overflow-y-auto space-y-5 p-4 pb-6">
                {/* Récapitulatif panier */}
                <div className="rounded-xl border p-4">
                    <h2 className="mb-3 text-sm font-semibold">Récapitulatif</h2>
                    <div className="space-y-2">
                        {items.map(({ product, quantity }) => (
                            <div key={product.id} className="flex justify-between text-sm">
                                <span>
                                    {product.name}{' '}
                                    <span className="text-muted-foreground">× {quantity}</span>
                                </span>
                                <span className="font-medium">{fmt(product.price * quantity)}</span>
                            </div>
                        ))}
                    </div>
                    <div className="mt-3 flex justify-between border-t pt-3 font-bold">
                        <span>Total</span>
                        <span>{fmt(totalPrice)}</span>
                    </div>
                </div>

                {/* Méthode de règlement */}
                <div>
                    <h2 className="mb-3 text-sm font-semibold">Méthode de règlement</h2>
                    <div className="grid grid-cols-2 gap-2">
                        {paymentMethods.map((method) => (
                            <button
                                key={method.value}
                                onClick={() => setSelectedMethod(method.value)}
                                className={cn(
                                    'flex min-h-[3.5rem] items-center justify-center rounded-xl border-2 px-3 py-2 text-sm font-medium transition-all active:scale-95',
                                    selectedMethod === method.value
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border bg-card hover:border-primary/60',
                                )}
                            >
                                {method.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Produit remis */}
                <button
                    onClick={() => setIsPickedUp((v) => !v)}
                    className={cn(
                        'flex w-full items-center justify-between rounded-xl border-2 p-4 transition-all',
                        isPickedUp
                            ? 'border-green-500 bg-green-50 dark:bg-green-950/20'
                            : 'border-border bg-card',
                    )}
                >
                    <span className="text-sm font-medium">Produit remis au joueur</span>
                    <div
                        className={cn(
                            'flex h-6 w-6 items-center justify-center rounded-full border-2 transition-all',
                            isPickedUp ? 'border-green-500 bg-green-500' : 'border-muted-foreground',
                        )}
                    >
                        {isPickedUp && <Check className="h-3.5 w-3.5 text-white" />}
                    </div>
                </button>

                <Button
                    className="h-14 w-full text-base font-semibold"
                    disabled={!selectedMethod || isPending}
                    onClick={() => selectedMethod && onConfirm(selectedMethod, isPickedUp)}
                >
                    {isPending ? 'Création en cours…' : 'Confirmer la commande'}
                </Button>
            </div>
        </div>
    );
}

// ─── Écran de succès ──────────────────────────────────────────────────────────

function SuccessStep({
    order,
    onNewOrder,
}: {
    order: CreatedOrder;
    onNewOrder: () => void;
}) {
    return (
        <div className="flex h-screen flex-col items-center justify-center gap-6 p-6 text-center">
            <div className="flex h-20 w-20 items-center justify-center rounded-full bg-green-100 dark:bg-green-950/40">
                <Check className="h-10 w-10 text-green-600" />
            </div>
            <div>
                <h1 className="text-2xl font-bold">Commande créée !</h1>
                <p className="text-muted-foreground mt-1 text-sm">{order.player_name}</p>
            </div>
            <div className="w-full max-w-xs rounded-xl border p-4 text-left">
                {order.items.map((item, i) => (
                    <div key={i} className="flex justify-between py-1 text-sm">
                        <span>
                            {item.name} <span className="text-muted-foreground">× {item.quantity}</span>
                        </span>
                        <span>{fmt(item.price * item.quantity)}</span>
                    </div>
                ))}
                <div className="mt-2 flex justify-between border-t pt-2 font-bold">
                    <span>Total</span>
                    <span>{fmt(order.total)}</span>
                </div>
                {order.is_picked_up && (
                    <div className="mt-2 flex items-center gap-1 text-green-600 text-xs">
                        <Check className="h-3.5 w-3.5" />
                        Produit remis
                    </div>
                )}
            </div>
            <Button onClick={onNewOrder} className="w-full max-w-xs h-12 text-base">
                Nouvelle commande
            </Button>
        </div>
    );
}

// ─── Page principale ──────────────────────────────────────────────────────────

export default function CompanionOrder({ players, products, paymentMethods }: Props) {
    const [step, setStep] = useState<Step>('player');
    const [selectedPlayer, setSelectedPlayer] = useState<CompanionPlayer | null>(null);
    const [cart, setCart] = useState<Map<number, CartItem>>(new Map());
    const [createdOrder, setCreatedOrder] = useState<CreatedOrder | null>(null);

    const { mutate: createOrder, isPending } = useMutation({
        mutationFn: (payload: CreateOrderPayload) => companionApi.createOrder(payload),
        onSuccess: (order) => {
            setCreatedOrder(order);
            setStep('success');
        },
        onError: (err: Error) => {
            toast.error(err.message ?? 'Erreur lors de la création de la commande');
        },
    });

    const addToCart = (product: CompanionProduct) => {
        setCart((prev) => {
            const next = new Map(prev);
            const current = next.get(product.id);
            next.set(product.id, { product, quantity: (current?.quantity ?? 0) + 1 });
            return next;
        });
    };

    const removeFromCart = (productId: number) => {
        setCart((prev) => {
            const next = new Map(prev);
            const current = next.get(productId);
            if (!current || current.quantity <= 1) {
                next.delete(productId);
            } else {
                next.set(productId, { ...current, quantity: current.quantity - 1 });
            }
            return next;
        });
    };

    const handleConfirm = (paymentMethod: string, isPickedUp: boolean) => {
        if (!selectedPlayer) return;

        createOrder({
            player_id: selectedPlayer.id,
            items: [...cart.values()].map((i) => ({
                product_id: i.product.id,
                quantity: i.quantity,
            })),
            payment_method: paymentMethod,
            is_picked_up: isPickedUp,
        });
    };

    const resetWizard = () => {
        setStep('player');
        setSelectedPlayer(null);
        setCart(new Map());
        setCreatedOrder(null);
    };

    return (
        <>
            <Head title="Commande rapide" />

            {step === 'player' && (
                <PlayerStep
                    players={players}
                    onSelect={(p) => {
                        setSelectedPlayer(p);
                        setCart(new Map());
                        setStep('products');
                    }}
                />
            )}

            {step === 'products' && selectedPlayer && (
                <ProductsStep
                    player={selectedPlayer}
                    products={products}
                    cart={cart}
                    onAdd={addToCart}
                    onRemove={removeFromCart}
                    onBack={() => setStep('player')}
                    onContinue={() => setStep('payment')}
                />
            )}

            {step === 'payment' && selectedPlayer && (
                <PaymentStep
                    player={selectedPlayer}
                    cart={cart}
                    paymentMethods={paymentMethods}
                    onBack={() => setStep('products')}
                    onConfirm={handleConfirm}
                    isPending={isPending}
                />
            )}

            {step === 'success' && createdOrder && (
                <SuccessStep order={createdOrder} onNewOrder={resetWizard} />
            )}
        </>
    );
}
