<?php

namespace App\DirectionsUpdater;

use App\DirectionsUpdater\DirectionsUpdater;
use App\Models\Currency;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class DirectionsUpdaterGarantex extends DirectionsUpdater
{
    protected string $stockMarketName = 'Garantex';
    public ?string $marketsUrl = 'https://garantex.org/api/v2/markets';
    public ?string $ratesUrl = 'https://garantex.org/rates';

    protected function createDirections($markets, $rates): array
    {
        foreach ($markets as $market) {
            if (is_null($rate = $this->getRate($rates, $market['id']))) continue;
            if ($this->checkPrices($rate)) continue;

            $bid_currency = Currency::query()->firstOrCreate(['name' => Str::upper($market['bid_unit'])]);
            $ask_currency = Currency::query()->firstOrCreate(['name' => Str::upper($market['ask_unit'])]);

            if ($this->isFiat($bid_currency->name) || $this->isFiat($ask_currency->name)) continue;

            $directions[] = [
                'bid_currency_id'   => $bid_currency->id,
                'ask_currency_id'   => $ask_currency->id,
                'stock_market_id'   => $this->stockMarket->id,
                'sell_price'        => (float) $rate['buy'],    // В эндпоинте /rates "buy" - цена из зеленого стакана
                'buy_price'         => (float) $rate['sell'],   // а "sell" - из красного
            ];
        }

        return $directions;
    }

    protected function parseMarkets(array $markets)
    {
        return $markets;
    }

    protected function parseRates(array $rates)
    {
        return $rates;
    }

    private function getRate($rates, $id)
    {
        return Arr::get($rates, $id);
    }

    private function checkPrices($rate)
    {
        return ! (float) $rate['buy']
            || ! (float) $rate['sell'];
    }
}
