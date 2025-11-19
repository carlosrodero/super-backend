<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Usuário A',
                'email' => 'user-a@example.com',
                'password' => Hash::make('admin123'),
                'subadquirente_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Usuário B',
                'email' => 'user-b@example.com',
                'password' => Hash::make('admin123'),
                'subadquirente_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Usuário C',
                'email' => 'user-c@example.com',
                'password' => Hash::make('admin123'),
                'subadquirente_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);
    }
}
