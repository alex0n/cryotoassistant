<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAccount;
use App\Models\UserBalance;
use App\Models\UserTradeStats;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;
use App\Services\Binance;
use Lin\Binance\Binance as BinanceClient2;
use Lin\Binance\BinanceFuture;
use Lin\Binance\BinanceDelivery;
use App\Models\Animal;

/**
 * https://github.com/zhouaini528/binance-php
 * Class TestController
 * @package App\Http\Controllers
 */
class TestController extends CryptoPortfolioController
{
    /**
     * https://www.grandum.com/t/tst
     */
    public function process(Binance $binanceService)
    {
        $user = User::first();
        $userAccount = $user->accounts()->first();
        $userAccountId = $userAccount->id;

        $msg = '';

        $currentMonthNum = (int)date('m');
        foreach (range(1, $currentMonthNum) as $monthNum) {

            $monthFirstDate = \DateTime::createFromFormat('Y-m-d', sprintf('%d-%d-%d', date('Y'), $monthNum, 1));
            $monthFirstDate->setTimezone(new \DateTimeZone('Europe/Helsinki'));
//                        $monthName = (\DateTime::createFromFormat('!m', $monthNum))->format('F');
            $monthName = $monthFirstDate->format('F');
            $monthLastDate = (clone $monthFirstDate)->modify('last day of this month');

            $monthFirstDate->modify('-2 days');

            $monthStartBalance = $userAccount->balances()->where(
                'date', '>=',
                $monthFirstDate
            )->offset(0)->limit(1)->first();
            $monthEndBalance = $userAccount->balances()
                ->where(
                    'date', '>=',
                    $monthFirstDate
                )
                ->where(
                    'date', '<=',
                    $monthLastDate
                )
                ->orderBy('date', 'desc')
                ->first();

            $startBalance = $monthStartBalance ? $monthStartBalance->balance : 0;
            $endBalance = $monthEndBalance ? $monthEndBalance->balance : 0;

            if (!$startBalance || !$endBalance) {
                continue;
            }

            $monthProfit = $endBalance - $startBalance;
            $msg .= sprintf('%s: %s - %s: %s', $monthName, $startBalance, $endBalance, $monthProfit)."\n";
        }
        $msg .= "\n";
        var_dump($msg);
        die('stop');

        $msg = '';
        $totalsPerMonthBalances = UserBalance::raw(function ($collection) {
            return $collection->aggregate([
                [
                    '$group' => [
                        "_id" => ['$month'=>'$created_at'],
                        'user_account_id' => ['$first' => '$user_account_id'],
                        'vsego' => ['$sum' => '$profit']
                    ]
                ],

            ]);
        });
        $totalsPerMonthBalances = $totalsPerMonthBalances
            ->where(
                'user_account_id', '=',
                '60ba0ab677b7ce059908071a'
            )
            ->sortBy('_id')
        ;

        /** @var Collection $totalsPerMonthBalancesRecords */
        $totalsPerMonthBalancesRecords = $totalsPerMonthBalances->pluck('vsego', '_id');
        foreach ($totalsPerMonthBalancesRecords as $monthNum => $profit) {
                        $dateObj   = \DateTime::createFromFormat('!m', $monthNum);
                        $monthName = $dateObj->format('F');
                        $msg .= sprintf('%s: %.2f', $monthName, $profit)."\n";
        }
        $msg .= "\n";
        dd($msg);


        $dtFrom = (new \DateTime())
            ->setTimezone(new \DateTimeZone('Europe/Helsinki'))
            ->sub(new \DateInterval('P1D'))
            ->setTime(0,0, 0);
        $dtTill = (clone $dtFrom)->add(new \DateInterval('P1D'));

        $trades = $binanceService->getTradesSinceBinanceId(
            Binance::BINANCE_FUT2_TOKEN_PUB,
            Binance::BINANCE_FUT2_TOKEN_SECRET,
            74989090
        );
           dd(count($trades));
//        $user = User::first();
//        $userAccount = $user->accounts()->first();
//        $stat = $userAccount->tradeStats()->save(
//            new UserTradeStats([
//                'pair' => 'BTTUSDT',
//                'contracts' => 1.24,
//                'profit' => 100.75,
//                'date' => date('Y-m-d')
//            ])
//        );
//        dd('done! Stat id:'.$stat->id);
//        die('done');

        $user = User::first();
        $balances = $user->accounts()->first()->balances()->orderBy('date', 'ASC')->get();

        $monthDtFrom = (new \DateTime('first day of this month'))
            ->setTimezone(new \DateTimeZone('Europe/Helsinki'))
            ->sub(new \DateInterval('P1D'))
            ->setTime(0,0, 0);

        $monthBalances = $user->accounts()->first()->balances()->where(
            'date', '>=',
            $monthDtFrom
        )->get();
        $totalThisMonth = 0;
        foreach($monthBalances as $balance) {
            $totalThisMonth += $balance->profit;
        }
        $msg = '';

        $prevBalanceAmount = 0;
        $incomeThisMonthDynamic = 0;

        foreach($balances as $balance) {
            $prevBalanceAmount = $prevBalanceAmount ?: $balance->balance - $balance->profit;
            $dt = new \DateTime($balance->date);
            $balanceAmountDiff = $prevBalanceAmount ? $balance->balance - $prevBalanceAmount : $balance->profit;
            $incomeThisMonthDynamic += $balanceAmountDiff;
            $profitPercent = $balanceAmountDiff > 0 ? $balanceAmountDiff * 100 / $prevBalanceAmount : 0;
            $prevBalanceAmount = $balance->balance;
            $msg .= sprintf('%s: %.2f (%.2f / %.2f) %.2f%%', $dt->format('d-m-Y'), $balance->balance, $balance->profit, $balanceAmountDiff, $profitPercent)."\n";
        }

        $dtFrom = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Helsinki'))->setTime(0,0, 0);
        $dtTill = (clone $dtFrom)->add(new \DateInterval('P1D'));
        $incomeToday = $binanceService->getIncomeForPeriod(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET, $dtFrom, $dtTill);

        $profitPercentToday = $balance->balance && $incomeToday ? $incomeToday * 100 / $balance->balance : 0;

        $currentBalance = $binanceService->returnBinanceFuturesUsdMBalance(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET);

        $msg .= sprintf('%s: %.2f %.2f %.2f%%', date('d-m-Y'), $currentBalance['total'], $incomeToday, $profitPercentToday)."\n";

        $incomeThisMonth = $totalThisMonth + $incomeToday;
        $incomeThisMonthDynamic = $incomeThisMonthDynamic + $incomeToday;

        $profitPercentThisMonth = !empty($balances[0]) && is_object($balances[0]) ? $incomeThisMonth * 100 / $balances[0]->balance : 0;
        $msg .= sprintf('This month: %.2f / %.2f %.2f%%', $incomeThisMonth, $incomeThisMonthDynamic, $profitPercentThisMonth);

        dd($msg);


        $dtFrom = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Helsinki'))->setTime(0,0, 0);
        $dtTill = (clone $dtFrom)->add(new \DateInterval('P1D'));
        $incomeToday = $binanceService->getIncomeForPeriod(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET, $dtFrom, $dtTill);
dd($incomeToday);

        $user = User::first();
//        $userAccount = $user->accounts()->save(
//            new UserAccount([
//                'platform_id' => 1,
//                'platform_user_id' => 3,
//                'api_public_key' => 'aa',
//                'api_secret_key' => 'bb',
//                'is_enabled' => true,
//            ])
//        );
////        $userAccount = $user->accounts()->first();
//        dd('done! User id:'.$user->id.' account id:'.$userAccount->id);
        $user = User::first();

        $balances = $user->accounts()->first()->balances()->orderBy('date', 'DESC')->get();
        $msg = '';

        foreach($balances as $balance) {
            $msg .= sprintf('%s: %.2f (%.2f)', $balance->date, $balance->balance, $balance->profit);
        }
        dd($msg);


dd($user->accounts()->first()->balances()->count());
        $userAccount = $user->accounts()->first();
        $balance = $userAccount->balances()->save(
            new UserBalance([
                'balance' => 1724.00,
                'date' => date('Y-m-d')
            ])
        );
        dd('done! Balance id:'.$balance->id);

//        $animal=new Animal();
//        $animal->species = 'one';
//        $animal->color = 'red';
//        $animal->leg = 'two';
//        $animal->save();
//        dd('done!');

//        $api = new Binance\API('XoomMOUDQO4LfJ2gWkbxp6Bhz7u7p73PPdQ3tUyDZmnZAEKUj2f9Q3Fsh0SVeadc',
//            'bMcsmJibc9tyVK041UDUlYqT6V0dOMC4OHcfzSVO0AiCnDYuMQN0vkkqfIoByYIn');
//        $ticker = $api->prices();
//        $balances = $api->balances($ticker);
//
//        $msg = '';
//        $i = 0;
//        foreach ($balances as $coinCode => $coinData) {
//            if ((float)$coinData['available'] > 0) {
//                $msg .= sprintf('%d. %s %.2f', ++$i, $coinCode, $coinData['available'])."\n";
//                if ($i > 2) break;
//            }
//        }
//        $token = '1663707830:AAH_fE8lNqVG15etdCYg_C05ven4jPPUZJw';
//        $bot = new \TelegramBot\Api\Client($token);
//
//        $bot->sendMessage(475279505, $msg);
//        dd('done');

//        $binance = new BinanceFuture(self::BINANCE_FUT_TOKEN_PUB, self::BINANCE_FUT_TOKEN_SECRET);
////        $binance = new BinanceDelivery(self::BINANCE_FUT_TOKEN_PUB, self::BINANCE_FUT_TOKEN_SECRET);
//
////        $result=$binance->user()->getBalance();
////        dd($result);
//
//        $result=$binance->user()->getAccount();
//        dd($result);

//        $api = $this->getBinanceApiClient();
//        $api = $this->getBinanceFuturesApiClient(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET);
//        $balance = $this->returnBinanceFuturesUsdMBalance(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET);
//        $this->closeBinanceFuturesOpenOrderForCoin(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET, 'RENUSDT');


//        "orderId" => 1988486526
//    "symbol" => "RENUSDT"
//    "status" => "NEW"
//    "clientOrderId" => "web_kWXif8eswzucc0r9j5qc"
//        $this->cancelBinanceFuturesOrder(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET, 'RENUSDT', 1988486526);

//        $this->closeBinanceFuturesPositionByMarket(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET, 'RENUSDT', '12');
//
        $api = $this->getBinanceFuturesUsdMApiClient(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET);

//        $r = $api->user()->getBalance([
//            'recvWindow' => 50000,
//            'timestamp' => time()*1000
//        ]);

//        $r = $api->user()->getIncome([
//            'startTime' => strtotime(date('2021-06-03 00:00:00'))*1000,
//            'endTime' => strtotime(date('2021-06-04 00:00:00'))*1000,
//            'limit' => 1000,
//        ]);
//
//        $total = 0;
//        foreach ($r as $income) {
//
//            $total += $income['income'];
//        }

        $dtFrom = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Helsinki'))->setDate(2021, 6, 3)->setTime(0,0, 0);
        $dtTill = (clone $dtFrom)->add(new \DateInterval('P1D'));

        $total = $this->getIncomeForPeriod(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET, $dtFrom, $dtTill);

        dd($total);

        $api = $this->getBinanceFuturesUsdMApiClient(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET);
        $result = $api->user()->getAccount();

        $msg = '';
        $i = 0;
        $totalPnl = 0;

        foreach ($result['positions'] as $coinData) {
            if ((float)$coinData['positionAmt'] > 0) {
                dd($coinData);
                $totalPnl += (float)$coinData['unrealizedProfit'];
                $msg .= sprintf('%d. %s %s$ %.1f pcs, %.2f$', ++$i, $coinData['symbol'], $coinData['entryPrice'], $coinData['positionAmt'], ($coinData['unrealizedProfit'] > 0 ? '+':'').$coinData['unrealizedProfit'])."\n";
            }
        }

        die('stop');

        $api = $this->getBinanceFuturesUsdMApiClient(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET);
        $result = $api->user()->getAccount();

        $msg = '';
        $i = 0;
        $totalPnl = 0;
        foreach ($result['positions'] as $coinData) {
            if ((float)$coinData['positionAmt'] > 0) {
                dd($coinData);
                $totalPnl += (float)$coinData['unrealizedProfit'];
                $msg .= sprintf('%d. %s %.1f pcs, %.2f profit', ++$i, $coinData['symbol'], $coinData['positionAmt'], $coinData['unrealizedProfit'])."\n";
            }
        }

        $balanceData = $this->returnBinanceFuturesUsdMBalance(self::BINANCE_FUT2_TOKEN_PUB, self::BINANCE_FUT2_TOKEN_SECRET);
        $balancTotal = (float)$balanceData['total'];
        $depoPercent = $balancTotal > 0 ? abs((float)$balanceData['pnl']) * 100 / $balancTotal : 0;
        $msg .= sprintf('Total PnL: %.2f (%.2f %%)', $balanceData['pnl'], $depoPercent)."\n";
        $msg .= sprintf('Balance: %.2f', $balanceData['total'])."\n";
        $msg .= sprintf('M.Ratio: %.2f', $balanceData['marginRatio'] * 100);
        $this->sendMessage($msg);
        dd($msg);
        $msg = '';
        $i = 0;
        $totalAmount = 0;
        $totalAvilableAmount = 0;
        foreach ($balances as $coinCode => $coinData) {
            if ((float)$coinData['balance'] > 0) {
                $coinCode = $coinData['asset'];

                $coinPriceData = $api->market()->getDepth([
                    'symbol'=>sprintf('%sUSD_PERP', $coinCode),
                    'limit'=>5
                ]);

                $coinPrice = $coinPriceData['asks'][0][0];
                $totalAmount += $coinData['balance'] * $coinPrice;
                $totalAvilableAmount += $coinData['availableBalance'] * $coinPrice;;

                //$msg .= sprintf('%d. %s %.2f', ++$i, $coinCode, $coinData['available'])."\n";
            }
        }
        dd($totalAmount);

//        $btcPrice = $api->price('BTCUSDT');
//        dd(\sprintf('Binance estimated total: %s BTC / %s USD', $api->btc_total, $api->btc_total * $btcPrice));
//        dd(\sprintf('Binance estimated total: %s BTC / %s USD', $api->btc_value, $api->btc_value * $btcPrice));
    }
}
