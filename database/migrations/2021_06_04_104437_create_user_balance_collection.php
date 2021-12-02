<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint as SchemaBlueprint;

class CreateUserBalanceCollection extends Migration
{
    /**
     * php artisan make:migration create_user_balance_collection
     * php artisan migrate
     * php artisan migrate:reset
     * php artisan migrate:rollback --step=1
     * php artisan migrate:rollback
     */
    public function up()
    {
        Schema::create('user_balance', function (SchemaBlueprint $collection) {
            $collection->increments('_id');
            $collection->string('user_account_id');
            $collection->integer('account_id');
            $collection->float('balance', 8, 2, true);
            $collection->float('profit', 8, 2, true);
            $collection->date('date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_balance');
    }
}
