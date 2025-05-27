<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\App;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (!App::environment(['local', 'testing'])) {
            $this->command->info('AdminSeeder skipped: not in local/testing environment.');
            return;
        }

        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => '123456',
            ],
            [
                'name' => 'Admin 2',
                'email' => 'admin2@admin.com',
                'password' => '123456',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                ]
            );
        }

        // $this->command->info('AdminSeeder ran successfully.');
    }
}
