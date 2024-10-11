<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class DirectionController extends Controller
{
    public function index(Request $request)
    {
        $directions = Direction::query()
            ->with([
                'bidCurrency',
                'askCurrency',
                'stockMarket',
            ]);

        $ask_currency = $request->input('ask_currency');
        if ($ask_currency) $directions->whereHas('askCurrency', fn (Builder $query) => $query->where('name', $ask_currency));
        
        $bid_currency = $request->input('bid_currency');
        if ($bid_currency) $directions->whereHas('bidCurrency', fn (Builder $query) => $query->where('name', $bid_currency));

        // Cache::set('directions', $directions);

        return $directions->paginate(50);
    }
}
