<?php

namespace App\StockMarketParsers;

use Illuminate\Support\Arr;

class GarantexParser extends StockMarketParser {
    public string $name = 'Garantex';

    private string $markets_url = 'https://garantex.org/api/v2/markets';
    private string $rates_url   = 'https://garantex.org/rates';
    
    public function handle(): array
    {
        [$markets, $rates] = $this->prepareDirectionsData();


        foreach ($markets as $market) {
            $rate = $this->getRate($rates, $market['id']);

            if (!$rate) continue;

            $directions[] = [
                'bid_currency' => $market['bid_unit'],
                'ask_currency' => $market['ask_unit'],
                'stock_market' => $this->name,
                'buy_price'       => (float) $rate['sell'],
                'sell_price'      => (float) $rate['buy'],
            ];
        }

        return $directions;
    }

    private function getRate($rates, $id)
    {
        return Arr::get($rates, $id);
    }

    private function prepareDirectionsData() {        
        [$markets, $rates] = $this->asyncFetchUrls([
            $this->markets_url,
            $this->rates_url,
        ]);

        return [ $markets, $rates ];
    }
}