<?php

namespace App\StockMarketParsers;

use Illuminate\Support\Facades\Http;

class OkxParser extends StockMarketParser {
    public string $name = 'Okx';

    
    private string $rates_url   = 'https://www.okx.com/api/v5/market/tickers?instType=SPOT';
    
    public function handle(): array
    {
        $rates = $this->prepareDirectionsData();
        
        $directions = [];

        foreach ($rates as $rate) {
            [ $bid_currency, $ask_currency ] = array_reverse(explode('-', $rate['instId']));
            
            [$bid_currency_id, $ask_currency_id] = $this->getCurrencyIds($bid_currency, $ask_currency);
            if (!$bid_currency_id || !$ask_currency_id) continue;

            $direction = [
                'bid_currency' => $bid_currency,
                'ask_currency' => $ask_currency,
                
                'bid_currency_id' => $bid_currency_id,
                'ask_currency_id' => $ask_currency_id,

                'stock_market_id' => $this->stock_market_id,
                'buy_price'       => (float) $rate['askPx'],
                'sell_price'      => (float) $rate['bidPx'],
            ];

            if (!$this->filterDirection($direction)) continue;

            $directions []= $direction;
        }

        return $directions;
    }

    private function prepareDirectionsData() {        
        return Http::get($this->rates_url)->json()['data'];
    }
}