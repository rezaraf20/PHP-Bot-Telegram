<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;


class CustomLinkCommand extends UserCommand
{
    protected $name = 'customlink';
    protected $description = 'راهنما';
    protected $usage = '/customlink';

    public function execute(): ServerResponse
    {
        $chat_id = $this->getMessage()->getChat()->getId();
		$lang = require __DIR__ . '/../lang.php';
        // متن پیام
        $text = $lang['guid_text'].$lang['bot_username'];

        // لینک ویدئو
        $video_path = $lang['video_path']; // لینک ویدئوی مورد نظر
        $video_caption = $lang['video_caption'];

        // ایجاد دکمه لینک
        $inline_keyboard = new InlineKeyboard([
            ['text' => $lang['jame_rahnama'], 'url' => $lang['site_url']],
        ]);

       if (1) {
            Request::sendVideo([
                'chat_id' => $chat_id,
                'video'   => $video_path,
                'caption' => $video_caption,
            ]);
        } else {
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => $lang['video_not_found'], // متن مناسب در صورت نبود ویدئو
            ]);
        }

        // ارسال متن و دکمه
        return Request::sendMessage([
            'chat_id'      => $chat_id,
            'text'         => $text,
            'reply_markup' => $inline_keyboard,
        ]);
    }
}
