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
        $text = $lang['guid_text'].$lang['bot_username'];
        $video_path = $lang['video_path'];  
        $video_caption = $lang['video_caption'];
        $inline_keyboard = new InlineKeyboard([
            ['text' => $lang['jame_rahnama'], 'url' => $lang['site_url']],
        ]);

       if ($video_path) {
            Request::sendVideo([
                'chat_id' => $chat_id,
                'video'   => $video_path,
                'caption' => $video_caption,
            ]);
        } else {
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => $lang['video_not_found'],  
            ]);
        }
        return Request::sendMessage([
            'chat_id'      => $chat_id,
            'text'         => $text,
            'reply_markup' => $inline_keyboard,
        ]);
    }
}
