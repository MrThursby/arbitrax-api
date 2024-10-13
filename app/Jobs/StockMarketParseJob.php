<?php

namespace App\Jobs;

use App\Models\Direction;
use App\StockMarketParsers\StockMarketParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Swoole\Coroutine;

class StockMarketParseJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public StockMarketParser $parser) {}

    public function handle(): void
    {
        $directions = [];

        Coroutine\run(function () use (&$directions) {
            $directions = $this->parser->handle();
        });

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

        $chunks = array_chunk($directions, 300);
        foreach ($chunks as $chunk) Direction::query()->upsert($chunk, ['stock_market_id', 'ask_currency_id', 'bid_currency_id']);
        
        Direction::query()
            ->where('stock_market_id', $this->parser->stock_market_id)
            ->where('updated_at', '<', $now)
            ->delete();
    }
}
