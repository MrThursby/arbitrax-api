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
            
            [$bid_currency_id, $ask_currency_id] = $this->getCurrencyIds($market['quoteCoin'], $market['baseCoin']);
            if (!$bid_currency_id || !$ask_currency_id) continue;

            $direction = [
                'bid_currency' => $market['quoteCoin'],
                'ask_currency' => $market['baseCoin'],
                
                'bid_currency_id' => $bid_currency_id,
                'ask_currency_id' => $ask_currency_id,

                'stock_market_id' => $this->stock_market_id,
                'buy_price'       => (float) $rate['askPr'],
                'sell_price'      => (float) $rate['bidPr'],
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
        $responses = $this->asyncFetchUrls([
            $this->markets_url, 
            $this->rates_url
        ]);

        return array_map(fn ($response) => $response['data'], $responses);
    }
}