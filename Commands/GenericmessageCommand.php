<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;

class GenericmessageCommand extends SystemCommand
{
    protected $name = 'genericmessage';
    protected $description = 'مدیریت پیام‌های عمومی';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
		error_log('GenericmessageCommand: Checking for active conversation.');

		if ($this->executeActiveConversation()) {
        return Request::emptyResponse();
    		}
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text = trim($message->getText(true));

        // بارگذاری رشته‌های متنی
        $lang = require __DIR__ . '/../lang.php';

        switch ($text) {
            case $lang['live_prices']:
                // دریافت قیمت‌های لحظه‌ای
                $live_prices = \get_live_prices();
                $response_text = $live_prices;
                break;

            case $lang['wage_calculation']:
                // اجرای دستور محاسبه اجرت
                return $this->getTelegram()->executeCommand('wagecalculation');

            case $lang['coin_analysis']:
                // اجرای دستور تحلیل سکه
                return $this->getTelegram()->executeCommand('coinanalysis');

            case $lang['registration']:
                // اجرای دستور ثبت نام
                return $this->getTelegram()->executeCommand('registration');

			case $lang['profile']:
                // اجرای دستور پروفایل
                return $this->getTelegram()->executeCommand('registration');

			case $lang['customlink']:
                // اجرای دستور لینک
                return $this->getTelegram()->executeCommand('customlink');

            case $lang['back']:
                // اجرای دستور بازگشت یا هر عمل دیگری
                $response_text = $lang['welcome_message'];
                break;

            default:
                $response_text = $lang['unknown_command'];
                break;
        }

        $data = [
            'chat_id' => $chat_id,
            'text'    => $response_text,
        ];

        return Request::sendMessage($data);
    }
}
?>
