<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubadquirenteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subadquirentes = [
            [
                'name' => 'SubadqA',
                'base_url' => 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io',
                'config' => json_encode([
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'mock_response_header' => 'x-mock-response-name',
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SubadqB',
                'base_url' => 'https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io',
                'config' => json_encode([
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'mock_response_header' => 'x-mock-response-name',
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('subadquirentes')->insert($subadquirentes);
    }
}
