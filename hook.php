<?php 
require __DIR__ . '/vendor/autoload.php';
require_once '../wp-load.php';
require_once __DIR__ . '/Handlers/BackButtonHandler.php';


 
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/goldpriceapi.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;




$bot_api_key  = '******************************************';
$bot_username = 'Bot_bot'; 

try {
     
    $telegram = new Telegram($bot_api_key, $bot_username);

     
    $telegram->addCommandsPaths([__DIR__ . '/Commands']);
	$telegram->setCommandConfig('genericmessage', ['disabled' => true]);
	$mysql_credentials = [
    'host'     => 'localhost',
    'user'     => 'db_user',
    'password' => '*********',
    'database' => 'Db_name',
     
];
$telegram->enableMySql($mysql_credentials);

 
 


     
    $telegram->handle();

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
     
    error_log($e->getMessage());
}

?>
