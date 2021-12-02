<?php

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});
$router->get('/tst', 'TestController@process');
//$router->get('/tst', function () use ($router) {
//    $api = new Binance\API('XoomMOUDQO4LfJ2gWkbxp6Bhz7u7p73PPdQ3tUyDZmnZAEKUj2f9Q3Fsh0SVeadc',
//        'bMcsmJibc9tyVK041UDUlYqT6V0dOMC4OHcfzSVO0AiCnDYuMQN0vkkqfIoByYIn');
//    $ticker = $api->prices();
//    $balances = $api->balances($ticker);
//    dd($balances);
//});
$router->post('/webhook/tradingview', function () use ($router) {
    $token = '1663707830:AAH_fE8lNqVG15etdCYg_C05ven4jPPUZJw';
    $bot = new \TelegramBot\Api\Client($token);
    $msg = 'Message from telegram';
    $bot->sendMessage(475279505, $msg);
});

$router->post('/webhook/crtypto-portfolio-bot', 'CryptoPortfolioController@process');
//$router->post('/webhook/crtypto-portfolio-bot', function () use ($router) {
//    $token = '1663707830:AAH_fE8lNqVG15etdCYg_C05ven4jPPUZJw';
//    $bot = new \TelegramBot\Api\Client($token);
//
//    $bot->command('start2', function ($message) use ($bot) {
//        $answer = 'Welcome!';
//        $bot->sendMessage($message->getChat()->getId(), $answer);
//    });
//
//    $bot->command('help', function ($message) use ($bot) {
//        $answer = 'Commands:';
//        //
//        $bot->sendMessage($message->getChat()->getId(), $answer);
//    });
//
//    $bot->command('hello', function ($message) use ($bot) {
//        $text = $message->getText();
//        $param = str_replace('/hello ', '', $text);
//        $answer = 'Unknown command';
//        if (!empty($param)) {
//            $answer = 'Hello, ' . $param;
//        }
//        $bot->sendMessage($message->getChat()->getId(), $answer);
//    });
//
//    $bot->command('test', function ($message) use ($bot) {
//        $buttons = [];
//        $buttons[] = ['text' => '🤖 Prev', 'callback_data' => '/post_1'];
//        $buttons[] = ['text' => '🚀 Next', 'callback_data' => '/post_3'];
//
//        $bot->sendMessage(
//            $message->getChat()->getId(),
//            'test text',
//            'markdown',
//            false,
//            null,
//            new InlineKeyboardMarkup([$buttons])
//        );
//    });
//
//    $bot->command('test2', function ($message) use ($bot) {
//        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array(array("one", "two", "three")),
//            true); // true for one-time keyboard
//
//        $bot->sendMessage($message->getChat()->getId(), 'message text', null, false, null, $keyboard);
//
//    });
//
//    $bot->command('test3', function ($message) use ($bot) {
//        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array(array("aa", "bbb", "ccc")),
//            true); // true for one-time keyboard
//
//        $bot->sendMessage($message->getChat()->getId(), 'message text2', null, false, null, $keyboard);
//
//    });
//
//
//    $bot->command('start2', function ($message) use ($bot) {
//        $buttons = [];
//        $buttons[] = ['text' => '🚀 Portfolio in BTC', 'callback_data' => '/stats'];
//
//        $bot->sendMessage(
//            $message->getChat()->getId(),
//            'test text',
//            'markdown',
//            false,
//            null,
//            new InlineKeyboardMarkup([$buttons])
//        );
//    });
//
//    $bot->command('start', function ($message) use ($bot) {
//
//        $buttons = [['🚀 Portfolio in BTC']];
//
//        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($buttons, true); // true for one-time keyboard
//
//        $bot->sendMessage($message->getChat()->getId(), 'Welcome!', null, false, null, $keyboard);
//    });
//
//    $bot->command('stats', function ($message) use ($bot) {
//        if (475279505 !== $message->getChat()->getId()) {
//            $bot->sendMessage($message->getChat()->getId(), 'No data');
//        }
//
//        $api = new Binance\API("XoomMOUDQO4LfJ2gWkbxp6Bhz7u7p73PPdQ3tUyDZmnZAEKUj2f9Q3Fsh0SVeadc",
//            "bMcsmJibc9tyVK041UDUlYqT6V0dOMC4OHcfzSVO0AiCnDYuMQN0vkkqfIoByYIn");
//        $ticker = $api->prices();
//        $balances = $api->balances($ticker);
//        $bot->sendMessage($message->getChat()->getId(), \sprintf('Estimated total: %s BTC', $api->btc_value));
//    });
//
//    $bot->on(function (\TelegramBot\Api\Types\Update $update) use ($bot) {
//        $message = $update->getMessage();
//        $id = $message->getChat()->getId();
//        switch (true) {
//            case (strpos($message->getText(), 'Portfolio in BTC') > 0):
//                if (475279505 !== $message->getChat()->getId()) {
//                    $bot->sendMessage($message->getChat()->getId(), 'No data');
//                }
//
//                $api = new Binance\API("XoomMOUDQO4LfJ2gWkbxp6Bhz7u7p73PPdQ3tUyDZmnZAEKUj2f9Q3Fsh0SVeadc",
//                    "bMcsmJibc9tyVK041UDUlYqT6V0dOMC4OHcfzSVO0AiCnDYuMQN0vkkqfIoByYIn");
//                $ticker = $api->prices();
//                $balances = $api->balances($ticker);
//                $bot->sendMessage($message->getChat()->getId(),
//                    \sprintf('Binance estimated total: %s BTC', $api->btc_value));
//                $btcPrice = $api->price('BTCUSDT');
//                //$bot->sendMessage($message->getChat()->getId(), "Price of BNB: {$price} BTC.");
//                $bot->sendMessage($message->getChat()->getId(),
//                    \sprintf('Binance estimated total: %s USD', $api->btc_value * $btcPrice));
//                break;
//        }
//    }, function () {
//        return true;
//    }
//    );
//
//    $bot->run();
//});
