<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

require_once __DIR__ . '/../wp-functions.php';

class CoinAnalysisCommand extends UserCommand
{
    protected $name = 'coinanalysis'; // نام دستور با حروف کوچک و بدون زیرخط
    protected $description = 'تحلیل سکه';
    protected $usage = '/coinanalysis';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        // بارگذاری رشته‌های متنی
        $lang = require __DIR__ . '/../lang.php';

        // بررسی ثبت شماره موبایل
        $user_mobile = \get_user_mobile($chat_id);

        if (empty($user_mobile)) {
            $text = $lang['must_register_mobile'];

            // تنظیم دستور بعدی پس از ثبت‌نام
            $this->getTelegram()->setCommandConfig('registermobile', ['next_command' => 'coinanalysis']);

            // ارسال پیام به کاربر
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => $text,
            ]);

            // اجرای دستور ثبت شماره موبایل
            return $this->getTelegram()->executeCommand('registermobile');
        }

        // دریافت داده‌های سکه
        $coin_data = get_coin_data(); // این تابع را پیاده‌سازی کنید
		$okornot = $coin_data['price'] / $coin_data['yekprice'];
		$okornottext;
		if($okornot >= 11.5){
			$okornottext=$lang['notinterest'];
		}
		if($okornot <= 9.7){
			$okornottext = $lang['interest'];
		}
        $text = sprintf($lang['coin_analysis_result'],
		 number_format($coin_data['priceimam']), 
		 $coin_data['bubble_percentage_imam'], 
		 number_format($coin_data['bubbleimam']),
		 $okornottext,
		 );
		 $text .= sprintf($lang['coin_analysis_result_tamam'],
		 number_format($coin_data['pricetamam']), 
		 $coin_data['bubble_percentagetamam'], 
		 number_format($coin_data['bubbletamam']),
		 );
		 $text .= sprintf($lang['coin_analysis_result_nim'],
		 number_format($coin_data['pricenim']), 
		 $coin_data['bubble_percentagenim'], 
		 number_format($coin_data['bubblenim']),
		 );
		 $text .= sprintf($lang['coin_analysis_result_rob'],
		 number_format($coin_data['pricerob']), 
		 $coin_data['bubble_percentagerob'], 
		 number_format($coin_data['bubblerob']),
		 $lang['bot_username'],
		 );
		$text .= sprintf($lang['extra-pm'],$coin_data['time']);

        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text'    => $text,
        ]);
    }
}
?>
