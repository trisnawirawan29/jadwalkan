<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $admin->notify(new SystemNotification('Selamat datang di NexaAdmin', 'Akun admin Anda siap digunakan.', 'success'));
        $admin->notify(new SystemNotification('Lengkapi profil Anda', 'Tambahkan avatar dan informasi pribadi agar profil lebih lengkap.', 'info'));
    }
}
