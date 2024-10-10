<?php

namespace App\DirectionsUpdater;

use App\DirectionsUpdater\DirectionsUpdater;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class DirectionsUpdaterOkx extends DirectionsUpdater
{
    protected string $stockMarketName = 'OKX';
    public ?string $marketsUrl = null;
    public ?string $ratesUrl = 'https://www.okx.com/api/v5/market/tickers?instType=SPOT';

    protected function parseMarkets(?array $markets) {
        return null;
    }

    protected function parseRates(array $rates) {
        return $rates['data'];
    }

    protected function createDirections($markets, $rates): array {
        foreach ($rates as $rate) {
            if ($this->checkPrices($rate)) continue;

            [ $bid_currency, $ask_currency ] = $this->getCurrencyModels(...array_reverse(explode('-', $rate['instId'])));

            if ($this->isFiat($bid_currency->name) || $this->isFiat($ask_currency->name)) continue;

            $directions []= [
                'bid_currency_id' => $bid_currency->id,
                'ask_currency_id' => $ask_currency->id,
                'stock_market_id' => $this->stockMarket->id,
                'buy_price'       => (float) $rate['askPx'],
                'sell_price'      => (float) $rate['bidPx'],
            ];
        }

        return $directions;
    }

    private function checkPrices($rate)
    {
        return ! (float) $rate['askPx']
            || ! (float) $rate['bidPx'];
    }
}
