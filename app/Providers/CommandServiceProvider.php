<?php

namespace App\Providers;

use App\Console\Commands\CryptoPortfolioBotCommand;
use App\Console\Commands\LogUserBalanceCommand;
use App\Console\Commands\LogUserTradeStatsCommand;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.telegram.crypto-portfolio-bot', function () {
            return new CryptoPortfolioBotCommand();
        });
        $this->app->singleton('command.telegram.log-user-balance', function () {
            return new LogUserBalanceCommand();
        });
        $this->app->singleton('command.telegram.log-user-trade-stats', function () {
            return new LogUserTradeStatsCommand();
        });

	$this->commands(
            'command.telegram.crypto-portfolio-bot',
            'command.telegram.log-user-balance',
            'command.telegram.log-user-trade-stats'
        );
    }
}
