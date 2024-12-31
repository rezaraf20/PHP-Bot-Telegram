<?php
require __DIR__ . '/vendor/autoload.php';
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Entities\InlineKeyboard;
$lang = require __DIR__ . '/lang.php';
$log_file = __DIR__ . '/debug_log.txt';
$error_file = __DIR__ . '/error_log.txt';


$db_host = 'localhost';
$db_user = '*******';
$db_pass = '************';
$db_name = '********';

$bot_api_key = '**********************************';
$bot_username = '*****_bot';
$channel_id = '@*****';
$cache_file = __DIR__ . '/price_cache.json';

try {
	$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $telegram = new Telegram($bot_api_key, $bot_username);
    Request::initialize($telegram);

    require __DIR__ . '/goldpriceapi.php';
    $current_prices = getApiData(); 
    $gptimeRead = $current_prices['TimeRead'];
list($gpdate, $gptime) = explode(' ', $gptimeRead);

function convert_gregorian_to_jalali($gptimeRead) {
    list($gpdate, $gptime) = explode(' ', $gptimeRead);
    list($gy, $gm, $gd) = explode('/', $gpdate);
    list($jy, $jm, $jd) = gregorian_to_jalali($gy, $gm, $gd);

    return sprintf('%04d/%02d/%02d %s', $jy, $jm, $jd, $gptime);
}
$jalali_date_time = convert_gregorian_to_jalali($gptimeRead);
	$gpmessages = $lang['header'];
	$gpmessages .= 'گرم: '.number_format($current_prices['YekGram18']/1000).'k' .'   دلار: '.number_format($current_prices['Dollar']) . '   سکه:'.number_format($current_prices['SekehTamam']) .'k' ;
$gpmessages .= "

🔸 هر گرم 18 عیار: " . number_format($current_prices['YekGram18']) . " تومان
🔸 خرید طلا 18 عیار: " . number_format($current_prices['KharidMotefaregheh18']) . " تومان
🔸 تعویض طلا 18 عیار: " . number_format($current_prices['TavizMotefaregheh18']) . " تومان
🔸 هر گرم 20 عیار: " . number_format($current_prices['YekGram20']) . " تومان
🔸 هر گرم 21 عیار: " . number_format($current_prices['YekGram21']) . " تومان

🥇 سکه امامی: " . number_format($current_prices['SekehEmam']*1000) . " تومان
🥇 سکه تمام: " . number_format($current_prices['SekehTamam']*1000) . " تومان
🥇 نیم سکه: " . number_format($current_prices['SekehNim']*1000) . " تومان
🥇 ربع سکه: " . number_format($current_prices['SekehRob']*1000) . " تومان
🥇 سکه گرمی: " . number_format($current_prices['SekehGerami']*1000) . " تومان

🇺🇸 دلار: " . number_format($current_prices['Dollar']) . " تومان
🇪🇺 یورو: " . number_format($current_prices['Euro']) . " تومان
🇦🇪 درهم: " . number_format($current_prices['Derham']) . " تومان

🇺🇸 اونس طلا: " . number_format($current_prices['OunceTala']) . " دلار
💰 مظنه: ".number_format(convert_ounce_to_mazane($current_prices['OunceTala'], $current_prices['Dollar']))." تومان";
	$gpmessages .= sprintf("\n".$lang['extra-pm1-ch'],$jalali_date_time);
	$gpmessages .=  $lang['extra-pm2-ch'];
	$gpmessages .=  $lang['extra-pm3'];

	$save_prices = $current_prices;

    // بررسی فایل کش قبلی
    $cached_data = [];
    if (file_exists($cache_file)) {
        $cached_data = json_decode(file_get_contents($cache_file), true);
    }

    // حذف TimeRead از مقایسه‌ها
    unset($current_prices['TimeRead']);
    unset($cached_data['TimeRead']);

	unset($current_prices['OunceTala']);
    unset($cached_data['OunceTala']);

	unset($current_prices['KharidMotefaregheh18']);
    unset($cached_data['KharidMotefaregheh18']);

	unset($current_prices['TavizMotefaregheh18']);
    unset($cached_data['TavizMotefaregheh18']);

	unset($current_prices['YekGram20']);
    unset($cached_data['YekGram20']);

	unset($current_prices['YekGram21']);
    unset($cached_data['YekGram21']);

	unset($current_prices['SekehEmam']);
    unset($cached_data['SekehEmam']);

	unset($current_prices['SekehTamam']);
    unset($cached_data['SekehTamam']);

	unset($current_prices['SekehNim']);
    unset($cached_data['SekehNim']);

	unset($current_prices['SekehRob']);
    unset($cached_data['SekehRob']);

	unset($current_prices['SekehGerami']);
    unset($cached_data['SekehGerami']);

	unset($current_prices['Euro']);
    unset($cached_data['Euro']);

	unset($current_prices['Derham']);
    unset($cached_data['Derham']);

    // مقایسه قیمت‌ها
    $prices_changed = false;
    foreach ($current_prices as $key => $value) {
        if (!isset($cached_data[$key]) || $cached_data[$key] !== $value) {
            $prices_changed = true; 
            break;
        }
    }

    if (!$prices_changed) {
        file_put_contents($log_file, "No changes detected at: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        exit; 
    }

    file_put_contents($cache_file, json_encode($current_prices));

	$inline_keyboard = new InlineKeyboard([
            ['text' => $lang['current-price-ch'], 'url' => $lang['bot-username-ch']],
        ]);

    $result = Request::sendMessage([
        'chat_id' => $channel_id,
        'text'    => $gpmessages,
        'parse_mode' => 'HTML',
		'reply_markup' => $inline_keyboard,
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO prices_log (date, SekehRob, SekehNim, SekehTamam, SekehEmam, YekGram18, Dollar, Euro, Derham, OunceTala) 
        VALUES (NOW(), :SekehRob, :SekehNim, :SekehTamam, :SekehEmam, :YekGram18, :Dollar, :Euro, :Derham, :OunceTala)
    ");
    $stmt->execute([
        ':SekehRob' => $save_prices['SekehRob'],
        ':SekehNim' => $save_prices['SekehNim'],
        ':SekehTamam' => $save_prices['SekehTamam'],
        ':SekehEmam' => $save_prices['SekehEmam'],
        ':YekGram18' => $save_prices['YekGram18'],
        ':Dollar' => $save_prices['Dollar'],
        ':Euro' => $save_prices['Euro'],
        ':Derham' => $save_prices['Derham'],
        ':OunceTala' => $save_prices['OunceTala'],
    ]);

    file_put_contents($log_file, print_r($result, true), FILE_APPEND);

} catch (TelegramException $e) {
    file_put_contents($error_file, $e->getMessage() . "\n", FILE_APPEND);
} catch (PDOException $e) {
    file_put_contents(__DIR__ . '/db_elog.txt', $e->getMessage() . "\n", FILE_APPEND);
}
