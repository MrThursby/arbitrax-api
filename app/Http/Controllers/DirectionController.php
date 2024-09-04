<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use Illuminate\Http\Request;

class DirectionController extends Controller
{
    public function index()
    {
        return Direction::query()->with([
            'bidCurrency',
            'askCurrency',
            'stockMarket',
        ])->limit(50)->get();
    }
}
