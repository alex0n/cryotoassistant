<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAccount;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Spatie\Emoji\Emoji;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\CallbackQuery;
use TelegramBot\Api\Types\Chat;
use TelegramBot\Api\Types\ForceReply;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;
use Binance\API as BinanceApiClient;
use TelegramBot\Api\Client as TelegramClient;
use Lin\Binance\Binance as BinanceClient2;
use Lin\Binance\BinanceFuture;
use Lin\Binance\BinanceDelivery;

/**
 * BINANCE docs: https://binance-docs.github.io/apidocs/futures/en/#position-information-v2-user_data
 */
class Binance
{
//    const CHAT_ID = 475279505;
//    const BINANCE_SPOT1_TOKEN_PUB = 'XoomMOUDQO4LfJ2gWkbxp6Bhz7u7p73PPdQ3tUyDZmnZAEKUj2f9Q3Fsh0SVeadc';
//    const BINANCE_SPOT1_TOKEN_SECRET = 'bMcsmJibc9tyVK041UDUlYqT6V0dOMC4OHcfzSVO0AiCnDYuMQN0vkkqfIoByYIn';
//
//    const BINANCE_FUT1_TOKEN_PUB = '7b228PZveIsvWOHMIzBQfFLwWnJmh0wLX7pavDgOaR3GzqsvJ1o3Lnh09JKLjiwd';
//    const BINANCE_FUT1_TOKEN_SECRET = 'S2JREPe76kLSL5PNFbC5WllfFHYWen6bodzXwOO7nksBIQ4ZgnXqAHisqyypqwWF';
//
//    const BINANCE_SPOT2_TOKEN_PUB = '16cSWRQWP3ohUNGlewtk3z9i3GE45cZS53EMyF8mHgTdiKcEa712aNfjARf8kM8v';
//    const BINANCE_SPOT2_TOKEN_SECRET = '6jYOjwpbPI6qT5IBlGrsGIPc0fUTEPTaBEIL99kL39IXceBOIdEWIML7huMPSoid';
//
//    const BINANCE_FUT2_TOKEN_PUB = 'ELMr6M4NBMBwxJ6KYgfzxrqbIJ1GOtte3LeZPxtcQQYEnyNqCS5R15TWE0dUcKkz';
//    const BINANCE_FUT2_TOKEN_SECRET = 'lFfnEYgyrP6Z3oDPmOu8VuZIAy6gV5nmXm19rzDV7ZESLo0B0zHgxpTnpBpALFy0';
//
//    const TELEGRAM_BOT_TOKEN = '1663707830:AAH_fE8lNqVG15etdCYg_C05ven4jPPUZJw';

    public function returnGrandTotals(): void
    {
        $grandTotal = 0;
        $grandAvailableTotal = 0;

        // SPOT 1
//        $api = $this->getBinanceSpotApiClient();
        $api = $this->getBinanceSpotApiClient($this->getBinanceKey('BINANCE_SPOT1_TOKEN_PUB'), $this->getBinanceKey('BINANCE_SPOT1_TOKEN_SECRET'));
        $ticker = $api->prices();
        $balances = $api->balances($ticker);
        $btcPrice = $api->price('BTCUSDT');
        $spotTotal = $api->btc_total * $btcPrice;

        $grandTotal += $spotTotal;
        $grandAvailableTotal += $spotTotal;

        // FEATURES 1
        $api = $this->getBinanceFuturesUsdMApiClient($this->getBinanceKey('BINANCE_FUT1_TOKEN_PUB'), $this->getBinanceKey('BINANCE_FUT1_TOKEN_SECRET'));
        $result = $api->user()->getAccount();

        $grandTotal += $result['totalWalletBalance'];
        $grandAvailableTotal += $result['availableBalance'];

        // SPOT 2
        $api = $this->getBinanceSpotApiClient($this->getBinanceKey('BINANCE_SPOT2_TOKEN_PUB'), $this->getBinanceKey('BINANCE_SPOT2_TOKEN_SECRET'));
        $ticker = $api->prices();
        $balances = $api->balances($ticker);
//        $btcPrice = $api->price('BTCUSDT');
        $spotTotal = $api->btc_total * $btcPrice;

        $grandTotal += $spotTotal;
        $grandAvailableTotal += $spotTotal;

        // FEATURES 2
        // USD-M
        $usdBalance = $this->returnBinanceFuturesUsdMBalance($this->getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), $this->getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'));
        $grandTotal += $usdBalance['total'];
        $grandAvailableTotal += $usdBalance['available'];
        // COIN-M
        $coinBalance = $this->returnBinanceFuturesCoinMBalance($this->getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), $this->getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'));
        $grandTotal += $coinBalance['total'];
        $grandAvailableTotal += $coinBalance['available'];

        $this->sendMessage(\sprintf('Total: %.2f USD / Available: %.2f USD', $grandTotal, $grandAvailableTotal));
    }

