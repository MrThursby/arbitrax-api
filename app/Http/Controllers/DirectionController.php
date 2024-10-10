<?php

namespace App\Http\Controllers;

use App\Models\Direction;

class DirectionController extends Controller
{
    public function index()
    {
        $directions = Direction::query()
            ->with([
                'bidCurrency',
                'askCurrency',
                'stockMarket',
            ])
            ->limit(50)->get();

        return $directions;
    }
}
