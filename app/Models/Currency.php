<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use MongoDB\Laravel\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    // protected $connection = 'mongodb';
    protected $guarded = [];

    public function askDirections() {
        return $this->hasMany(Direction::class, 'ask_currency_id');
    }

    public function bidDirections() {
        return $this->hasMany(Direction::class, 'bid_currency_id');
    }

    public function directions()
    {
        return Direction::where('ask_currency_id', $this->id)
            ->orWhere('bid_currency_id', $this->id)
            ->get();
    }
}
