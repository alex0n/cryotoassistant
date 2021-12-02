<?php
namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserAccount;
use App\Models\UserBalance;
use App\Models\UserTradeStats;
use App\Services\Binance;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Str;
use GuzzleHttp;

/**
 * php artisan telegram:log-user-trade-stats
 */
class LogUserTradeStatsCommand extends Command
{
    protected $signature = 'telegram:log-user-trade-stats {continue?} {--dt-from=} {--dt-till=}';
    protected $description = "Log user trade stats";


    public function __construct()
    {
        parent::__construct();

    }

    public function handle(Binance $binanceService)
    {
        $resumeFromLastId = $this->argument('continue');
        if (! $resumeFromLastId) {
            $dtFromInput = $this->option('dt-from');
            $dtTillInput = $this->option('dt-till');

            $dtFrom = \DateTime::createFromFormat('Y-m-d H:i:s', $dtFromInput, new \DateTimeZone('Europe/Helsinki'));
            $dtTill = \DateTime::createFromFormat('Y-m-d H:i:s', $dtTillInput, new \DateTimeZone('Europe/Helsinki'));

            if (!empty($dtFromInput) && false === $dtFrom) {
                throw new \Exception('Wrong dt-from specified');
            }

            if (!empty($dtTillInput) && false === $dtTill) {
                throw new \Exception('Wrong dt-from specified');
            }
        }

        $user = User::first();

        /** @var UserAccount $userAccount */
        $userAccount = $user->accounts()->first();

        if (! $resumeFromLastId) {
            if (!$dtFrom) {
                $dtFrom = (new \DateTime())
                    ->setTimezone(new \DateTimeZone('Europe/Helsinki'))
                    ->sub(new \DateInterval('P1D'))
                    ->setTime(0, 0, 0);


                $dtTill = (clone $dtFrom)->add(new \DateInterval('P1D'));
            }

            $trades = $binanceService->getTradesForPeriod(
                Binance::BINANCE_FUT2_TOKEN_PUB,
                Binance::BINANCE_FUT2_TOKEN_SECRET,
                $dtFrom,
                $dtTill ?: null
            );
        } else {
//            $lastTrade = $userAccount->tradeStats()->latest('_id')->first();
            $lastTrade = true;
            if ($lastTrade) {
                $trades = $binanceService->getTradesSinceBinanceId(
                    Binance::BINANCE_FUT2_TOKEN_PUB,
                    Binance::BINANCE_FUT2_TOKEN_SECRET,
                    74989090
//                    $lastTrade['binance_id']
                );
            }
        }

        foreach ($trades as $trade) {
            $userAccount->tradeStats()->save(
                    new UserTradeStats([
                        'binance_id' => (int)$trade['binance_id'],
                        'pair' => $trade['pair'],
                        'contracts' => (float)$trade['qty'],
                        'profit' => (float)$trade['profit'],
                        'date' => $trade['dt']
                ])
            );
        }

        $binanceService->sendMessage(sprintf(
            'Trade (%d) stats logged at: %s',
            count($trades),
            date('d-m-Y H:i')
        ));

        $this->info(sprintf(
            'Trade (%d) stats logged at: %s',
            count($trades),
            date('d-m-Y H:i')
        ));
    }
}
