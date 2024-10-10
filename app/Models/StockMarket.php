<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use MongoDB\Laravel\Eloquent\Model;

class StockMarket extends Model
{
    use HasFactory;

    // protected $connection = 'mongodb';
    protected $guarded = [];

    protected $casts = [
        'refetched_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // protected function casts(): array
    // {
    //     return [ 'refetched_at' => 'datetime' ];
    // }
}
