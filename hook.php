<?php
// Load composer
require __DIR__ . '/vendor/autoload.php';
use Longman\TelegramBot\Request;

$bot_api_key  = 'your:bot_api_key';
$bot_username = 'username_bot';

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

    // Handle telegram webhook request
    $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Silence is golden!
    // log telegram errors
    // echo $e->getMessage();
}
//Test Bot
$data = json_decode(file_get_contents("php://input"), true);
$chat_id = $data['message']['chat']['id'];

// Send message to user
Request::sendMessage(['chat_id' => $chat_id, 'text' => 'Your message has been received!']);