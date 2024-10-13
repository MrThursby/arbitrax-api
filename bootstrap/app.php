<?php

use App\Jobs\StockMarketParseJob;
use App\StockMarketParsers\BinanceParser;
use App\StockMarketParsers\BitgetParser;
use App\StockMarketParsers\HuobiParser;
use App\StockMarketParsers\OkxParser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(new StockMarketParseJob(new BinanceParser))->everyMinute();
        $schedule->job(new StockMarketParseJob(new BitgetParser))->everyMinute();
        $schedule->job(new StockMarketParseJob(new HuobiParser))->everyMinute();
        $schedule->job(new StockMarketParseJob(new OkxParser))->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
