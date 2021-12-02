<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAccount;
use App\Models\UserBalance;
use App\Services\Binance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
 * https://github.com/jaggedsoft/php-binance-api
 * https://github.com/zhouaini528/binance-php
 */
class CryptoPortfolioController extends Controller
{
    public function process(Binance $binance)
    {
        $bot = $this->getTelegramClient();

        $bot->command('help', function ($message) use ($bot) {
            $answer = Emoji::wavingHand() . ' ';
            $answer .= 'Commands:';
            //
            $bot->sendMessage($message->getChat()->getId(), $answer);
        });

        $bot->callbackQuery(function (CallbackQuery $query) use ($bot) {
            /** @var \TelegramBot\Api\Client|\TelegramBot\Api\BotApi $bot */
            switch(true) {
                case (/*$query->getMessage()->getText() === 'test' && */$query->getData() === 'Yes'):
//                    $bot->editedMessage()
//                    $bot->sendChatAction($query->getMessage()->getChat()->getId(), 'typing');

                    $bot->editMessageText($query->getMessage()->getChat()->getId(), $query->getMessage()->getMessageId(), 'Edited: You answered Yes');
//                    $bot->sendMessage($query->getMessage()->getChat()->getId(), 'Initial message:'.$query->getMessage()->getText());
////                    $bot->sendMessage($query->getMessage()->getChat()->getId(), 'Initial message:'.$query->getMessage()->getReplyToMessage()->getText());
//                    $bot->sendMessage($query->getMessage()->getChat()->getId(), 'You answered Yes');
//                $bot->answerCallbackQuery($query->getId(), 'This is my answer', true);

                    break;

                case (substr($query->getData(), 0, strlen('deleteOrder')) === 'deleteOrder'):
                    if (!$this->isAdminMode()) {
                        break;
                    }
                    $params = explode(';', $query->getData());
                    list($command,$orderNumber,$coinSymbol,$orderId) = $params;
                    $this->cancelBinanceFuturesOrder(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'), $coinSymbol, $orderId);
                    $bot->sendMessage($query->getMessage()->getChat()->getId(), 'Cancelled #'.$orderNumber.': '.$coinSymbol);
                    $this->returnBinanceFuturesOpenOrders(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'));
                    break;

                case (substr($query->getData(), 0, strlen('deleteCoinOrders')) === 'deleteCoinOrders'):
                    if (!$this->isAdminMode()) {
                        break;
                    }
                    $params = explode(';', $query->getData());
                    list($command,$coinSymbol) = $params;
                    $this->cancelBinanceFuturesOpenOrderForCoin(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'), $coinSymbol);
                    $bot->sendMessage($query->getMessage()->getChat()->getId(), 'Cancelled all orders for '.$coinSymbol);
                    $this->returnBinanceFuturesOpenOrders(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'));
                    break;

                case (substr($query->getData(), 0, strlen('closePositionMarket')) === 'closePositionMarket'):
                    if (!$this->isAdminMode()) {
                        break;
                    }
                    $params = explode(';', $query->getData());
                    list($command,$orderNumber,$coinSymbol,$contractAmount) = $params;
                    $this->closeBinanceFuturesPositionByMarket(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'), $coinSymbol, $contractAmount);
                    $bot->sendMessage($query->getMessage()->getChat()->getId(), 'Closed by market #'.$orderNumber.': '.$coinSymbol. ' '.$contractAmount. ' psc');
                    $this->returnBinanceFuturesOpenPositions(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'));
                    break;
            }
        });


//        $bot->command('hello', function ($message) use ($bot) {
//            $text = $message->getText();
//            $param = str_replace('/hello ', '', $text);
//            $answer = 'Unknown command';
//            if (!empty($param)) {
//                $answer = 'Hello, ' . $param;
//            }
//            $bot->sendMessage($message->getChat()->getId(), $answer);
//        });
//
//        $bot->command('test', function ($message) use ($bot) {
//            $buttons = [];
//            $buttons[] = ['text' => '🤖 Prev', 'callback_data' => '/post_1'];
//            $buttons[] = ['text' => '🚀 Next', 'callback_data' => '/post_3'];
//
//            $bot->sendMessage(
//                $message->getChat()->getId(),
//                'test text',
//                'markdown',
//                false,
//                null,
//                new InlineKeyboardMarkup([$buttons])
//            );
//        });
//
//        $bot->command('test2', function ($message) use ($bot) {
//            $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array(array("one", "two", "three")),
//                true); // true for one-time keyboard
//
//            $bot->sendMessage($message->getChat()->getId(), 'message text', null, false, null, $keyboard);
//
//        });
//
//        $bot->command('test3', function ($message) use ($bot) {
//            $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array(array("aa", "bbb", "ccc")),
//                true); // true for one-time keyboard
//
//            $bot->sendMessage($message->getChat()->getId(), 'message text2', null, false, null, $keyboard);
//
//        });
//
//
//        $bot->command('start2', function ($message) use ($bot) {
//            $buttons = [];
//            $buttons[] = ['text' => '🚀 Portfolio in BTC', 'callback_data' => '/stats'];
//
//            $bot->sendMessage(
//                $message->getChat()->getId(),
//                'test text',
//                'markdown',
//                false,
//                null,
//                new InlineKeyboardMarkup([$buttons])
//            );
//        });

        $bot->command('start', function (\TelegramBot\Api\Types\Message $message) use ($bot) {
            $user = User::where('username', $message->getChat()->getUsername())->first();
            if (!$user) {
                $user = $this->registerUser($message->getChat());
            }

            $this->showMainMenu($bot, $user, $message->getChat());
//            $buttons = [
//                    ['💵 Grand total balance'],
//                    [
//                        '💵 B1 Spot balance',
//                        '💰 B1 Spot coins',
//                        '💰 B1 Futures balance',
//                        '💰 B1 Futures coins',
////                '💵 Binance1 Spot balance',
////                '💰 Binance1 Spot coins',
//                    ],
//                    [
//                        '💰 B2 Futures balance',
//                        '💰 B2 Futures coins',
//                    ],
//                ];
//
//            $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($buttons, true, true); // true for one-time keyboard
//
//            $bot->sendMessage($message->getChat()->getId(), Emoji::wavingHand() . ' Welcome back '. ($user->first_name ?? '@'.$user->username) .'!', null, false, null, $keyboard);
        });

        $bot->command('stats', function ($message) use ($bot) {
            $this->checkChatIsCorrect($message->getChat()->getId());
            $this->returnPortfolioTotals();
        });

        $bot->on(function (\TelegramBot\Api\Types\Update $update) use ($bot, $binance) {
            /** @var \TelegramBot\Api\BotApi $bot */
            $message = $update->getMessage();
            $id = $message->getChat()->getId();
            switch (true) {
                case ($this->matchCommand($message->getText(), 'Account')):
                    $bot->sendMessage($message->getChat()->getId(), '[ALL ACCOUNTS SUMMARY]');
                    $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                        [
                            new InlineKeyboardButton(['text' => Emoji::airplane().' Account #1', 'callback_data' => 'AccountView;1']),
                            new InlineKeyboardButton(['text' => Emoji::airplane().' Account #2', 'callback_data' => 'AccountView;2']),
                            new InlineKeyboardButton(['text' => Emoji::airplane().' Account #3', 'callback_data' => 'AccountView;3']),

                        ],
                        [
                            new InlineKeyboardButton(['text' => Emoji::airplane().' Account #4', 'callback_data' => 'AccountView;4']),

                        ]
                    ]); // true for one-time keyboard
                    $bot->sendMessage($message->getChat()->getId(), 'Your accounts:', null, false, null, $keyboard);
//
                    break;
                case ($message->getText() === 'test'):
                    $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                        [
                            new InlineKeyboardButton(['text' => Emoji::airplane().' press me', 'callback_data' => 'Yes']),
                            new InlineKeyboardButton(['text' => Emoji::airplane().' press me', 'callback_data' => 'Yes']),
                            new InlineKeyboardButton(['text' => Emoji::airplane().' press me', 'callback_data' => 'Yes']),

                        ],
                        [
                            new InlineKeyboardButton(['text' => Emoji::airplane().' press me', 'callback_data' => 'Yes'])

                        ]
                        ]); // true for one-time keyboard
                    $m = $bot->sendMessage($message->getChat()->getId(), 'test message', null, false, null, $keyboard);
//                    $this->sendMessage('Message id:'.$m->getMessageId());
                    break;
                case ($message->getText() === 'id'):

                    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array(array("one", "two", "three")), true); // true for one-time keyboard

                    $m = $bot->sendMessage($message->getChat()->getId(), 'test message', null, false, null, $keyboard);
//                    $this->sendMessage($m->getReplyToMessage()->getText());
                    break;
                    $this->sendMessage(
                        'Chat id: '.$message->getChat()->getId()."\n" .
                        'Username: '.$message->getChat()->getUsername()."\n" .
                        'Firstname: '.$message->getChat()->getFirstName()."\n" .
                        'Lastname: '.$message->getChat()->getLastName()."\n"
                    );

                    break;
                case ($message->getText() === 'Add account'):
//
                    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([array_values(UserAccount::PLATFORMS)], true);
                    $answer = $bot->sendMessage($message->getChat()->getId(), 'Please choose account exchange', null, false, null, $keyboard);
//                    switch ($answer->getReplyToMessage()->getText()) {
//                        case UserAccount::PLATFORM_ID_BINANCE:
//                            $this->sendMessage('You selected binance');
//                            break;
//                        case UserAccount::PLATFORM_ID_BITMEX:
//                            $this->sendMessage('You selected bitmex');
//                            break;
//                        default:
//                            $this->sendMessage('Invalid exchange selected');
//                    }

                    break;
                    $this->sendMessage(
                        'Chat id: '.$message->getChat()->getId()."\n" .
                        'Username: '.$message->getChat()->getUsername()."\n" .
                        'Firstname: '.$message->getChat()->getFirstName()."\n" .
                        'Lastname: '.$message->getChat()->getLastName()."\n"
                    );

                    break;
                case ($message->getText() === 'register'):
//                    $userAccount = new UserAccount();
//                    $userAccount->user_id = UserAccount::PLATFORM_ID_BINANCE;
//                    $userAccount->platform_id = 2;
//                    $userAccount->platform_user_id = 3;
//                    $userAccount->api_public_key = 'aa';
//                    $userAccount->api_secret_key = 'bb';
//                    $userAccount->is_enabled = true;
//                    $userAccount->save();
//                    $this->sendMessage('created');
//                    break;
                    $user = User::find($message->getChat()->getUsername());
                    if ($user) {
                        $this->sendMessage('User already registered. Id: '.$user->id);
                        break;
                    }

                    if (null !== $this->registerUser($message->getChat())) {
                        $this->sendMessage('Successfully registered');
                    } else {
                        $this->sendMessage('Registration failed');
                    }

                    break;
                case ($this->matchCommand($message->getText(), 'Grand total balance')):
                    $this->checkChatIsCorrect($message->getChat()->getId());
                    $this->returnGrandTotals();

                    break;
                case ($this->matchCommand($message->getText(), 'B1 Spot balance')):
                    $this->checkChatIsCorrect($message->getChat()->getId());
                    $this->returnPortfolioTotals();

                    break;
                case ($this->matchCommand($message->getText(), 'B1 Spot coins')):
                    $this->checkChatIsCorrect($message->getChat()->getId());
                    $this->returnBinanceSpotCoins();
                    break;
                case ($this->matchCommand($message->getText(), 'B1 Futures balance')):
                    $this->checkChatIsCorrect($message->getChat()->getId());
                    $this->returnBinanceFuturesUsdMBalanceMessage(Binance::getBinanceKey('BINANCE_FUT1_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT1_TOKEN_SECRET'));
                    break;
                case ($this->matchCommand($message->getText(), 'B1 Futures coins')):
                    $this->checkChatIsCorrect($message->getChat()->getId());
                    $this->returnBinanceFuturesOpenPositions(Binance::getBinanceKey('BINANCE_FUT1_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT1_TOKEN_SECRET'));
                    break;
                case ($this->matchCommand($message->getText(), 'B2 Futures balance')):
                    $this->checkChatIsCorrect($message->getChat()->getId());
                    $this->returnBinanceFuturesUsdMBalanceMessage(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'));
                    break;
                case ($this->matchCommand($message->getText(), 'B2 Futures coins')):
                    $this->checkChatIsCorrect($message->getChat()->getId());
                    $binance->returnBinanceFuturesOpenPositions(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'));
                    break;
                case ($this->matchCommand($message->getText(), 'B2 Futures o.orders')):
                    $this->checkChatIsCorrect($message->getChat()->getId());
                    $this->returnBinanceFuturesOpenOrders(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'));
                    break;
                case ($this->matchCommand($message->getText(), 'B2 profit')):
                    $this->checkChatIsCorrect($message->getChat()->getId());

                    $this->returnBinanceFutures2Profit($binance);
                    break;
                case ($this->matchCommand($message->getText(), 'Toggle admin')):
                    $this->checkChatIsCorrect($message->getChat()->getId());
                    if ($this->toggleAdminMode()) {
                        $this->sendMessage('Admin mode ON');
                    } else {
                        $this->sendMessage('Admin mode OFF');
                    }
                    break;
            }
        }, function () {
            return true;
        });

        $bot->run();
    }

    public function returnGrandTotals(): void
    {
        $grandTotal = 0;
        $grandAvailableTotal = 0;

        // SPOT 1
//        $api = $this->getBinanceSpotApiClient();
        $api = $this->getBinanceSpotApiClient(Binance::getBinanceKey('BINANCE_SPOT1_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_SPOT1_TOKEN_SECRET'));
        $ticker = $api->prices();
        $balances = $api->balances($ticker);
        $btcPrice = $api->price('BTCUSDT');
        $spotTotal = $api->btc_total * $btcPrice;

        $grandTotal += $spotTotal;
        $grandAvailableTotal += $spotTotal;

        // FEATURES 1
        $api = $this->getBinanceFuturesUsdMApiClient(Binance::getBinanceKey('BINANCE_FUT1_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT1_TOKEN_SECRET'));
        $result = $api->user()->getAccount();

        $grandTotal += $result['totalWalletBalance'];
        $grandAvailableTotal += $result['availableBalance'];

        // SPOT 2
        $api = $this->getBinanceSpotApiClient(Binance::getBinanceKey('BINANCE_SPOT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_SPOT2_TOKEN_SECRET'));
        $ticker = $api->prices();
        $balances = $api->balances($ticker);
//        $btcPrice = $api->price('BTCUSDT');
        $spotTotal = $api->btc_total * $btcPrice;

        $grandTotal += $spotTotal;
        $grandAvailableTotal += $spotTotal;

        // FEATURES 2
        // USD-M
        $usdBalance = $this->returnBinanceFuturesUsdMBalance(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'));
        $grandTotal += $usdBalance['total'];
        $grandAvailableTotal += $usdBalance['available'];
        // COIN-M
        $coinBalance = $this->returnBinanceFuturesCoinMBalance(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'));
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

        $bot->sendMessage($chat->getId(), Emoji::wavingHand() . ' Welcome back '. ($user->first_name ?? '@'.$user->username) .'!'. ($this->isAdminMode() ? ' '. Emoji::mechanic() : ''), null, false, null, $keyboard);
    }


    private function returnBinanceFuturesUsdMBalanceMessage(string $keyPublic, string $keySecret): void
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
        $bot->sendMessage(Binance::getChatId(), $msg, null, false, null, $keyboard);
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
        $bot->sendMessage(Binance::getChatId(), $msg, null, false, null, $keyboard);
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
//        $bot->sendMessage(Binance::getChatId(), 'Seconds: '.$secondsPassed);

        if ($secondsPassed > 30) {
            unlink($fileName);
            return false;
        } else {
            return true;
        }
    }

    public function checkChatIsCorrect(int $chatId): void
    {
        if (Binance::getChatId() !== $chatId) {
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
        return new TelegramClient(Binance::getTelegramBotToken());
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
        $bot->sendMessage(Binance::getChatId(), $message);
    }

    private function matchCommand(string $commandText, string $command): bool
    {
        return $command === mb_substr($commandText, mb_strlen($commandText) - mb_strlen($command));
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

    public function returnBinanceFutures2Profit(Binance $binance): void
    {

        $msg = '';

//                    $dtFrom = (new \DateTime())
//                        ->setTimezone(new \DateTimeZone('Europe/Helsinki'))
//                        ->sub(new \DateInterval('P1D'))
//                        ->setTime(0,0, 0);
//                    $dtTill = (clone $dtFrom)->add(new \DateInterval('P1D'));
//                    $incomeYesterday = $this->getIncomeForPeriod(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'), $dtFrom, $dtTill);
//
//                    $dtFrom = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Helsinki'))->setTime(0,0, 0);
//                    $dtTill = (clone $dtFrom)->add(new \DateInterval('P1D'));
//                    $incomeToday = $this->getIncomeForPeriod(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'), $dtFrom, $dtTill);
//
//                    $msg = sprintf('Yesterday: %.2f', $incomeYesterday)."\n";
//                    $msg .= sprintf('Today: %.2f', $incomeToday);

        //https://github.com/jenssegers/laravel-mongodb#dates
        $user = User::first();
        $userAccount = $user->accounts()->first();
        $userAccountId = $userAccount->id;
//                    $balances = $user->accounts()->first()->balances()->orderBy('date', 'ASC')->get();


//$this->sendMessage('Acc id:'.$balances = $user->accounts()->first()->id);

//                    $totalsPerMonthBalances = UserBalance::raw(function ($collection) {
//                        return $collection->aggregate([
//                            [
//                                '$group' => [
//                                    "_id" => ['$month'=>'$created_at'],
//                                    'user_account_id' => ['$first' => '$user_account_id'],
//                                    'vsego' => ['$sum' => '$profit']
//                                ]
//                            ],
//
//                        ]);
//                    });
//                    $totalsPerMonthBalances = $totalsPerMonthBalances
//                        ->where(
//                            'user_account_id', '=',
//                            $userAccountId
//                            //'60ba0ab677b7ce059908071a'
//                        )
//                        ->sortBy('_id')
//                    ;

        /** @var Collection $totalsPerMonthBalancesRecords */
//                    $totalsPerMonthBalancesRecords = $totalsPerMonthBalances->pluck('vsego', '_id');
//                    foreach ($totalsPerMonthBalancesRecords as $monthNum => $profit) {
//                        $dateObj   = \DateTime::createFromFormat('!m', $monthNum);
//                        $monthName = $dateObj->format('F');
//                        $msg .= sprintf('%s: %.2f', $monthName, $profit)."\n";
//                    }
//                    $msg .= "\n";

        // --- income today
        $dtFrom = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Helsinki'))->setTime(0,0, 0);
        $dtTill = (clone $dtFrom)->add(new \DateInterval('P1D'));

        $incomeToday = $binance->getIncomeForPeriod(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'), $dtFrom, $dtTill);

        // --- Profits by months
        $currentMonthNum = (int)date('m');
        foreach (range(1, $currentMonthNum) as $monthNum) {

            $monthFirstDate = \DateTime::createFromFormat('Y-m-d', sprintf('%d-%d-%d', date('Y'), $monthNum, 1));
            $monthFirstDate->setTimezone(new \DateTimeZone('Europe/Helsinki'));
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

            if ($monthNum === 7) {
                $endBalance += 501; // withdrawal to myself
            }

            if ($monthNum === $currentMonthNum) {
                $endBalance += $incomeToday;
            }

            $monthProfit = $endBalance - $startBalance;
//
            $profitPercent = $monthProfit * 100 / $startBalance;

//                        $msg .= sprintf('%s: %.2f - %.2f', $monthName, $startBalance, $endBalance)."\n";
            $msg .= sprintf('%s: %.2f %.2f%%', $monthName, $monthProfit, $profitPercent)."\n";
        }
        $msg .= "\n";

        //------

        $monthDtFrom = (new \DateTime())
            ->setTimezone(new \DateTimeZone('Europe/Helsinki'))
            ->modify('first day of this month')
            ->modify('-1 day')
//                        ->sub(new \DateInterval('P1D'))
            ->setTime(0,0, 0);


        $balances = $userAccount->balances()->where(
            'date', '>=',
            $monthDtFrom
        )->get();

        $monthBalances = $userAccount->balances()->where(
            'date', '>=',
            $monthDtFrom
        )->get();
        $totalThisMonth = 0;
        $firstMonthDayBalance = 0;
        foreach($monthBalances as $k=>$balance) {
            $totalThisMonth += $balance->profit;
            if (!$k) {
                $firstMonthDayBalance = $balance->balance;
            }
        }


        $prevBalanceAmount = 0;
        $incomeThisMonthDynamic = 0;

        foreach($balances as $balance) {
            $prevBalanceAmount = $prevBalanceAmount ?: $balance->balance - $balance->profit;
            $dt = new \DateTime($balance->date);
            $balanceAmountDiff = $prevBalanceAmount ? $balance->balance - $prevBalanceAmount : $balance->profit;
            $incomeThisMonthDynamic += $balanceAmountDiff;
            $profitPercent = $balanceAmountDiff > 0 ? $balanceAmountDiff * 100 / $prevBalanceAmount : 0;
            $prevBalanceAmount = $balance->balance;
            $msg .= sprintf('%s: %.2f %.2f%% %.2f', $dt->format('d-m-Y'), $balance->profit, $profitPercent, $balance->balance)."\n";
        }

        $profitPercentToday = $balance->balance && $incomeToday ? $incomeToday * 100 / $balance->balance : 0;

        $currentBalance = $binance->returnBinanceFuturesUsdMBalance(Binance::getBinanceKey('BINANCE_FUT2_TOKEN_PUB'), Binance::getBinanceKey('BINANCE_FUT2_TOKEN_SECRET'));

        $currentDate = (new \DateTime)->setTimezone(new \DateTimeZone('Europe/Helsinki'));
        $msg .= sprintf('%s: %.2f %.2f%% %.2f', $currentDate->format('d-m-Y'), $incomeToday, $profitPercentToday, $currentBalance['total'])."\n";

//                    $incomeThisMonth = $totalThisMonth + $incomeToday;
        $incomeThisMonth = $currentBalance['total'] - $firstMonthDayBalance;

        $profitPercentThisMonth = !empty($balances[0]) && is_object($balances[0]) ? $incomeThisMonth * 100 / $balances[0]->balance : 0;


        $msg .= sprintf('This month: %.2f %.2f%%', $incomeThisMonth, $profitPercentThisMonth);
        $this->sendMessage($msg);
    }
}
