<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Str;
use GuzzleHttp;

/**
 * php artisan telegram:crypto-portfolio-bot
 */
class CryptoPortfolioBotCommand extends Command
{
    protected $signature = 'telegram:crypto-portfolio-bot';
    protected $description = "Runs crypto portfolio bot daemon";

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
      $token = '1663707830:AAH_fE8lNqVG15etdCYg_C05ven4jPPUZJw';
	$bot = new \TelegramBot\Api\Client($token);
	
	$bot->command('start', function ($message) use ($bot) {
    		$answer = 'Welcome!';
    		$bot->sendMessage($message->getChat()->getId(), $answer);
	});

	$bot->command('help', function ($message) use ($bot) {
    		$answer = 'Commands:';
		//
    		$bot->sendMessage($message->getChat()->getId(), $answer);
	});

	$bot->run();

    }
}
