<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuário administrador inicial — TROQUE a senha no primeiro acesso (ou defina no .env).
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@dservice.local')],
            ['name' => 'Administrador', 'password' => Hash::make(env('ADMIN_PASSWORD', 'trocar-esta-senha'))]
        );

        $this->call(SpreadsheetSeeder::class);
        $this->call(ExtraSheetsSeeder::class);
    }
}
