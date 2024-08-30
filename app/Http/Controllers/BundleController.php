<?php

namespace App\Http\Controllers;

use App\Models\Direction;

class BundleController extends Controller
{
    public function index()
    {
        $bundles = Direction::query()
            ->with([
                'askCurrency' => fn($query) => $query->select('name', 'id'),
                'bidCurrency' => fn($query) => $query->select('name', 'id'),
                'stockMarket' => fn($query) => $query->select('name', 'id'),
            ])
            ->get()
            ->groupBy(function ($item) {
                return $item->bid_currency_id . '-' . $item->ask_currency_id;
            })
            ->filter(fn($item) => $item->count() >= 2)
            ->map(function ($item) {
                return $item->map(function ($from_direction) use ($item) {
                    return $item->map(function ($to_direction) use ($from_direction) {
                        if ($from_direction->id === $to_direction->id) return null;

                        return [
                            'from_direction' => $from_direction,
                            'to_direction' => $to_direction,

                            'spread' => ($to_direction->sell_price / $from_direction->buy_price - 1) * 100
                        ];
                    });
                })
                    ->flatten(1)
                    ->filter(fn($direction) => $direction != null);
            })
            ->flatten(1)
            ->filter(fn ($value) => $value['spread'] >= 0)
            ->sortByDesc('spread')
            ->values();

        return $bundles;
    }
}
