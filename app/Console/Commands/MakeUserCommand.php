<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Hash;
use PharIo\Manifest\Email;

use function Laravel\Prompts\table;

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
            'email' => $this->ask("Enter email"),
            'password' => $this->secret('Enter password'),
        ];

        $validator = validator($credentials, [
            'password' => 'required|string|min:8',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->table(
                ['Errors'],
                $validator->errors(),
            );

            return false;
        }

        $user = User::query()->create([
            'email' => $credentials['email'],
            'password' => Hash::make($credentials['password']),
        ]);

        $this->info('User created');
    }
}
