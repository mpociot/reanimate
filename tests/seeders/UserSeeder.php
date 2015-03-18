<?php

use Illuminate\Database\Capsule\Manager as DB;

class UserSeeder {

    public function run() {
        DB::table('users')->delete();

        User::unguard();

        User::create(array('id' => 1, 'name' => 'User 1' ));
        User::create(array('id' => 2, 'name' => 'User 2' ));
        User::create(array('id' => 3, 'name' => 'User 3' ));
        User::create(array('id' => 4, 'name' => 'User 4' ));

        User::reguard();
    }

}