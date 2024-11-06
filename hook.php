<?php
ini_set("error_log", __DIR__ . "/error_log.log");
ini_set('display_errors', '1');
error_reporting(E_ALL);
// Load composer
require __DIR__ . '/vendor/autoload.php';
require_once '../wp-load.php';
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

global $wpdb;
$table_name = $wpdb->prefix . 'your_table';

function get_user_registration($chat_id) {
    global $wpdb, $table_name;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE chat_id = %d", $chat_id), ARRAY_A);
}

function update_user_registration($chat_id, $data) {
    global $wpdb, $table_name;
    $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE chat_id = %s", $chat_id));

    if ($existing) {
        $wpdb->update($table_name, $data, ['chat_id' => $chat_id]);
    } else {
        $data['chat_id'] = $chat_id;
        $wpdb->insert($table_name, $data);
    }
}

function register_user_in_wordpress($user_data) {
    $user_id = wp_create_user($user_data['phone'], wp_generate_password(), $user_data['phone'] . '@example.com');
    
    if (is_wp_error($user_id)) {
        error_log("Error creating user: " . $user_id->get_error_message());
        return false;
    }

    wp_update_user([
        'ID' => $user_id,
        'first_name' => $user_data['name'],
        'last_name' => $user_data['lastname'],
        'role' => 'customer'
    ]);

    update_user_meta($user_id, 'telegram_chat_id', $user_data['chat_id']);
    update_user_meta($user_id, 'national_code', $user_data['national_code']);
    update_user_meta($user_id, 'bank_account', $user_data['bank_account']);
    update_user_meta($user_id, 'address', $user_data['address']);

    return true;
}

$data = json_decode(file_get_contents("php://input"), true);
$chat_id = $data['message']['chat']['id'] ?? $data['callback_query']['from']['id'];
$text = trim($data['message']['text'] ?? '');
$callback_data = $data['callback_query']['data'] ?? '';

$user_data = get_user_registration($chat_id);

if (!$user_data) {
    update_user_registration($chat_id, ['step' => 'name']);
    Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً نام خود را وارد کنید.']);
} elseif ($user_data['is_registered'] == 1) {
    Request::sendMessage(['chat_id' => $chat_id, 'text' => 'شما قبلاً ثبت‌نام کرده‌اید!']);
} else {
    $current_step = $user_data['step'];

    if ($current_step == 'name' && !empty($text)) {
        update_user_registration($chat_id, ['name' => $text, 'step' => 'lastname']);
        Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً نام خانوادگی خود را وارد کنید.']);
    } elseif ($current_step == 'lastname' && !empty($text)) {
        update_user_registration($chat_id, ['lastname' => $text, 'step' => 'national_code']);
        Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً کد ملی خود را وارد کنید.']);
    } elseif ($current_step == 'national_code' && !empty($text)) {
        update_user_registration($chat_id, ['national_code' => $text, 'step' => 'phone']);
        Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً شماره موبایل خود را وارد کنید.']);
    } elseif ($current_step == 'phone' && !empty($text)) {
        update_user_registration($chat_id, ['phone' => $text, 'step' => 'bank_account']);
        Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً شماره حساب خود را وارد کنید.']);
    } elseif ($current_step == 'bank_account' && !empty($text)) {
        update_user_registration($chat_id, ['bank_account' => $text, 'step' => 'address']);
        Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً آدرس خود را وارد کنید.']);
    } elseif ($current_step == 'address' && !empty($text)) {
        update_user_registration($chat_id, ['address' => $text, 'step' => 'confirm']);

        $keyboard = [
            'inline_keyboard' => [
                [['text' => 'تأیید و ثبت‌نام', 'callback_data' => 'finalize_registration']]
            ]
        ];
        Request::sendMessage([
            'chat_id' => $chat_id,
            'text'    => 'برای تأیید و ثبت‌نام نهایی دکمه زیر را فشار دهید.',
            'reply_markup' => json_encode($keyboard)
        ]);
    }
}

if ($callback_data == 'finalize_registration') {

	
    if ($user_data) {
     	$user_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE chat_id = %d", $chat_id), ARRAY_A);

   // $user_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE chat_id = %d", $chat_id), ARRAY_A);

    if ($user_data && $user_data['is_registered'] == 0) {
		
        $username = 'user_' . $user_data['chat_id'];
        $email = 'user_' . $user_data['phone'] . '@example.com'; 
        $password = wp_generate_password();

        if (!username_exists($username) && !email_exists($email)) {
            $user_id = wp_create_user($username, $password, $email);

            if (!is_wp_error($user_id)) {
                wp_update_user([
                    'ID' => $user_id,
                    'role' => 'customer',
                ]);

                update_user_meta($user_id, 'first_name', $user_data['name']);
                update_user_meta($user_id, 'last_name', $user_data['lastname']);
                update_user_meta($user_id, 'national_code', $user_data['national_code']);
                update_user_meta($user_id, 'phone', $user_data['phone']);
                update_user_meta($user_id, 'bank_account', $user_data['bank_account']);
                update_user_meta($user_id, 'address', $user_data['address']);
                
				 Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'ثبت‌نام شما در سایت با موفقیت انجام شد و شما به عنوان مشتری ثبت شدید!'
                ]);
            } else {
                Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'خطا در ثبت نام. لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.'
                ]);
            }
        } else {
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => 'این کاربر قبلاً در سیستم ثبت شده است.'
            ]);
        }
		update_user_registration($chat_id, ['is_registered' => 1]);
    } 
	if(!$user_data) {
        Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => 'اطلاعات ثبت نام شما ناقص است. لطفاً دوباره تلاش کنید.'
        ]);
    }

      //  Request::sendMessage(['chat_id' => $chat_id, 'text' => 'ثبت‌نام شما با موفقیت انجام شد!']);
    }
}