    private function returnPortfolioTotals(): void
    {
        $api = $this->getBinanceSpotApiClient();
        $ticker = $api->prices();
        $balances = $api->balances($ticker);

        $btcPrice = $api->price('BTCUSDT');
        $this->sendMessage(\sprintf('Binance estimated total: %.4f BTC / %.2f USD', $api->btc_total, $api->btc_total * $btcPrice));
    }

    private function returnBinanceSpotCoins(): void
    {
        $api = $this->getBinanceSpotApiClient();
        $ticker = $api->prices();
        $balances = $api->balances($ticker);
        $btcPrice = $api->price('BTCUSDT');

        $msg = '';
        $i = 0;
        foreach ($balances as $coinCode => $coinData) {
            if ((float)$coinData['available'] > 0 && (float)$coinData['btcTotal'] > 0) {
                $amountInUsd = $coinData['btcTotal']*$btcPrice;
                if ((float)sprintf('%.2f', $amountInUsd) === 0.0) {
                    continue;
                }
                $msg .= sprintf('%d. %s %.5f (%.2f$)', ++$i, $coinCode, $coinData['available'], $amountInUsd)."\n";
            }
        }

        $this->sendMessage($msg);
    }

    public function returnBinanceFuturesUsdMBalance(string $keyPublic, string $keySecret): array
    {
        $api = $this->getBinanceFuturesUsdMApiClient($keyPublic, $keySecret);
        $result = $api->user()->getAccount();

        $totalMargin = (float)$result['totalMarginBalance'];

        $marginRatio = $totalMargin > 0 ? (float)$result['totalMaintMargin'] / $totalMargin : 0;
        return [
            'total' => $result['totalWalletBalance'],
            'available' => $result['availableBalance'],
            'pnl' => $result['totalUnrealizedProfit'],
            'marginRatio' => $marginRatio,
        ];
    }

    private function showMainMenu(\TelegramBot\Api\Client $bot, User $user, Chat $chat): void
    {
        $buttons = [
            ['👤 Account'],
            ['💵 Grand total balance', Emoji::hammerAndWrench().' Toggle admin'],
            [
                '💵 B1 Spot balance',
                '💰 B1 Spot coins',
                '💰 B1 Futures balance',
                '💰 B1 Futures coins',
//                '💵 Binance1 Spot balance',
//                '💰 Binance1 Spot coins',
            ],
            [
                '💰 B2 Futures balance',
                '💰 B2 Futures coins',
                '💰 B2 Futures o.orders',
                '💰 B2 profit',
            ],
        ];

        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($buttons, true, true); // true for one-time keyboard

        $bot->sendMessage($chat->getId(), Emoji::wavingHand() . ' Welcome back '. ($user->first_name ?? '@'.$user->username) .'!', null, false, null, $keyboard);
    }


