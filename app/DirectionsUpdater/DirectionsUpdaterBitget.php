<?php

namespace App\DirectionsUpdater;

use App\DirectionsUpdater\DirectionsUpdater;
use App\Models\Currency;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class DirectionsUpdaterBitget extends DirectionsUpdater
{
    protected string $stockMarketName = 'Bitget';

    protected function fetchMarkets()
    {
        return Http::get('https://api.bitget.com/api/v2/spot/public/symbols')->json()['data'];
    }

    protected function fetchRates()
    {
        return Http::get('https://api.bitget.com/api/v2/spot/market/tickers')->json()['data'];
    }

    protected function createDirections($markets, $rates): array
    {
        foreach ($markets as $market) {
            if (is_null($rate = $this->getRate($rates, $market['symbol']))) continue;
            if ($this->checkPrices($rate)) continue;

            [$bid_currency, $ask_currency] = $this->getCurrencyModels($market['quoteCoin'], $market['baseCoin']);

            if ($this->isFiat($bid_currency->name) || $this->isFiat($ask_currency->name)) continue;

            $directions[] = [
                'bid_currency_id' => $bid_currency->id,
                'ask_currency_id' => $ask_currency->id,
                'stock_market_id' => $this->stockMarket->id,
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

    private function checkPrices($rate)
    {
        return ! (float) $rate['bidPr']
            || ! (float) $rate['askPr'];
    }
}
