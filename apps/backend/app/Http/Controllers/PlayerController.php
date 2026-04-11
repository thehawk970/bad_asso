<?php

namespace App\Http\Controllers;

use App\Actions\ValidateLicense;
use App\Actions\ValidatePayment;
use App\Models\License;
use App\Models\Payment;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(): Response
    {
        $players = Player::with([
            'licenses' => fn ($q) => $q->latest()->limit(1),
            'payments' => fn ($q) => $q->latest()->limit(1),
        ])
            ->orderBy('last_name')
            ->get()
            ->map(fn (Player $player) => [
                'id'                => $player->id,
                'full_name'         => $player->full_name,
                'first_name'        => $player->first_name,
                'last_name'         => $player->last_name,
                'email'             => $player->email,
                'phone'             => $player->phone,
                'has_valid_license' => $player->has_valid_license,
                'has_valid_payment' => $player->has_valid_payment,
                'latest_license'    => $player->licenses->first()?->only(['status', 'season']),
                'latest_payment'    => $player->payments->first()?->only(['status', 'amount']),
            ]);

        return Inertia::render('players/index', compact('players'));
    }

    public function show(Player $player): Response
    {
        $player->load(['licenses', 'payments']);

        return Inertia::render('players/show', [
            'player' => [
                'id'                => $player->id,
                'full_name'         => $player->full_name,
                'first_name'        => $player->first_name,
                'last_name'         => $player->last_name,
                'email'             => $player->email,
                'phone'             => $player->phone,
                'has_valid_license' => $player->has_valid_license,
                'has_valid_payment' => $player->has_valid_payment,
                'created_at'        => $player->created_at->format('d/m/Y'),
            ],
            'licenses' => $player->licenses->map(fn (License $l) => [
                'id'         => $l->id,
                'season'     => $l->season,
                'status'     => $l->status->value,
                'status_label' => $l->status->label(),
                'status_color' => $l->status->color(),
                'created_at' => $l->created_at->format('d/m/Y'),
            ]),
            'payments' => $player->payments->map(fn (Payment $p) => [
                'id'           => $p->id,
                'amount'       => $p->amount,
                'method'       => $p->method->value,
                'method_label' => $p->method->label(),
                'status'       => $p->status->value,
                'status_label' => $p->status->label(),
                'status_color' => $p->status->color(),
                'reference'    => $p->reference,
                'created_at'   => $p->created_at->format('d/m/Y'),
            ]),
        ]);
    }

    public function validatePayment(Payment $payment, ValidatePayment $action): RedirectResponse
    {
        $action->execute($payment);

        return back()->with('success', 'Paiement validé.');
    }

    public function validateLicense(License $license, ValidateLicense $action): RedirectResponse
    {
        $action->execute($license);

        return back()->with('success', 'Licence validée.');
    }
}
