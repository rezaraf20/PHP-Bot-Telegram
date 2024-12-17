<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Handlers\BackButtonHandler;

require_once __DIR__ . '/../wp-functions.php';

class WageCalculationCommand extends UserCommand
{
    protected $name = 'wagecalculation';
    protected $description = 'محاسبه اجرت';
    protected $usage = '/wagecalculation';
    protected $version = '1.0.0';

    private $conversation;

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $text = trim($message->getText());

         
        $lang = require __DIR__ . '/../lang.php';

         
        $user_mobile = \get_user_mobile($chat_id);

        if (empty($user_mobile)) {
            $text = $lang['must_register_mobile'];

             
            $this->getTelegram()->executeCommand('registermobile');

            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => $text,
            ]);
        }

         
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        if (!is_array($notes)) {
            $notes = [];
        }

        $state = $notes['state'] ?? 0;

        $keyboard_back = new Keyboard(['بازگشت']);
        $keyboard_back->setResizeKeyboard(true);

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

        switch ($state) {
            case 0:  
                $notes['state'] = 1;
                $this->conversation->update();

                return Request::sendMessage([
                    'chat_id'      => $chat_id,
                    'text'         => $lang['enter_gold_weight'],
                    'reply_markup' => $keyboard_back,
                ]);

            case 1:  
                if (is_numeric($text)) {
                    $notes['weight'] = floatval($text);  
                    $notes['state'] = 2;
                    $this->conversation->update();

                    return Request::sendMessage([
                        'chat_id'      => $chat_id,
                        'text'         => $lang['enter_product_price'],
                        'reply_markup' => $keyboard_back,
                    ]);
                }

                return Request::sendMessage([
                    'chat_id'      => $chat_id,
                    'text'         => $lang['invalid_input'],
                    'reply_markup' => $keyboard_back,
                ]);

            case 2:  
                if (is_numeric($text)) {
                    $prdPrc = floatval($text);  
                    $weight = $notes['weight'];

                     
                    $api_data = getApiData();
                    $dailyGoldPrcApi = str_replace(',', '', $api_data['YekGram18']);
                    $dailyGoldPrcApi = floatval($dailyGoldPrcApi);
					
					$gpttimeRead = $api_data['TimeRead'];
	list($gpdate, $gptime) = explode(' ', $gpttimeRead);
function convert_gregorian_to_jalali($gpttimeRead) {
    list($gpdate, $gptime) = explode(' ', $gpttimeRead);
    list($gy, $gm, $gd) = explode('/', $gpdate);
    list($jy, $jm, $jd) = gregorian_to_jalali($gy, $gm, $gd);

    return sprintf('%04d/%02d/%02d %s', $jy, $jm, $jd, $gptime);
}
$jalali_date_time = convert_gregorian_to_jalali($gpttimeRead);

                     
                    $wage_percentage = calculate_gold_wage_percentage($prdPrc, $weight, $dailyGoldPrcApi);
                    $wage_amount = calculate_gold_wage_amount($prdPrc, $weight, $dailyGoldPrcApi);
                    $gold_value = $weight * $dailyGoldPrcApi;

                     
                    $response_text = sprintf(
                        $lang['wage_calculation_result'],
						$weight,
						$prdPrc,
                        round($wage_percentage, 2),
                        number_format($wage_amount),
                        number_format($gold_value),
                        $lang['bot_username'],
                        );
					$response_text .= sprintf(
						"\n".$lang['extra-pm'],
						$jalali_date_time,
					);

                    Request::sendMessage([
                        'chat_id' => $chat_id,
                        'text'    => $response_text,
                    ]);

                     
                    $notes['state'] = 1;  
                    $this->conversation->update();

                    return Request::sendMessage([
                        'chat_id'      => $chat_id,
                        'text'         => $lang['enter_gold_weight_again'],
                        'reply_markup' => $keyboard_back,
                    ]);
                }

                return Request::sendMessage([
                    'chat_id'      => $chat_id,
                    'text'         => $lang['invalid_input'],
                    'reply_markup' => $keyboard_back,
                ]);
        }

        return Request::emptyResponse();
    }
}
