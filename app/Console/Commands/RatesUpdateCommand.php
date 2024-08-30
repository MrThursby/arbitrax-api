<?php

namespace App\Console\Commands;

use App\DirectionsUpdater\DirectionsUpdaterBinance;
use App\DirectionsUpdater\DirectionsUpdaterBitget;
use App\DirectionsUpdater\DirectionsUpdaterGarantex;
use App\DirectionsUpdater\DirectionsUpdaterHuobi;
use App\DirectionsUpdater\DirectionsUpdaterOkx;
use Illuminate\Console\Command;
use Illuminate\Support\Benchmark;

class RatesUpdateCommand extends Command
{
    protected $signature = 'rates:update';
    protected $description = 'Refetch rates from stock markets';

    public function handle()
    {
        while (true) {
            $time = Benchmark::value(function () {
                app(DirectionsUpdaterGarantex::class)->update();
                app(DirectionsUpdaterBinance::class)->update();
                app(DirectionsUpdaterOkx::class)->update();
                app(DirectionsUpdaterBitget::class)->update();
                app(DirectionsUpdaterHuobi::class)->update();
            });

            dump($time[1]);
        }

        // bybit, mexc, bingX, gate, kucoin
        // bitget, okx, huobi, 
    }
}
