<?php

namespace App\StockMarketParsers;

use Illuminate\Support\Arr;

class BinanceParser extends StockMarketParser {
    public string $name = 'Binance';

    private string $markets_url = 'https://api.binance.com/api/v3/exchangeInfo';
    private string $rates_url = 'https://api.binance.com/api/v3/ticker/bookTicker';
    
    public function handle(): array
    {
        [$markets, $rates] = $this->prepareDirectionsData();

        foreach ($markets as $market) {
            $rate = $this->getRate($rates, $market['symbol']);

            if (!$rate) continue;
            
            [$bid_currency_id, $ask_currency_id] = $this->getCurrencyIds($market['quoteAsset'], $market['baseAsset']);
            if (!$bid_currency_id || !$ask_currency_id) continue;
            
            $direction = [
                'bid_currency' => $market['quoteAsset'],
                'ask_currency' => $market['baseAsset'],
                
                'bid_currency_id' => $bid_currency_id,
                'ask_currency_id' => $ask_currency_id,
                
                'stock_market_id' => $this->stock_market_id,
                'buy_price'       => (float) $rate['askPrice'],
                'sell_price'      => (float) $rate['bidPrice'],
            ];

            if (!$this->filterDirection($direction)) continue;
            
            $directions []= $direction;
        }

        return $directions;
    }

    private function getRate($rates, $symbol)
    {
        return Arr::first($rates, fn($rate) => $rate['symbol'] === $symbol);
    }

    private function prepareDirectionsData() {        
        [$markets, $rates] = $this->asyncFetchUrls([
            $this->markets_url,
            $this->rates_url,
        ]);

        return [
            $markets['symbols'],
            $rates,
        ];
    }
}