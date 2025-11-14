<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => '西 伶奈', 'first_name' => 'rena', 'last_initial' => 'n'],
            ['name' => '山田 太郎', 'first_name' => 'taro', 'last_initial' => 'y'],
            ['name' => '増田 一世', 'first_name' => 'issei', 'last_initial' => 'm'],
            ['name' => '山本 敬吉', 'first_name' => 'keikichi', 'last_initial' => 'y'],
            ['name' => '秋田 朋美', 'first_name' => 'tomomi', 'last_initial' => 'a'],
            ['name' => '中西 教夫', 'first_name' => 'norio', 'last_initial' => 'n'],
            ['name' => '安倍 祐作', 'first_name' => 'yusaku', 'last_initial' => 'a'],
        ];

        foreach ($users as $user) {
            $email = "{$user['first_name']}.{$user['last_initial']}@coachtech.com";

            $newUser = User::create([
                'name' => $user['name'],
                'email' => $email,
                'password' => Hash::make('password'),
            ]);

            $newUser->sendEmailVerificationNotification();
        }
    }
}
