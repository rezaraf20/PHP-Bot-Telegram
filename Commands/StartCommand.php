<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class StartCommand extends UserCommand
{
    protected $name = 'start';
    protected $description = 'دستور شروع';
    protected $usage = '/start';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

         
        $lang = require __DIR__ . '/../lang.php';

         
        $live_prices = \get_live_prices();  

        $text = $lang['welcome_message'] ;
	 
         
        $keyboard = [
            [$lang['live_prices']],
           					[$lang['wage_calculation'], $lang['coin_analysis']],
							[$lang['customlink'],$lang['profile']]
             
        ];

        $reply_markup = [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ];
        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
            'reply_markup' => json_encode($reply_markup),
        ];

        Request::sendMessage($data);
		return $this->getTelegram()->executeCommand('customlink');

    }
}
?>
