<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $pendingOrders = Order::pending()
            ->with(['player', 'items.product', 'payments'])
            ->latest()
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'player_name' => $order->player->full_name,
                'player_id' => $order->player_id,
                'total' => (float) $order->total,
                'amount_paid' => $order->amount_paid,
                'remaining' => $order->remaining_amount,
                'items_count' => $order->items->count(),
                'items_summary' => $order->items
                    ->map(fn (OrderItem $i) => $i->product?->name . ($i->quantity > 1 ? ' ×' . $i->quantity : ''))
                    ->join(', '),
                'created_at' => $order->created_at?->format('d/m/Y') ?? '',
            ]);

        return Inertia::render('old/welcome', compact('pendingOrders'));
    }
}