    public function returnBinanceFuturesUsdMBalanceMessage(string $keyPublic, string $keySecret): void
    {
        $result = $this->returnBinanceFuturesUsdMBalance($keyPublic, $keySecret);

        $msg = '';
        $msg .= sprintf('Total balance: %.2f', $result['total'])."\n";
        $msg .= sprintf('Unrealized profit: %.2f', $result['pnl'])."\n";
        $msg .= sprintf('Available balance: %.2f', $result['available'])."\n";

        $this->sendMessage($msg);
    }

    public function returnBinanceFuturesCoinMBalance(string $keyPublic, string $keySecret): array
    {
        $api = $this->getBinanceFuturesCoinMApiClient($keyPublic, $keySecret);
        $balances = $api->user()->getBalance();

        $totalAmount = 0;
        $totalAvilableAmount = 0;
        $totalPnl = 0;
        foreach ($balances as $coinCode => $coinData) {
            if ((float)$coinData['balance'] > 0) {
                $coinCode = $coinData['asset'];

                $coinPriceData = $api->market()->getDepth([
                    'symbol'=>sprintf('%sUSD_PERP', $coinCode),
                    'limit'=>5
                ]);

                $coinPrice = $coinPriceData['asks'][0][0];
                $totalAmount += $coinData['balance'] * $coinPrice;
                $totalAvilableAmount += $coinData['availableBalance'] * $coinPrice;
                $totalPnl += $coinData['crossUnPnl'] * $coinPrice;
            }
        }

        return [
            'total' => $totalAmount,
            'available' => $totalAvilableAmount,
            'pnl' => $totalPnl,
        ];
    }

    public function returnBinanceFuturesOpenPositions(string $keyPublic, string $keySecret): void
    {
        $api = $this->getBinanceFuturesUsdMApiClient($keyPublic, $keySecret);
        $result = $api->user()->getAccount();

        $msg = '';
        $i = 0;
        $totalPnl = 0;
        $openPositions = [];
        foreach ($result['positions'] as $coinData) {
            if ((float)$coinData['positionAmt'] > 0) {
                $openPositions[] = $coinData;
                $totalPnl += (float)$coinData['unrealizedProfit'];
                $buyOrderCount = $this->getNumberOfBuyOrdersExecutedSinceEnter($keyPublic, $keySecret, $coinData['symbol']);
                $msg .= sprintf(
                        '%d. %s %s$ %.1f pcs, %.2f$ (%d)',
                        ++$i,
                        $coinData['symbol'],
                        $coinData['entryPrice'],
                        $coinData['positionAmt'],
                        ($coinData['unrealizedProfit'] > 0 ? '+':'').$coinData['unrealizedProfit'],
                        $buyOrderCount
                    )."\n";
            }
        }

        $balanceData = $this->returnBinanceFuturesUsdMBalance($keyPublic, $keySecret);
        $balancTotal = (float)$balanceData['total'];
        $depoPercent = $balancTotal > 0 ? abs((float)$balanceData['pnl']) * 100 / $balancTotal : 0;
        $msg .= sprintf('Total PnL: %.2f (%.2f %%)', $balanceData['pnl'], $depoPercent)."\n";
        $msg .= sprintf('Balance: %.2f', $balanceData['total'])."\n";
        $msg .= sprintf('M.Ratio: %.2f', $balanceData['marginRatio'] * 100);
//        $this->sendMessage($msg);

        $keyboard = null;
        if ($this->isAdminMode()) {
            $buttons = [];
            $i = 0;
            foreach ($openPositions as $position) {
//                $buttons[] = new InlineKeyboardButton([
//                    'text' => Emoji::crossMark() . ' Close #' . (++$i) . ' ' . sprintf('%s',
//                            str_replace('USDT', '', $position['symbol'])),
//                    'callback_data' => 'closePositionMarket;' . $i . ';' . $position['symbol'] . ';' . $position['positionAmt']
//                ]);
                $buttons[] = [
                    new InlineKeyboardButton([
                        'text' => Emoji::crossMark() . sprintf('%s',
                                str_replace('USDT', '', $position['symbol'])),
                        'callback_data' => 'closePositionMarket;' . $i . ';' . $position['symbol'] . ';' . $position['positionAmt']
                    ])
                ];
            }
            $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($buttons); // true for one-time keyboard
        }
        $bot = $this->getTelegramClient();
        $bot->sendMessage($this->getChatId(), $msg, null, false, null, $keyboard);
    }

