/**
 * Helpers pour les appels API du companion mobile.
 * Utilise fetch() avec le token CSRF de Laravel (cookie XSRF-TOKEN).
 */

function getCsrf(): string {
    return decodeURIComponent(
        document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );
}

async function apiFetch<T>(url: string, options: RequestInit = {}): Promise<T> {
    const res = await fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': getCsrf(),
            ...(options.headers ?? {}),
        },
    });

    if (!res.ok) {
        const body = (await res.json().catch(() => ({}))) as { message?: string };
        throw new Error(body.message ?? `Erreur HTTP ${res.status}`);
    }

    return res.json() as Promise<T>;
}

// ─── Types ───────────────────────────────────────────────────────────────────

export type CreateOrderPayload = {
    player_id: number;
    items: Array<{ product_id: number; quantity: number }>;
    payment_method: string | null;
    is_picked_up: boolean;
};

export type CreatedOrder = {
    id: number;
    total: number;
    is_picked_up: boolean;
    player_name: string;
    items: Array<{ name: string; quantity: number; price: number }>;
};

export type LicenseData = {
    id: number;
    season: string;
    status: 'validated' | 'in_progress' | 'pending';
    payment_confirmed: boolean;
    health_form_filled: boolean;
    info_form_filled: boolean;
    rules_signed: boolean;
};

export type LicenseConditions = Pick<LicenseData, 'health_form_filled' | 'info_form_filled' | 'rules_signed'>;

export type PlayerApiData = {
    player: {
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
    license: LicenseData | null;
};

// ─── API ─────────────────────────────────────────────────────────────────────

export const companionApi = {
    createOrder: (data: CreateOrderPayload) =>
        apiFetch<CreatedOrder>('/companion/api/orders', {
            method: 'POST',
            body: JSON.stringify(data),
        }),

    getPlayer: (playerId: number) =>
        apiFetch<PlayerApiData>(`/companion/api/players/${playerId}`),

    updateLicenseConditions: (licenseId: number, conditions: Partial<LicenseConditions>) =>
        apiFetch<LicenseData>(`/companion/api/licenses/${licenseId}/conditions`, {
            method: 'PATCH',
            body: JSON.stringify(conditions),
        }),
};
