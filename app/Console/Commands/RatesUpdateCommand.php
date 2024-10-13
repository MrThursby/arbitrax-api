<?php

namespace App\Console\Commands;

use App\Models\Direction;
use App\StockMarketParsers\BinanceParser;
use App\StockMarketParsers\BitgetParser;
use App\StockMarketParsers\GarantexParser;
use App\StockMarketParsers\HuobiParser;
use App\StockMarketParsers\OkxParser;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Cache;
use Swoole\Coroutine;

class RatesUpdateCommand extends Command
{
    protected $signature = 'rates:update {--chunk=500}';
    protected $description = 'Refetch rates from stock markets';
    
    private $parsers = [
        BinanceParser::class,
        BitgetParser::class,
        // GarantexParser::class,
        HuobiParser::class,
        OkxParser::class,
    ];

    public function handle()
    {
        [, $duration] = Benchmark::value(function () {
            $directions = [];

            $this->comment("\nStart corutine and async parsers");

            Coroutine\run(function () use (&$directions) {            
                foreach ($this->parsers as $parserClass) {
                    $directions[] = (new $parserClass)->handle();
                }
            });

            $this->comment('Prepare directions');
            $directions = Arr::flatten($directions, 1);

            $this->saveDirections($directions, $this->option('chunk'));

            Coroutine\run(function () {
                Cache::delete('bundles');
                Cache::delete('directions');
            });

            unset($directions);
            gc_collect_cycles();
        });

        $this->info("\nDirections updated. Working time: $duration", );
    }

    private function saveDirections($directions, $chink_size) {
        $now = now();

        $directions = Arr::map($directions, function ($direction) use ($now) {
            $d = [
                'bid_currency_id' => $direction['bid_currency_id'],
                'ask_currency_id' => $direction['ask_currency_id'],
                'stock_market_id' => $direction['stock_market_id'],
                'buy_price'       => $direction['buy_price'],
                'sell_price'      => $direction['sell_price'],
                'updated_at'      => $now,
            ];

            return $d;
        });

        $chunks = array_chunk($directions, $chink_size);
        foreach ($chunks as $chunk) Direction::query()->upsert($chunk, ['stock_market_id', 'ask_currency_id', 'bid_currency_id']);
        
        Direction::query()
            ->where('updated_at', '<', $now)
            ->delete();
    }
}
