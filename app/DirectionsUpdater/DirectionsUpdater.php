<?php

namespace App\DirectionsUpdater;

use App\Models\Currency;
use App\Models\Direction;
use App\Models\StockMarket;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class DirectionsUpdater
{
    protected string $stockMarketName;
    protected StockMarket $stockMarket;

    public function __construct()
    {
        $this->stockMarket = StockMarket::query()
            ->firstOrCreate(['name' => $this->stockMarketName]);
    }

    public function update()
    {
        $markets = $this->fetchMarkets();
        $rates = $this->fetchRates();

        $directions = $this->createDirections($markets, $rates);
        $this->updateDirections($directions);

        $this->touchStockMarket();
    }

    public function isFiat($currencyName): bool
    {
        $fiatNames = [ 'EUR', 'USD', 'RUB' ];

        return in_array($currencyName, $fiatNames);
    }

    abstract protected function fetchMarkets();
    abstract protected function fetchRates();

    abstract protected function createDirections($markets, $rates): array;

    protected function updateDirections($directions) {
        Direction::query()->where('stock_market_id', $this->stockMarket->id)->delete();
        Direction::query()->upsert($directions, ['from_currency_id', 'to_currency_id']);
    }

    protected function getCurrencyModels($bidCurrencyName, $askCurrencyName): array {
        return [
            Currency::query()->firstOrCreate(['name' => Str::upper($bidCurrencyName)]),
            Currency::query()->firstOrCreate(['name' => Str::upper($askCurrencyName)]),
        ];
    }

    protected function touchStockMarket() {
        $this->stockMarket->refetched_at = now();
        $this->stockMarket->save();
    }
}
