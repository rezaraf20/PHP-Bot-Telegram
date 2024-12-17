<?php
namespace Longman\TelegramBot\Handlers;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
class BackButtonHandler
{
    public static function handleBackButton($chat_id)
    {
		$lang = require __DIR__ . '/../lang.php';
         
        $keyboard_main = new Keyboard([$lang['live_prices']],
           					[$lang['wage_calculation'], $lang['coin_analysis']],
							[$lang['customlink'],$lang['profile']]);
        $keyboard_main->setResizeKeyboard(true);
        $keyboard_main->setOneTimeKeyboard(false);  

        $data = [
            'chat_id' => $chat_id,
            'text'    => 'لطفاً یکی از گزینه‌ها را انتخاب کنید:',  
            'reply_markup' => $keyboard_main,
        ];
        Request::sendMessage($data);
    }
}
