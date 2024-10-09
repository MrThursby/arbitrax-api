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

            $directions[] = [
                'bid_currency' => $market['quoteAsset'],
                'ask_currency' => $market['baseAsset'],
                'stock_market' => $this->name,
                'buy_price'       => (float) $rate['askPrice'],
                'sell_price'      => (float) $rate['bidPrice'],
            ];
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