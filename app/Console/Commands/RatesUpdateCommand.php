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
    protected $signature = 'rates:update';
    protected $description = 'Refetch rates from stock markets';

    public function handle()
    {
        Benchmark::dd(function () {
            Coroutine\run(function () {
                $waitGroup = new WaitGroup();
        
                $parsers = [
                    BinanceParser::class,
                    BitgetParser::class,
                    GarantexParser::class,
                    HuobiParser::class,
                    OkxParser::class,
                ];
        
                $results = [];
        
                foreach ($parsers as $parserClass) {
                    $waitGroup->add();
        
                    Coroutine::create(function () use ($parserClass, &$results, $waitGroup) {
                        $results[] = (new $parserClass)->handle();
        
                        $waitGroup->done(); 
                    });
                }

                $waitGroup->wait();

                $results = Arr::flatten($results, 1);
                $results = $this->filterNullPrice($results);
                $results = $this->castCurrencyNames($results);

                $this->saveStockMarkets($results);
                $this->saveCurrencies($results);
                $this->clearOldDirections();
                $this->saveDirections($results);

                Cache::set('bundles', null);
            });
        });
    }

    private function castCurrencyNames($directions) {
        foreach ($directions as &$direction) {
            $direction['bid_currency'] = strtoupper($direction['bid_currency']);
            $direction['ask_currency'] = strtoupper($direction['ask_currency']); 
        }

        return $directions;
    }

    private function saveStockMarkets($directions) {
        $stockMarkets = Arr::pluck($directions, 'stock_market');
        $stockMarkets = array_unique($stockMarkets);
        $stockMarkets = Arr::map($stockMarkets, fn ($stockMarket) => [ 
            'name' => $stockMarket, 
            'refetched_at' => now(), // new UTCDateTime(now()->getTimestamp() * 1000)
        ]);
        $stockMarkets = array_values($stockMarkets);

        return StockMarket::query()->upsert($stockMarkets, [ 'name' ], [ 'refetched_at' ]);
    }

    private function saveCurrencies($directions) {
        $bid_currencies = Arr::pluck($directions, 'bid_currency');
        $ask_currencies = Arr::pluck($directions, 'ask_currency');

        $currencies = array_unique(Arr::flatten([$bid_currencies, $ask_currencies]));
        $currencies = Arr::map($currencies, fn ($currency) => [ 'name' => $currency ]);

        $currencies = array_values($currencies);

        return Currency::query()->upsert($currencies, [ 'name' ]);
    }

    private function clearOldDirections() {
        Direction::query()->delete();
    }

    private function saveDirections($directions) {
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
            
            if (
                in_array('FUD', [$direction['ask_currency'], $direction['bid_currency']])
                && in_array('USDT', [$direction['ask_currency'], $direction['bid_currency']])
                && $direction['stock_market'] == 'Huobi'
            ) dump($d, $direction['ask_currency']);

            return $d;
        });

        dump(count($directions));

        // dump($directions);

        return Direction::query()->upsert($directions, ['stock_market_id', 'ask_currency_id', 'bid_currency_id']);
    }

    private function filterNullPrice($directions) {
        return array_values(
            array_filter($directions, fn ($direction) => $direction['buy_price'] != 0 && $direction['sell_price'] != 0)
        );
    }

    // public function handle_old()
    // {
    //     while (true) {
    //         $updaters = array_map(fn($updater) => new $updater, [
    //             DirectionsUpdaterGarantex::class,
    //             DirectionsUpdaterBinance::class,
    //             DirectionsUpdaterOkx::class,
    //             DirectionsUpdaterBitget::class,
    //             DirectionsUpdaterHuobi::class,
    //         ]);

    //         $time = Benchmark::value(function () use ($updaters) {
    //             $start = now();

    //             $responses = Http::pool(function (Pool $pool) use ($updaters) {
    //                 foreach ($updaters as $id => $updater) {                        
    //                     $updater->marketsUrl && $pool->as('markets.' . $id)->get($updater->marketsUrl);
    //                     $updater->ratesUrl && $pool->as('rates.' . $id)->get($updater->ratesUrl);
    //                 }
    //             });

    //             dump("Responses: ". now()->diff($start));

    //             // $start = now();

    //             $directions = [];
    //             foreach ($updaters as $id => $updater) {                
    //                 $directions = array_merge($directions, $updater->update(
    //                     Arr::get($responses, 'markets.' . $id)?->json(),
    //                     Arr::get($responses, 'rates.' . $id)?->json(),
    //                 ));
    //             }

    //             // dump("Update database: ". now()->diff($start));

    //             // $start = now();
    //             // $encodedDirections = array_map('json_encode', $directions);
    //             // dump("Json endoe: ". now()->diff($start));

    //             // Redis::delete('directions');
    //             // Redis::rpush('directions', ...$encodedDirections);
    //         });

    //         dump($time[1]);
    //     }

    //     // bybit, mexc, bingX, gate, kucoin
    //     // bitget, okx, huobi, 
    // }
}
