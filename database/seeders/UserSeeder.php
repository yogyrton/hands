<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Создаём только если пользователя ещё нет — чтобы повторный запуск
        // НЕ сбрасывал пароль и не разлогинивал (токены остаются валидными).
        // Запускать точечно: php artisan db:seed --class=UserSeeder
        User::query()->firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@hands.local')],
            [
                'name' => 'Администратор',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => UserRole::Admin,
            ],
        );

        User::query()->firstOrCreate(
            ['email' => env('MASTER_EMAIL', 'master@hands.local')],
            [
                'name' => 'Мастер',
                'password' => Hash::make(env('MASTER_PASSWORD', 'password')),
                'role' => UserRole::Master,
            ],
        );
    }
}
