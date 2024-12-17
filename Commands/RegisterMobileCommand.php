<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Handlers\BackButtonHandler;

 
 

require_once '/home/momg/domains/momg.ir/public_html/wp-load.php';

class RegisterMobileCommand extends UserCommand
{
    protected $name = 'registermobile';
    protected $description = 'ثبت شماره موبایل';
    protected $usage = '/registermobile';
    protected $version = '1.0.0';

    private $conversation;

    public function execute(): ServerResponse
    {
        require_once __DIR__ . '/../wp-functions.php';

        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $contact = $message->getContact();
        $text = trim($message->getText(true));

        $lang = require __DIR__ . '/../lang.php';
        $command_params = $this->getConfig('next_command');

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());
        $notes = &$this->conversation->notes;
        if (!is_array($notes)) {
            $notes = [];
        }

        $state = $notes['state'] ?? 0;

        switch ($state) {
            case 0:
			 if ($text === 'بازگشت') {
    BackButtonHandler::handleBackButton($chat_id);

     
    if (!isset($this->conversation)) {
        $this->conversation = new \Longman\TelegramBot\Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId(),
            $this->getName()
        );
    }

    $this->conversation->stop();  
    return Request::emptyResponse();
}
                $notes['state'] = 1;
                $this->conversation->update();

                 
                $keyboard = [
                    'keyboard' => [
                        [
                            ['text' => $lang['share_mobile_button'], 'request_contact' => true],
                        ],
						[
            			    ['text' => $lang['back']],
        			    ],
                    ],
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true,
                ];

                $data = [
                    'chat_id'      => $chat_id,
                    'text'         => $lang['enter_mobile'],
                    'reply_markup' => json_encode($keyboard),
                ];

                return Request::sendMessage($data);

            case 1:
                if ($contact !== null && !empty($contact->getPhoneNumber())) {
                    $mobile = $contact->getPhoneNumber();
					if (strpos($mobile, '98') === 0) {
           			 $mobile = '0' . substr($mobile, 2);
       				 }elseif (strpos($mobile, '+98') === 0) {
           			 $mobile = '0' . substr($mobile, 3);
       				 }

                    \save_user_mobile($chat_id, $mobile);

                    $this->conversation->stop();

                    $data = [
                        'chat_id' => $chat_id,
                        'text'    => $lang['mobile_registered'],
                    ];
                    Request::sendMessage($data);

                     
                    $keyboard = [
                        'keyboard' => [
                            [$lang['live_prices']],
           					[$lang['wage_calculation'], $lang['coin_analysis']],
							[$lang['customlink'],$lang['profile']]
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => false,
                    ];

                    $data = [
                        'chat_id'      => $chat_id,
                        'text'         => $lang['registration_complete'],
                        'reply_markup' => json_encode($keyboard),
                    ];

                    Request::sendMessage($data);

                    if (!empty($command_params)) {
                        return $this->getTelegram()->executeCommand($command_params);
                    }

                    return Request::emptyResponse();
                } else {
					if ($text === 'بازگشت') {
    BackButtonHandler::handleBackButton($chat_id);

     
    if (!isset($this->conversation)) {
        $this->conversation = new \Longman\TelegramBot\Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId(),
            $this->getName()
        );
    }

    $this->conversation->stop();  
    return Request::emptyResponse();
}
                     
                    $keyboard = [
                        'keyboard' => [
                            [
                                ['text' => $lang['share_mobile_button'], 'request_contact' => true],
                            ],
							[
            			    ['text' => $lang['back']],
        			    ],
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true,
                    ];

                    $data = [
                        'chat_id'      => $chat_id,
                        'text'         => $lang['invalid_mobile'],
                        'reply_markup' => json_encode($keyboard),
                    ];

                    return Request::sendMessage($data);
                }

            default:
                $this->conversation->stop();

                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $lang['error_occurred'],
                ];

                return Request::sendMessage($data);
        }
    }
}
