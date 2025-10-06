<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateUserCommand extends Command
{
    protected $signature = 'chat:create-user {name} {email} {password}';
    protected $description = 'Creates a new user with a unique API key for the chat system';

    public function handle()
    {
        $user = User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => Hash::make($this->argument('password')),
            'api_key' => Str::random(60), // Otomatik olarak benzersiz bir API key oluşturur
        ]);

        $this->info("User '{$user->name}' created successfully.");
        $this->info("Email: {$user->email}");
        $this->warn("API Key: {$user->api_key}"); // API anahtarını ekrana basar

        return 0;
    }
}
