<?php

namespace App\Http\Controllers;

use App\Models\StockMarket;

class StockMarketController extends Controller
{
    public function index() {
        return StockMarket::all();
    }
}
