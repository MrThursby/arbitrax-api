<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class BundleController extends Controller
{
    public function index()
    {
        if ($bundles = Cache::get('bundles')) {
            return $bundles;
        } 
        
        $directions = Direction::query()->get();
        $bundles = $this->groupDirections($directions)
                        // ->filter(fn($item) => $item->count() >= 2)
                        ->flatMap(fn($item) => $this->createBundles($item))
                        ->filter(fn($direction) => $direction != null);

        $directionIds = $this->getDirectionIds($bundles);
        $directionsWithRelations = $this->loadDirectionRelations($directionIds);

        $bundlesWithRelations = $this->createBundlesWithRelations($bundles, $directionsWithRelations)
            ->filter(fn($bundle) => $bundle['spread'] >= 0.01 && $bundle['spread'] < 50)
            ->sortByDesc('spread')
            ->values();

        Cache::set('bundles', $bundlesWithRelations);

        return $bundlesWithRelations;
    }

    private function groupDirections($directions)
    {
        return $directions->groupBy(function ($item) {
            return collect([$item->bid_currency_id, $item->ask_currency_id])
                // ->sort()
                ->implode("-");
        });
    }

    private function createBundles($item)
    {
        return $item->flatMap(function ($from_direction) use ($item) {
            return $item->map(function ($to_direction) use ($from_direction) {
                if ($from_direction->id === $to_direction->id) return null;

                return [
                    'from_direction' => $from_direction,
                    'to_direction' => $to_direction,
                    'spread' => ($to_direction->sell_price / $from_direction->buy_price - 1) * 100,
                ];
            });
        });
    }

    private function getDirectionIds($bundles)
    {
        return $bundles->pluck('from_direction.id')
                    ->merge($bundles->pluck('to_direction.id'))
                    ->unique();
    }

    private function loadDirectionRelations($directionIds)
    {
        return Direction::with([
            'askCurrency:id,name',
            'bidCurrency:id,name',
            'stockMarket:id,name',
        ])
        ->whereIn('id', $directionIds)
        ->get();
    }

    private function createBundlesWithRelations($bundles, $directionsWithRelations)
    {
        $directionsMap = $directionsWithRelations->keyBy('id');

        return $bundles->map(function ($bundle) use ($directionsMap) {
            return [
                'from_direction' => $directionsMap->get($bundle['from_direction']['id']),
                'to_direction' => $directionsMap->get($bundle['to_direction']['id']),
                'spread' => $bundle['spread'],
            ];
        });
    }
}