    public function returnBinanceFuturesOpenOrders(string $keyPublic, string $keySecret): void
    {
        $api = $this->getBinanceFuturesUsdMApiClient($keyPublic, $keySecret);
        $orders = $api->user()->getOpenOrders();
        $msg = '';
        $i = 0;
        foreach ($orders as $order) {
            $msg .= sprintf(
                    '%d. %s %s %s %s$ %spsc %s',
                    ++$i,
                    str_replace('USDT', '', $order['symbol']), $order['side'],
                    substr($order['origType'],0,2  ),
                    $order['price'],
                    $order['origQty'],
                    (new \DateTime())->setTimestamp((int)$order['time']/1000)->format('d-m-Y H:i')
                )."\n";
        }

        $allButtons = [];
        $i = 0;
        $coinSymbols = [];
        foreach ($orders as $order) {
            $coinSymbols[$order['symbol']] = null;
            $allButtons[] = [
                new InlineKeyboardButton(['text' => Emoji::crossMark().' #'.(++$i). ' '.sprintf('%s %s', str_replace('USDT', '', $order['symbol']), $order['side']), 'callback_data' => 'deleteOrder;'.$i.';'.$order['symbol'].';'.$order['orderId']])
            ];
        }

        $keyboard = null;
        if ($this->isAdminMode()) {
            foreach (array_keys($coinSymbols) as $coinSymbol) {
                $allButtons[] = [
                    new InlineKeyboardButton([
                        'text' => Emoji::crossMark() . ' All ' . str_replace('USDT', '', $coinSymbol),
                        'callback_data' => 'deleteCoinOrders;' . $coinSymbol
                    ])
                ];
            }
            $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($allButtons); // true for one-time keyboard
        }
        $bot = $this->getTelegramClient();
        $bot->sendMessage($this->getChatId(), $msg, null, false, null, $keyboard);
    }

