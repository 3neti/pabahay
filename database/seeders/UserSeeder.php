<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $systemEmail = env('SYSTEM_USER_ID');

        $admin = User::updateOrCreate(
            ['email' => $systemEmail],
            [
                'name' => 'Lester B. Hurtado',
                'password' => Hash::make('password'),
//                'is_admin' => true,
            ]
        );

        $user = User::updateOrCreate(
            ['email' => 'lester@hurtado.ph'],
            [
                'name' => 'Lester Hurtado',
                'password' => Hash::make('password'),
            ]
        );
    }
}
