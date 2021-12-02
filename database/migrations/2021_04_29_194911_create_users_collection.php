<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint as SchemaBlueprint;

class CreateUsersCollection extends Migration
{
    /**
     * create migration command: php artisan make:migration create_users_collection
     * migrate commands:
     * php artisan migrate
     * php artisan migrate:reset
     *
     * @return void
     */
    public function up()
    {
//        Schema::create('users_collection', function (Blueprint $table) {
//            $table->id();
//            $table->timestamps();
//        });
        Schema::create('users', function (SchemaBlueprint $collection) {
            $collection->increments('_id');
            $collection->string('username');
            $collection->unique('username');
            $collection->unique('chat_id');
            $collection->string('first_name');
            $collection->string('last_name');
            $collection->dateTime('deleted_at');
            $collection->timestamps();

        });

//        Schema::create('users', function ($collection) {
//            $collection->index(
//                'username',
//                null,
//                null,
//                [
//                    'sparse' => true,
//                    'unique' => true,
//                    'background' => true,
//                ]
//            );
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_collection');
    }
}