    /**
     * Cancel all Open Orders on a Symbol
     */
    public function cancelBinanceFuturesOpenOrderForCoin(string $keyPublic, string $keySecret, string $coinSymbol): bool
    {
        $api = $this->getBinanceFuturesUsdMApiClient($keyPublic, $keySecret);

        try {
            $result=$api->trade()->deleteAllOpenOrders([
                'symbol'=> $coinSymbol, //ADAUSDT
                //'timeInForce'=>'GTC',
            ]);
            return $result['code'] === 200;
        } catch (\Exception $e){
            $this->sendMessage('Error:'. $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel specific open order
     */
    public function cancelBinanceFuturesOrder(string $keyPublic, string $keySecret, string $coinSymbol, int $orderId): bool
    {
        $api = $this->getBinanceFuturesUsdMApiClient($keyPublic, $keySecret);

        try {
            $result=$api->trade()->deleteOrder([
                'symbol'=>$coinSymbol,
                'orderId'=>$orderId,
//                'origClientOrderId'=>$result['origClientOrderId'],
            ]);
            return $result['status'] === 'CANCELED';
        } catch (\Exception $e){
            $this->sendMessage('Error:'. $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel specific open order
     */
    public function closeBinanceFuturesPositionByMarket(string $keyPublic, string $keySecret, string $coinSymbol, string $quantity): bool
    {
        $api = $this->getBinanceFuturesUsdMApiClient($keyPublic, $keySecret);

        try {
            $result=$api->trade()->postOrder([
                'symbol'=> $coinSymbol,
                'side'=>'SELL',
                'type'=>'MARKET',
                'quantity'=>$quantity, //'0.01',
//                'timeInForce'=>'GTC',
            ]);
            return true;
        } catch (\Exception $e){
            dd($e->getMessage());
            $this->sendMessage('Error:'. $e->getMessage());
            return false;
        }
    }

    public function getNumberOfBuyOrdersExecutedSinceEnter(string $keyPublic, string $keySecret, string $coinSymbol): int
    {
        $api = $this->getBinanceFuturesUsdMApiClient($keyPublic, $keySecret);

        $orders = $api->user()->getAllOrders([
                'symbol' => $coinSymbol,
                'limit'=>'50',
            ]
        );

        $orders = array_reverse($orders);

        $buyOrderCount = 0;
        foreach ($orders as $order) {
            if ($order['status'] !== 'FILLED') {
                continue;
            }
            if ($order['side'] === 'SELL') {
                break;
            }
            $buyOrderCount++;
        }

        return $buyOrderCount > 0 ? --$buyOrderCount : 0;
    }

    public function getIncomeForPeriod(string $keyPublic, string $keySecret, \DateTime $fromDt, \DateTime $tillDt): float
    {
        $api = $this->getBinanceFuturesUsdMApiClient($keyPublic, $keySecret);

        $incomeItems = $api->user()->getIncome([
            'startTime' => $fromDt->getTimestamp()*1000,
            'endTime' => $tillDt->getTimestamp()*1000,
            'limit' => 1000,
        ]);

        $total = 0;
        foreach ($incomeItems as $income) {
            $total += $income['income'];
        }
        return $total;
    }

    public function getTradesForPeriod(string $keyPublic, string $keySecret, \DateTime $fromDt, ?\DateTime $tillDt = null): array
    {
        $api = $this->getBinanceFuturesUsdMApiClient($keyPublic, $keySecret);

        $query = [
            'startTime' => $fromDt->getTimestamp()*1000,
            'limit' => 1000,
        ];

        if ($tillDt) {
            $query['endTime'] = $tillDt->getTimestamp()*1000;
        }
        $tradesResult = $api->user()->getUserTrades($query);
    /*
    "symbol" => "ALICEUSDT"
    "id" => 74989090
    "orderId" => 997153213
    "side" => "SELL"
    "price" => "14.860"
    "qty" => "17.8"
    "realizedPnl" => "0"
    "marginAsset" => "USDT"
    "quoteQty" => "264.5080"
    "commission" => "0.05290160"
    "commissionAsset" => "USDT"
    "time" => 1628641752578
    "positionSide" => "BOTH"
    "buyer" => false
    "maker" => true
    */

        $trades = array_map(function (array $tradeData) {
            if ($tradeData['realizedPnl'] === '0') {
                return null;
            }

            return [
                'binance_id' => $tradeData['id'],
                'pair' => $tradeData['symbol'],
                'qty' => $tradeData['qty'],
                'profit' => (float)$tradeData['realizedPnl']-(float)$tradeData['commission'],
                'dt' => (new \DateTime())->setTimestamp((int)$tradeData['time']/1000)->format('d-m-Y H:i:s'),
            ];
        }, $tradesResult);

        return array_filter($trades);
    }

    public function getTradesSinceBinanceId(string $keyPublic, string $keySecret, $binanceTradeId): array
    {
        $api = $this->getBinanceFuturesUsdMApiClient($keyPublic, $keySecret);

        $tradesResult = $api->user()->getUserTrades([
            'fromId' => $binanceTradeId,
            'limit' => 1000,
        ]);

    /*
    "symbol" => "ALICEUSDT"
    "id" => 74989090
    "orderId" => 997153213
    "side" => "SELL"
    "price" => "14.860"
    "qty" => "17.8"
    "realizedPnl" => "0"
    "marginAsset" => "USDT"
    "quoteQty" => "264.5080"
    "commission" => "0.05290160"
    "commissionAsset" => "USDT"
    "time" => 1628641752578
    "positionSide" => "BOTH"
    "buyer" => false
    "maker" => true
    */

        $trades = array_map(function (array $tradeData) {
            if ($tradeData['realizedPnl'] === '0') {
                return null;
            }

            return [
                'binance_id' => $tradeData['id'],
                'pair' => $tradeData['symbol'],
                'qty' => $tradeData['qty'],
                'profit' => (float)$tradeData['realizedPnl']-(float)$tradeData['commission'],
                'dt' => (new \DateTime())->setTimestamp((int)$tradeData['time']/1000)->format('d-m-Y H:i:s'),
            ];
        }, $tradesResult);

        return array_filter($trades);
    }

    public function toggleAdminMode(): bool
    {
        $fileName = '/tmp/crypto-man';
        if (file_exists($fileName)) {
            unlink($fileName);
            return false;
        } else {
            touch($fileName);
            return true;
        }
    }

    public function isAdminMode(): bool
    {
        $bot = $this->getTelegramClient();
        $fileName = '/tmp/crypto-man';
        if (!file_exists($fileName)) {
            return false;
        }

        $ftime = filemtime($fileName);
        $secondsPassed = $this->secondsAgo($ftime);
//        $bot->sendMessage($this->getChatId(), 'Seconds: '.$secondsPassed);

        if ($secondsPassed > 30) {
            unlink($fileName);
            return false;
        } else {
            return true;
        }
    }

    public function checkChatIsCorrect(int $chatId): void
    {
        if ($this->getChatId() !== $chatId) {
            $this->getBinanceSpotApiClient()->sendMessage($chatId, 'No data');
            die;
        }
    }

    public function getBinanceSpotApiClient(?string $keyPublic, string $keySecret): BinanceApiClient
    {
        if (empty($keyPublic)) {
            $keyPublic = $this->getBinanceKey('BINANCE_SPOT1_TOKEN_PUB');
        }
        if (empty($keySecret)) {
            $keySecret = $this->getBinanceKey('BINANCE_SPOT1_TOKEN_SECRET');
        }
        return new BinanceApiClient($keyPublic, $keySecret);
    }

    public function getBinanceFuturesUsdMApiClient(string $keyPublic, string $keySecret): BinanceFuture
    {
        return new BinanceFuture($keyPublic, $keySecret);
    }

    public function getBinanceFuturesCoinMApiClient(string $keyPublic, string $keySecret): BinanceDelivery
    {
        return new BinanceDelivery($keyPublic, $keySecret);
    }

    public function getTelegramClient(): TelegramClient
    {
        return new TelegramClient($this->getTelegramBotToken());
    }

    public function registerUser(Chat $chat): ?User
    {
//        try {
        $user = new User();
        $user->username = $chat->getUsername();
        $user->first_name = $chat->getFirstName();
        $user->last_name = $chat->getLastName();
        $user->chat_id = $chat->getId();
        $user->save();

        return $user;
//        } catch (\Exception $e) {
//            return null;
//        }
    }

    public function sendMessage(string $message): void
    {
        $bot = $this->getTelegramClient();
        $bot->sendMessage($this->getChatId(), $message);
    }

    private function secondsAgo(int $startTime): int
    {

        $diff = 0;

        $currentTime = time();
        if($currentTime >= $startTime) {
            $diff     = time()- $startTime;

            return $diff;
        }

        return $diff;
    }

    public static function getChatId(): string
    {
        return env('CHAT_ID');
    }

    public static  function getTelegramBotToken(): string
    {
        return env('TELEGRAM_BOT_TOKEN');
    }
    
    public static function getBinanceKey(string $namespace): string
    {
        return env($namespace);
    }    
}
