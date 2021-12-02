<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint as SchemaBlueprint;

class CreateUserTradeStatsCollection extends Migration
{
    /**
     * php artisan make:migration create_user_balance_collection
     * php artisan migrate
     * php artisan migrate:reset
     * php artisan migrate:rollback --step=1 (revert one migration)
     * php artisan migrate:rollback (roll back last migration)
     */
    public function up()
    {
        Schema::create('user_trade_stats', function (SchemaBlueprint $collection) {
            $collection->increments('_id');
            $collection->string('user_account_id');
            $collection->unsignedInteger('binance_id');
            $collection->string('pair');
            $collection->float('contracts', 8, 2, true);
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
        Schema::dropIfExists('user_trade_stats');
    }
}
