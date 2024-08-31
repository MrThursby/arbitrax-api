<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $credentials = [
            'login' => $this->ask("Enter login"),
            'password' => $this->secret('Enter password'),
        ];

        $validator = validator($credentials, [
            'login' => 'required|string|min:3|max:64',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->table(
                ['Errors'],
                $validator->errors(),
            );

            return false;
        }

        User::query()->create([
            'login' => $credentials['login'],
            'password' => Hash::make($credentials['password']),
        ]);

        $this->info('User created');
    }
}
