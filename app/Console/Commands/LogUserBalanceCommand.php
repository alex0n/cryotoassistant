<?php
namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserBalance;
use App\Services\Binance;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Str;
use GuzzleHttp;

/**
 * php artisan telegram:log-user-balance
 */
class LogUserBalanceCommand extends Command
{
    protected $signature = 'telegram:log-user-balance';
    protected $description = "Log user balance";


    public function __construct()
    {
        parent::__construct();

    }

    public function handle(Binance $binanceService)
    {
        $result = $binanceService->returnBinanceFuturesUsdMBalance(Binance::BINANCE_FUT2_TOKEN_PUB, Binance::BINANCE_FUT2_TOKEN_SECRET);

        $user = User::first();

        $userAccount = $user->accounts()->first();
        $userBalances = $userAccount->balances()->where([
            'date' => date('Y-m-d')
        ]);

        $dtFrom = (new \DateTime())
            ->setTimezone(new \DateTimeZone('Europe/Helsinki'))
            ->sub(new \DateInterval('P1D'))
            ->setTime(0,0, 0);
        $dtTill = (clone $dtFrom)->add(new \DateInterval('P1D'));

        $profit = $binanceService->getIncomeForPeriod(
            Binance::BINANCE_FUT2_TOKEN_PUB,
            Binance::BINANCE_FUT2_TOKEN_SECRET,
            $dtFrom,
            $dtTill
        );

        if (0 === $userBalances->count()) {
            $balance = $userAccount->balances()->save(
            new UserBalance([
                'balance' => (float)$result['total'],
                'profit' => (float)$profit,
//                'date' => new \DateTime()
                'date' => date('Y-m-d')
            ])
            );
        } else {
            $userBalances->first()->update([
                'balance' => (float)$result['total'],
                'profit' => (float)$profit,
            ]);
        }

        $binanceService->sendMessage(sprintf(
            'Balance logged at: %s. End balance: %.2f. Day profit: %.2f',
            date('d-m-Y H:i'),
            $result['total'],
            $profit
        ));
//        $balance = $userAccount->balances()->updateOrCreate(
//            [
//                'balance' => (float)$result['total'],
//                'date' => date('Y-m-d')
//            ]
//        );

    }
}
