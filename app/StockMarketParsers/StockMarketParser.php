<?php

namespace App\StockMarketParsers;

use App\Models\Currency;
use App\Models\ParserRule;
use App\Models\StockMarket;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;

abstract class StockMarketParser {
    public string $name;
    
    public int $stock_market_id;
    private array $parser_rules;

    private ?array $coingecko_coins = null;
    private ?array $coinmaketcap_coins = null;

    public function __construct()
    {
        $this->stock_market_id = StockMarket::query()->firstOrCreate(['name' => $this->name])->id;
        $this->parser_rules = ParserRule::where('stock_market_id', $this->stock_market_id)->get()->toArray();
    }

    protected function filterDirection($direction): bool {
        if (in_array(0, [ 
            $direction['buy_price'], 
            $direction['sell_price'],
        ])) return false;
        
        if (array_intersect(['RUB', 'EUR', 'USD'], [
            $direction['bid_currency'], 
            $direction['ask_currency'],
        ])) return false;

        return true;
    }

    /** @param string[] $tickers */
    protected function getCurrencyIds(...$tickers): array {
        return Arr::map($tickers, function (string $ticker): null|int|string {
            $rule = $this->getRule($ticker);

            return $rule['currency_id'] ?? null;
        });
    }

    /** @return array{ticker:string,stock_market_id:int,currency_id:?int} */
    private function getRule(string $ticker): array {
        $rule = Arr::first($this->parser_rules, fn ($rule) => $rule['ticker'] === $ticker);

        if (!$rule) {
            $rule = ParserRule::query()->create([
                'ticker' => $ticker,
                'stock_market_id' => $this->stock_market_id,
                'currency_id' => $this->tryFirstOrCreateCurrency($ticker),
            ])->toArray();

            $this->parser_rules[] = $rule;
            return $rule;
        }

        return $rule;
    }

    private function tryFirstOrCreateCurrency(string $ticker): ?int {
        [$coinmaketcap_id, $coingecko_id] = $this->getTickerIdentifiers($ticker);

        if (!$coingecko_id && !$coinmaketcap_id) return null;

        return Currency::query()->firstOrCreate([
            'name' => mb_strtoupper($ticker),
            'coinmarketcap_id' => $coinmaketcap_id,
            'coingecko_id' => $coingecko_id,
        ])->id;
    }

    private function getTickerIdentifiers(string $ticker) {
        if (!$this->coinmaketcap_coins && Cache::has('coinmaketcap_coins')) $this->coinmaketcap_coins = Cache::get('coinmaketcap_coins');
        if (!$this->coingecko_coins && Cache::has('coingecko_coins')) $this->coingecko_coins = Cache::get('coingecko_coins');

        if (!$this->coinmaketcap_coins) {
            $this->coinmaketcap_coins = Http::withHeaders([
                'X-CMC_PRO_API_KEY' => config('services.coinmarketcap.key'),
                'Accept' => 'application/json',
            ])->get('https://pro-api.coinmarketcap.com/v1/cryptocurrency/map')->json()['data'] ?? [];

            Cache::set('coinmaketcap_coins', $this->coinmaketcap_coins, now()->addHours(12));
        }

        if (!$this->coingecko_coins) {
            $this->coingecko_coins = Http::get('https://api.coingecko.com/api/v3/coins/list')->json() ?? [];
            Cache::set('coingecko_coins', $this->coingecko_coins, now()->addHours(12));
        }

        $coinmaketcap_coins_for_ticker = array_filter($this->coinmaketcap_coins, fn ($coin) => $coin['symbol'] === mb_strtoupper($ticker));
        $coingecko_coins_for_ticker = array_filter($this->coingecko_coins, fn ($coin) => $coin['symbol'] === mb_strtolower($ticker));

        return [
            count($coinmaketcap_coins_for_ticker) === 1 ? Arr::first($coinmaketcap_coins_for_ticker)['id'] : null,
            count($coingecko_coins_for_ticker) === 1 ? Arr::first($coingecko_coins_for_ticker)['id'] : null,
        ];
    }

    public abstract function handle(): array;

    protected function asyncFetchUrls($urls) {
        $waitGroup = new WaitGroup();
        $results = [];
    
        foreach ($urls as $key => $url) {
            $waitGroup->add(1);
    
            Coroutine::create(function () use ($waitGroup, &$results, $key, $url) {
                $results[$key] = $this->fetch($url);
                $waitGroup->done();
            });
        }

        $waitGroup->wait();

        return $results;
    }

    private function fetch(string $url): ?array
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];
        $path = $parsedUrl['path'] . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');

        $client = new Client($host, 443, true); // Используем HTTPS
        $client->setHeaders(['Host' => $host]);
        $client->get($path);

        $data = null;
        if ($client->statusCode === 200) {
            $data = json_decode($client->body, true);
        } else {
            echo "Ошибка при запросе к {$url}: {$client->body}\n";
        }

        $client->close();
        return $data;
    }
}