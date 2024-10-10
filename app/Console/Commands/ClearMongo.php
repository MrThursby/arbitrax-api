<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearMongo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongo:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = DB::connection('mongodb');

        $collections = $connection->listCollections();

        foreach ($collections as $collection) {
            $connection->table($collection->getName())->delete();
        }

        $this->info('Mongo database was deleted');
    }
}
