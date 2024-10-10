<?php

namespace App\DirectionsUpdater;

use App\DirectionsUpdater\DirectionsUpdater;
use App\Models\Currency;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class DirectionsUpdaterBinance extends DirectionsUpdater
{
    protected string $stockMarketName = 'Binance';
    public ?string $marketsUrl = 'https://api.binance.com/api/v3/exchangeInfo';
    public ?string $ratesUrl = 'https://api.binance.com/api/v3/ticker/bookTicker';

    protected function parseMarkets(array $markets)
    {
        return $markets['symbols'];
    }

    protected function parseRates(array $rates)
    {
        return $rates;
    }

    protected function createDirections($markets, $rates): array
    {
        foreach ($markets as $market) {
            if (is_null($rate = $this->getRate($rates, $market['symbol']))) continue;
            if ($this->checkPrices($rate)) continue;

            [$bid_currency, $ask_currency] = $this->getCurrencyModels($market['quoteAsset'], $market['baseAsset']);

            if ($this->isFiat($bid_currency->name) || $this->isFiat($ask_currency->name)) continue;

            $directions[] = [
                'bid_currency_id' => $bid_currency->id,
                'ask_currency_id' => $ask_currency->id,
                'stock_market_id' => $this->stockMarket->id,
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

    private function checkPrices($rate)
    {
        return ! (float) $rate['bidPrice']
            || ! (float) $rate['askPrice'];
    }
}
