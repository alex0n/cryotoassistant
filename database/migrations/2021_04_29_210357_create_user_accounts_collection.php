<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint as SchemaBlueprint;

class CreateUserAccountsCollection extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_accounts', function (SchemaBlueprint $collection) {
            $collection->increments('_id');
            $collection->integer('user_id');
            $collection->integer('platform_id');
            $collection->integer('platform_user_id');
            $collection->string('api_public_key');
            $collection->string('api_secret_key');
            $collection->boolean('is_enabled');
            $collection->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_accounts_collection');
    }
}
