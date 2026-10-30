<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class HrAdmin extends Command
{
    protected $signature = 'hr:admin {email=admin@dservice.local} {password=trocar-esta-senha}';
    protected $description = 'Cria ou redefine o usuário administrador';

    public function handle(): int
    {
        User::updateOrCreate(
            ['email' => $this->argument('email')],
            ['name' => 'Administrador', 'password' => Hash::make($this->argument('password'))]
        );
        $this->info('Login: ' . $this->argument('email') . '  |  Senha: ' . $this->argument('password'));
        return self::SUCCESS;
    }
}
