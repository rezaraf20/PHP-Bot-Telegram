<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

require_once __DIR__ . '/../wp-functions.php';

class RegistrationCommand extends UserCommand
{
    protected $name = 'registration';
    protected $description = 'ثبت نام';
    protected $usage = '/registration';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text = trim($message->getText());

         
        $lang = require __DIR__ . '/../lang.php';

         
        $user_mobile = get_user_mobile($chat_id);

        if (empty($user_mobile)) {
             
            $text = $lang['must_register_mobile'];

             
            $this->getTelegram()->setCommandConfig('registermobile', ['next_command' => 'registration']);

             
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => $text,
            ]);

             
            return $this->getTelegram()->executeCommand('registermobile');
        }

         
        $response_text = sprintf(
            "📋 شماره موبایل شما:\n📞 %s",
            $user_mobile
        );

        $keyboard = [
                        'keyboard' => [
                            [$lang['live_prices']],
           					[$lang['wage_calculation'], $lang['coin_analysis']],
							[$lang['customlink'],$lang['profile']]
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => false,
                    ];
        

        return Request::sendMessage([
            'chat_id'      => $chat_id,
            'text'         => $response_text,
            'reply_markup' => $keyboard,
        ]);
    }
}


