<?php

namespace App\StockMarketParsers;

use Illuminate\Support\Facades\Http;

class OkxParser extends StockMarketParser {
    public string $name = 'Okx';

    private string $rates_url   = 'https://www.okx.com/api/v5/market/tickers?instType=SPOT';
    
    public function handle(): array
    {
        $rates = $this->prepareDirectionsData();

        foreach ($rates as $rate) {
            [ $bid_currency, $ask_currency ] = array_reverse(explode('-', $rate['instId']));

            // if ($bid_currency === 'VELO' || $ask_currency === 'VELO') {
            //     dd($rate);
            // }

            $directions []= [
                'bid_currency' => $bid_currency,
                'ask_currency' => $ask_currency,
                'stock_market' => $this->name,
                'buy_price'       => (float) $rate['askPx'],
                'sell_price'      => (float) $rate['bidPx'],
            ];
        }

        return $directions;
    }

    private function prepareDirectionsData() {        
        return Http::get($this->rates_url)->json()['data'];
    }
}