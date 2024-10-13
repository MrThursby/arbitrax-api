<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\Direction;
use App\Models\ParserRule;
use App\Models\StockMarket;
use Illuminate\Console\Command;

class TruncateStockMarketsInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rates:truncate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all data from stock_markets, directions, currencies tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ParserRule::query()->delete();
        StockMarket::query()->delete();
        Direction::query()->delete();
        Currency::query()->delete();
    }
}
