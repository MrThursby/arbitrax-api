<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\Direction;
use App\Models\StockMarket;
use App\StockMarketParsers\BinanceParser;
use App\StockMarketParsers\BitgetParser;
use App\StockMarketParsers\GarantexParser;
use App\StockMarketParsers\HuobiParser;
use App\StockMarketParsers\OkxParser;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;
// use MongoDB\BSON\UTCDateTime;

class RatesUpdateCommand extends Command
{
    protected $signature = 'rates:update {--once} {--chunk=500}';
    protected $description = 'Refetch rates from stock markets';
    
    private $parsers = [
        BinanceParser::class,
        BitgetParser::class,
        GarantexParser::class,
        HuobiParser::class,
        OkxParser::class,
    ];

    public function handle()
    {
        do {
            [, $duration] = Benchmark::value(function () {
                $directions = [];
    
                $this->comment('Start corutine and async parsers');
                Coroutine\run(function () use (&$directions) {            
                    foreach ($this->parsers as $parserClass) {        
                        Coroutine::create(function () use ($parserClass, &$directions) {
                            $directions[] = (new $parserClass)->handle();
                        });
                    }
                });
    
                $this->comment('Prepare directions');
                $directions = Arr::flatten($directions, 1);
                $directions = $this->filterNotInterestingDirections($directions);
                $directions = $this->castCurrencyNames($directions);
    
                $this->comment('Save stockmarkets');
                $this->saveStockMarkets($directions, $this->option('chunk'));
                
                $this->comment('Save currencies');
                $this->saveCurrencies($directions, $this->option('chunk'));
                
                $this->comment('Remove old directions');
                $this->clearOldDirections();
                
                $this->comment('Save directions');
                $this->saveDirections($directions, $this->option('chunk'));
    
                Cache::delete('bundles');
                Cache::delete('directions');

                unset($directions);
                gc_collect_cycles();
            });

            $this->info("\nDirections updated. Working time: $duration\n", );
        } while (!$this->option('once'));
    }

    private function castCurrencyNames($directions) {
        foreach ($directions as &$direction) {
            $direction['bid_currency'] = strtoupper($direction['bid_currency']);
            $direction['ask_currency'] = strtoupper($direction['ask_currency']); 
        }

        return $directions;
    }

    private function saveStockMarkets($directions, $chink_size) {
        $stockMarkets = Arr::pluck($directions, 'stock_market');
        $stockMarkets = array_unique($stockMarkets);

        $now = now()->toDateTime();
        $stockMarkets = Arr::map($stockMarkets, fn ($stockMarket) => [ 
            'name' => $stockMarket, 
            'refetched_at' => $now, // new UTCDateTime(now()->getTimestamp() * 1000)
        ]);
        
        $stockMarkets = array_values($stockMarkets);

        DB::transaction(function () use ($stockMarkets, $chink_size) {
            $chunks = array_chunk($stockMarkets, $chink_size);
            foreach ($chunks as $chunk) StockMarket::query()->upsert($chunk, [ 'name' ], [ 'refetched_at' ]);
        });

        return true;
    }

    private function saveCurrencies($directions, $chink_size) {
        $bid_currencies = Arr::pluck($directions, 'bid_currency');
        $ask_currencies = Arr::pluck($directions, 'ask_currency');

        $currencies = array_unique(Arr::flatten([$bid_currencies, $ask_currencies]));
        $currencies = Arr::map($currencies, fn ($currency) => [ 'name' => $currency ]);

        $currencies = array_values($currencies);

        DB::transaction(function () use ($currencies, $chink_size) {
            $chunks = array_chunk($currencies, $chink_size);
            foreach ($chunks as $chunk) Currency::query()->upsert($chunk, [ 'name' ]);
        });

        return true;
    }

    private function clearOldDirections() {
        Direction::query()->delete();
    }

    private function saveDirections($directions, $chink_size) {
        $currencies = Currency::query()->get()->keyBy('name');
        $stockMarkets = StockMarket::query()->get()->keyBy('name');

        $directions = Arr::map($directions, function ($direction) use ($currencies, $stockMarkets) {
            $d = [
                'bid_currency_id' => $currencies[$direction['bid_currency']]->id,
                'ask_currency_id' => $currencies[$direction['ask_currency']]->id,
                'stock_market_id' => $stockMarkets[$direction['stock_market']]->id,
                'buy_price'       => $direction['buy_price'],
                'sell_price'      => $direction['sell_price'],
            ];

            return $d;
        });

        DB::transaction(function () use ($directions, $chink_size) {
            $chunks = array_chunk($directions, $chink_size);
            foreach ($chunks as $chunk) Direction::query()->insert($chunk, ['stock_market_id', 'ask_currency_id', 'bid_currency_id']);
        });

        return true;
    }

    private function filterNotInterestingDirections($directions) {
        $directions = array_filter($directions, function ($direction) {
            if ($direction['buy_price'] == 0 && $direction['sell_price'] == 0) return false;
            if (in_array(['RUB', 'USDT', 'EUR'], $direction)) return false;

            return true;
        });

        return array_values($directions);
    }
}
