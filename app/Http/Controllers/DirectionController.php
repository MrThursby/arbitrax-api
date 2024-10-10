<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use Illuminate\Support\Facades\Cache;

class DirectionController extends Controller
{
    public function index()
    {
        if ($directions = Cache::get('directions')) {
            return $directions;
        }

        $directions = Direction::query()
            ->with([
                'bidCurrency',
                'askCurrency',
                'stockMarket',
            ])
            ->limit(50)->get();

        Cache::set('directions', $directions);

        return $directions;
    }
}
