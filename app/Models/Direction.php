<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use MongoDB\Laravel\Eloquent\Model;

class Direction extends Model
{
    use HasFactory;

    // protected $connection = 'mongodb';
    protected $guarded = [];

    public function bidCurrency()
    {
        return $this->belongsTo(Currency::class, 'bid_currency_id');
    }

    public function askCurrency()
    {
        return $this->belongsTo(Currency::class, 'ask_currency_id');
    }

    public function stockMarket()
    {
        return $this->belongsTo(StockMarket::class);
    }
}
