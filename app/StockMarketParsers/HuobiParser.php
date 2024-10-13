<?php

namespace App\StockMarketParsers;

use Illuminate\Support\Arr;

class HuobiParser extends StockMarketParser {
    public string $name = 'Huobi';

    private string $markets_url = 'https://api.huobi.pro/v2/settings/common/symbols';
    private string $rates_url   = 'https://api.huobi.pro/market/tickers';
    
    public function handle(): array
    {
        [$markets, $rates] = $this->prepareDirectionsData();

        foreach ($markets as $market) {
            $rate = $this->getRate($rates, $market['sc']);

            if (!$rate) continue;
            
            [$bid_currency_id, $ask_currency_id] = $this->getCurrencyIds($market['qcdn'], $market['bcdn']);
            if (!$bid_currency_id || !$ask_currency_id) continue;

            $direction = [
                'bid_currency' => $market['qcdn'],
                'ask_currency' => $market['bcdn'],
                
                'bid_currency_id' => $bid_currency_id,
                'ask_currency_id' => $ask_currency_id,

                'stock_market_id' => $this->stock_market_id,
                'buy_price'       => (float) $rate['ask'],
                'sell_price'      => (float) $rate['bid'],
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