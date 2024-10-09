<?php

namespace App\StockMarketParsers;

use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;

abstract class StockMarketParser {
    public string $name;

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