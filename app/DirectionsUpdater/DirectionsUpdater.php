<?php

namespace App\DirectionsUpdater;

use Redis;
use App\Models\Currency;
use App\Models\Direction;
use App\Models\StockMarket;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;

abstract class DirectionsUpdater
{
    protected string $stockMarketName;
    protected StockMarket $stockMarket;

    public ?string $marketsUrl;
    public ?string $ratesUrl;

    public function __construct()
    {
        $this->stockMarket = StockMarket::query()
            ->firstOrCreate(['name' => $this->stockMarketName]);
    }

    public function update($markets, $rates)
    {
        $markets = $this->parseMarkets($markets);
        $rates = $this->parseRates($rates);

        $directions = $this->createDirections($markets, $rates);
        $this->updateDirections($directions);

        $this->touchStockMarket();

        return $directions;
    }

    public function isFiat($currencyName): bool
    {
        $fiatNames = [ 'EUR', 'USD', 'RUB' ];

        return in_array($currencyName, $fiatNames);
    }

    abstract protected function parseMarkets(array $markets);
    abstract protected function parseRates(array $rates);

    abstract protected function createDirections($markets, $rates): array;

    protected function updateDirections($directions) {        
        Direction::query()->where('stock_market_id', $this->stockMarket->id)->delete();
        Direction::query()->insert($directions);
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
