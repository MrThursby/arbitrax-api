<?php

namespace App\StockMarketParsers;

use Illuminate\Support\Arr;

class BitgetParser extends StockMarketParser {
    public string $name = 'Bitget';

    private string $markets_url = 'https://api.bitget.com/api/v2/spot/public/symbols';
    private string $rates_url   = 'https://api.bitget.com/api/v2/spot/market/tickers';
    
    public function handle(): array
    {
        [$markets, $rates] = $this->prepareDirectionsData();

        foreach ($markets as $market) {
            $rate = $this->getRate($rates, $market['symbol']);

            if (!$rate) continue;

            $directions[] = [
                'bid_currency' => $market['quoteCoin'],
                'ask_currency' => $market['baseCoin'],
                'stock_market' => $this->name,
                'buy_price'       => (float) $rate['askPr'],
                'sell_price'      => (float) $rate['bidPr'],
            ];
        }

        return $directions;
    }

    private function getRate($rates, $symbol)
    {
        return Arr::first($rates, fn($rate) => $rate['symbol'] === $symbol);
    }

    private function prepareDirectionsData() {        
        $responses = $this->asyncFetchUrls([
            $this->markets_url, 
            $this->rates_url
        ]);

        return array_map(fn ($response) => $response['data'], $responses);
    }
}