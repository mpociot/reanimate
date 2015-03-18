<?php

use Illuminate\Database\Capsule\Manager as DB;

class UserMigrator {

    public function up() {
        DB::schema()->dropIfExists('users');

        DB::schema()->create('users', function($t) {
            $t->increments('id');
            $t->string('name');
            $t->timestamp('created_at')->nullable();
            $t->timestamp('updated_at')->nullable();
            $t->softDeletes();
        });
    }

    public function down() {
        DB::schema()->drop('users');
    }

}