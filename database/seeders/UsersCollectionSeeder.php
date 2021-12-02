<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersCollectionSeeder extends Seeder
{
    /**
     * php artisan db:seed --class=UsersCollectionSeeder
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'username' => 'borovko',
            'chat_id' => 475279505,
            'first_name' => 'Aleksandrs',
            'last_name' => 'Borovko',
//            'password' => Hash::make('password'),
        ]);
    }
}
