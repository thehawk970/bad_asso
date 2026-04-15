<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\License;
use App\Models\Order;
use App\Models\Player;
use App\Models\Product;
use App\Models\Season;
use App\Services\LicenseService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CompanionController extends Controller
{
    // ─── Pages Inertia ──────────────────────────────────────────────────────────

    public function showOrderWizard(Request $request): Response
    {
        $players = Player::orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Player $p) => [
                'id'             => $p->id,
                'full_name'      => $p->full_name,
                'ffbad_category' => $p->ffbad_category,
                'license_status' => $p->licenses()->latest()->first()?->status->value,
            ]);

        $products = Product::active()
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id'                 => $p->id,
                'name'               => $p->name,
                'price'              => (float) $p->price,
                'description'        => $p->description,
                'is_license_product' => $p->is_license_product,
            ]);

        $paymentMethods = collect(PaymentMethod::cases())
            ->map(fn (PaymentMethod $m) => ['value' => $m->value, 'label' => $m->label()]);

        $initialPlayer = null;
        if ($request->filled('player_id')) {
            $player = Player::find($request->integer('player_id'));
            if ($player) {
                $initialPlayer = [
                    'id'             => $player->id,
                    'full_name'      => $player->full_name,
                    'ffbad_category' => $player->ffbad_category,
                    'license_status' => $player->licenses()->latest()->first()?->status->value,
                ];
            }
        }

        return Inertia::render('companion/order', compact('players', 'products', 'paymentMethods', 'initialPlayer'));
    }

    public function showPlayer(Player $player): Response
    {
        $currentSeason = Season::current();

        $license = $currentSeason
            ? $player->licenses()->where('season_id', $currentSeason->id)->first()
            : null;

        return Inertia::render('companion/player', [
            'player'  => [
                'id'                   => $player->id,
                'full_name'            => $player->full_name,
                'first_name'           => $player->first_name,
                'last_name'            => $player->last_name,
                'ffbad_license_number' => $player->ffbad_license_number,
                'ffbad_category'       => $player->ffbad_category,
                'birth_date'           => $player->birth_date?->format('d/m/Y'),
                'email'                => $player->email,
                'phone'                => $player->phone,
            ],
            'license' => $license ? $this->formatLicense($license) : null,
        ]);
    }

    // ─── API JSON ────────────────────────────────────────────────────────────────

    public function createOrder(Request $request, OrderService $orderService, LicenseService $licenseService): JsonResponse
    {
        $validated = $request->validate([
            'player_id'      => ['required', 'integer', 'exists:players,id'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'string', 'in:'.implode(',', array_column(PaymentMethod::cases(), 'value'))],
            'is_picked_up'   => ['boolean'],
        ]);

        $order = DB::transaction(function () use ($validated, $orderService, $licenseService): Order {
            /** @var Order $order */
            $order = Order::create([
                'player_id' => $validated['player_id'],
                'status'    => OrderStatus::Pending,
                'total'     => 0,
                'is_picked_up' => $validated['is_picked_up'] ?? false,
            ]);

            foreach ($validated['items'] as $item) {
                /** @var Product $product */
                $product = Product::findOrFail($item['product_id']);
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                ]);
            }

            $orderService->handleAfterCreate($order, $licenseService);

            if (isset($validated['payment_method'])) {
                $orderService->markAsPaid($order, PaymentMethod::from($validated['payment_method']));
            }

            /** @var Order $fresh */
            $fresh = $order->fresh(['player', 'items.product']);

            return $fresh;
        });

        return response()->json([
            'id'           => $order->id,
            'total'        => (float) $order->total,
            'is_picked_up' => $order->is_picked_up,
            'player_name'  => $order->player?->full_name,
            'items'        => $order->items->map(fn ($i) => [
                'name'     => $i->product?->name,
                'quantity' => $i->quantity,
                'price'    => (float) $i->unit_price,
            ]),
        ], 201);
    }

    public function getPlayer(Player $player): JsonResponse
    {
        $currentSeason = Season::current();
        $license = $currentSeason
            ? $player->licenses()->where('season_id', $currentSeason->id)->first()
            : null;

        return response()->json([
            'player'  => [
                'id'                   => $player->id,
                'full_name'            => $player->full_name,
                'first_name'           => $player->first_name,
                'last_name'            => $player->last_name,
                'ffbad_license_number' => $player->ffbad_license_number,
                'ffbad_category'       => $player->ffbad_category,
                'birth_date'           => $player->birth_date?->format('d/m/Y'),
                'email'                => $player->email,
                'phone'                => $player->phone,
            ],
            'license' => $license ? $this->formatLicense($license) : null,
        ]);
    }

    public function updateLicenseConditions(License $license, Request $request, LicenseService $licenseService): JsonResponse
    {
        $conditions = $request->validate([
            'health_form_filled' => ['sometimes', 'boolean'],
            'info_form_filled'   => ['sometimes', 'boolean'],
            'rules_signed'       => ['sometimes', 'boolean'],
        ]);

        $licenseService->updateConditionsAndValidate($license, $conditions);

        /** @var License $fresh */
        $fresh = $license->fresh();

        return response()->json($this->formatLicense($fresh));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function formatLicense(License $license): array
    {
        return [
            'id'                 => $license->id,
            'season'             => $license->season->name ?? '—',
            'status'             => $license->status->value,
            'payment_confirmed'  => $license->payment_confirmed,
            'health_form_filled' => $license->health_form_filled,
            'info_form_filled'   => $license->info_form_filled,
            'rules_signed'       => $license->rules_signed,
        ];
    }
}
