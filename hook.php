<?php
ini_set("error_log", __DIR__ . "/error_log.log");
ini_set('display_errors', '1');
error_reporting(E_ALL);
// Load composer
require __DIR__ . '/vendor/autoload.php';
require_once '../wp-load.php';
use Longman\TelegramBot\Request;

// ثابت‌ها
define('GOLD_CARAT', 750);
define('COIN_CARAT', 900);
define('COIN_WEIGH', 8.133);
define('COIN_GOLD_WEIGH', 7.3224); 
define('HALF_COIN_WEIGH', 4.0665);
define('HALF_COIN_GOLD_WEIGH', 3.6612); 
define('QUARTER_COIN_WEIGH', 2.03325);
define('QUARTER_COIN_GOLD_WEIGH', 1.8306); 
define('MAZANE_TO_18CARAT', 4.3318);
define('OUNCE_TO_MAZANE', 9.574);

$bot_api_key  = 'bot_token';
$bot_username = 'sample_bot';

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

    // Handle telegram webhook request
    $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Silence is golden!
}

global $wpdb;
$table_name = $wpdb->prefix . 'table_name';

function get_user_registration($chat_id) {
    global $wpdb, $table_name;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE chat_id = %d", $chat_id), ARRAY_A);
}

function update_user_registration($chat_id, $data) {
    global $wpdb, $table_name;
    $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE chat_id = %d", $chat_id));

    if ($existing) {
        $result = $wpdb->update($table_name, $data, ['chat_id' => $chat_id]);
        if ($result === false) {
            error_log("Failed to update user_registration for chat_id: $chat_id. Error: " . $wpdb->last_error);
        } else {
            error_log("Updated user_registration for chat_id: $chat_id with data: " . json_encode($data));
        }
    } else {
        $data['chat_id'] = $chat_id;
        $result = $wpdb->insert($table_name, $data);
        if ($result === false) {
            error_log("Failed to insert user_registration for chat_id: $chat_id. Error: " . $wpdb->last_error);
        } else {
            error_log("Inserted user_registration for chat_id: $chat_id with data: " . json_encode($data));
        }
    }
}

$data = json_decode(file_get_contents("php://input"), true);
$chat_id = $data['message']['chat']['id'] ?? $data['callback_query']['from']['id'];

$text = trim($data['message']['text'] ?? '');
$callback_data = $data['callback_query']['data'] ?? '';

error_log("User input: " . $text);

$user_data = get_user_registration($chat_id);

if (!$user_data) {
    update_user_registration($chat_id, [
        'is_registered' => 0,
        'step' => '',
        'current_action' => '',
        'calc_step' => '',
        'calc_price' => '',
        'calc_weight' => '',
    ]);
    $user_data = get_user_registration($chat_id);
}

if (isset($callback_data) && $callback_data == 'confirm_registration') {
    $text = '/confirm_registration';
}

if ($text == '/start') {
    update_user_registration($chat_id, [
        'is_registered' => $user_data['is_registered'], 
        'step' => '',
        'current_action' => '',
        'calc_step' => '',
        'calc_price' => '',
        'calc_weight' => '',
    ]);
    $user_data = get_user_registration($chat_id); 

    error_log("Handling /start command for chat_id: $chat_id");

    if ($user_data['is_registered'] == 1) {
        display_registered_menu($chat_id);
    } else {
        display_main_menu($chat_id);
    }
    exit;
}

if ($user_data['current_action']) {
    error_log("Current action: " . $user_data['current_action']);
    switch ($user_data['current_action']) {
        case 'calculate_percentage_wage':
            handle_percentage_wage($chat_id, $text);
            exit; 
        case 'calculate_numeric_wage':
            handle_numeric_wage($chat_id, $text);
            exit;
        case 'calculate_coin_difference':
            handle_coin_difference($chat_id, $text);
            exit;
        case 'calculate_coin_bubble':
            handle_coin_bubble($chat_id, $text);
            exit;
    }
}

if (1) {
    //handle_registered_user($chat_id, $text);
	handle_pre_registration($chat_id, $text);
} else {
    handle_pre_registration($chat_id, $text);
}

function handle_registered_user($chat_id, $text) {
    error_log("handle_registered_user called for chat_id: $chat_id with text: $text");
    if (mb_strpos($text, 'قیمت لحظه ای طلا') !== false) {
        showprice($chat_id);
        exit;
    } else {
        display_registered_menu($chat_id);
        exit;
    }
}

function handle_pre_registration($chat_id, $text) {
    error_log("handle_pre_registration called for chat_id: $chat_id with text: $text");
    $user_data = get_user_registration($chat_id);

    $remove_keyboard = [
        'remove_keyboard' => true
    ];

    if (in_array($user_data['step'], ['name', 'lastname', 'national_code', 'phone', 'bank_account', 'address', 'confirm'])) {
        $current_step = $user_data['step'];
        error_log("Current registration step: " . $current_step);

        if ($current_step == 'name' && !empty($text) && $text != '/start') {
            update_user_registration($chat_id, ['name' => $text, 'step' => 'lastname']);
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => 'لطفاً نام خانوادگی خود را وارد کنید.',
                'reply_markup' => json_encode($remove_keyboard)
            ]);
            error_log("Updated step to 'lastname' for chat_id: $chat_id");
            exit;
        } elseif ($current_step == 'lastname' && !empty($text)) {
            update_user_registration($chat_id, ['lastname' => $text, 'step' => 'national_code']);
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => 'لطفاً کد ملی خود را وارد کنید.',
                'reply_markup' => json_encode($remove_keyboard)
            ]);
            error_log("Updated step to 'national_code' for chat_id: $chat_id");
            exit;
        } elseif ($current_step == 'national_code' && !empty($text)) {
            update_user_registration($chat_id, ['national_code' => $text, 'step' => 'phone']);
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => 'لطفاً شماره موبایل خود را وارد کنید.',
                'reply_markup' => json_encode($remove_keyboard)
            ]);
            error_log("Updated step to 'phone' for chat_id: $chat_id");
            exit;
        } elseif ($current_step == 'phone' && !empty($text)) {
            update_user_registration($chat_id, ['phone' => $text, 'step' => 'bank_account']);
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => 'لطفاً شماره حساب خود را وارد کنید.',
                'reply_markup' => json_encode($remove_keyboard)
            ]);
            error_log("Updated step to 'bank_account' for chat_id: $chat_id");
            exit;
        } elseif ($current_step == 'bank_account' && !empty($text)) {
            update_user_registration($chat_id, ['bank_account' => $text, 'step' => 'address']);
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => 'لطفاً آدرس خود را وارد کنید.',
                'reply_markup' => json_encode($remove_keyboard)
            ]);
            error_log("Updated step to 'address' for chat_id: $chat_id");
            exit;
        } elseif ($current_step == 'address' && !empty($text)) {
            update_user_registration($chat_id, ['address' => $text, 'step' => 'confirm']);

            $inline_keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'تأیید و ثبت‌نام', 'callback_data' => 'confirm_registration']
                    ]
                ]
            ];
            Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'لطفاً برای تأیید و ثبت‌نام نهایی، دکمه زیر را فشار دهید:',
                'reply_markup' => json_encode($inline_keyboard)
            ]);
            error_log("Updated step to 'confirm' for chat_id: $chat_id");
            exit;
        } elseif ($current_step == 'confirm' && $text == '/confirm_registration') {
            error_log("Finalizing registration for chat_id: $chat_id");
            $user_data = get_user_registration($chat_id);

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

                        $response = Request::sendMessage([
                            'chat_id' => $chat_id,
                            'text' => 'ثبت‌نام شما در سایت با موفقیت انجام شد و شما به عنوان مشتری ثبت شدید!'
                        ]);
                        if (!$response->isOk()) {
                            error_log("Failed to send success message: " . $response->getDescription());
                        } else {
                            error_log("Sent success message to chat_id: $chat_id");
                        }

                        update_user_registration($chat_id, [
                            'is_registered' => 1,
                            'step' => '',
                            'current_action' => '',
                            'calc_step' => '',
                            'calc_price' => '',
                            'calc_weight' => '',
                        ]);

                        display_registered_menu($chat_id);
                        exit;
                    } else {
                        error_log("Error creating user: " . $user_id->get_error_message());
                        $response = Request::sendMessage([
                            'chat_id' => $chat_id,
                            'text' => 'خطا در ثبت نام. لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.'
                        ]);
                        if (!$response->isOk()) {
                            error_log("Failed to send error message: " . $response->getDescription());
                        }
                        exit;
                    }
                } else {
                    $response = Request::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => 'این کاربر قبلاً در سیستم ثبت شده است.'
                    ]);
                    if (!$response->isOk()) {
                        error_log("Failed to send already exists message: " . $response->getDescription());
                    }

                    update_user_registration($chat_id, [
                        'is_registered' => 1,
                        'step' => '',
                        'current_action' => '',
                        'calc_step' => '',
                        'calc_price' => '',
                        'calc_weight' => '',
                    ]);

                    display_registered_menu($chat_id);
                    exit;
                }
            } else {
                $response = Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'اطلاعات ثبت نام شما ناقص است. لطفاً دوباره تلاش کنید.'
                ]);
                if (!$response->isOk()) {
                    error_log("Failed to send incomplete registration message: " . $response->getDescription());
                }
                exit;
            }
        } else {
            $response = Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => 'لطفاً ورودی معتبری وارد کنید.',
                'reply_markup' => json_encode($remove_keyboard)
            ]);
            if (!$response->isOk()) {
                error_log("Failed to send invalid input message: " . $response->getDescription());
            }
            exit;
        }
    } else {
        error_log("User is not in registration process.");
        if (mb_strpos($text, 'ثبت نام') !== false) {
            update_user_registration($chat_id, ['step' => 'name']);
            error_log("Starting registration for chat_id: $chat_id");

            $remove_keyboard = [
                'remove_keyboard' => true
            ];

            $response = Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => 'لطفاً نام خود را وارد کنید.',
                'reply_markup' => json_encode($remove_keyboard)
            ]);
            if (!$response->isOk()) {
                error_log("Failed to send start registration message: " . $response->getDescription());
            }
            exit;
        } elseif (in_array($text, [
            'درصد اجرت طلای ساخته شده',
            'اجرت عددی طلای ساخته شده',
            'اختلاف عددی سکه تمام',
            'حباب سکه امام'
        ])) {
            handle_user_choice($chat_id, $text);
            exit;
        } else {
            display_main_menu($chat_id);
            exit;
        }
    }
}

function handle_user_choice($chat_id, $text) {
    error_log("handle_user_choice called for chat_id: $chat_id with text: $text");
    if (mb_strpos($text, 'درصد اجرت طلای ساخته شده') !== false) {
        update_user_registration($chat_id, [
            'current_action' => 'calculate_percentage_wage',
            'calc_step' => 'ask_price',
            'calc_price' => '',
            'calc_weight' => '',
        ]);
        $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً قیمت طلا را وارد کنید.']);
        if (!$response->isOk()) {
            error_log("Failed to send ask_price message: " . $response->getDescription());
        }
        exit;
    } elseif (mb_strpos($text, 'اجرت عددی طلای ساخته شده') !== false) {
        update_user_registration($chat_id, [
            'current_action' => 'calculate_numeric_wage',
            'calc_step' => 'ask_price',
            'calc_price' => '',
            'calc_weight' => '',
        ]);
        $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً قیمت طلا را وارد کنید.']);
        if (!$response->isOk()) {
            error_log("Failed to send ask_price message: " . $response->getDescription());
        }
        exit;
    } elseif (mb_strpos($text, 'اختلاف عددی سکه تمام') !== false) {
        update_user_registration($chat_id, [
            'current_action' => 'calculate_coin_difference',
            'calc_step' => 'ask_coin_price',
        ]);
        $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً قیمت سکه تمام را وارد کنید.']);
        if (!$response->isOk()) {
            error_log("Failed to send ask_coin_price message: " . $response->getDescription());
        }
        exit;
    } elseif (mb_strpos($text, 'حباب سکه امام') !== false) {
        update_user_registration($chat_id, [
            'current_action' => 'calculate_coin_bubble',
            'calc_step' => 'ask_coin_price',
        ]);
        $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً قیمت سکه امام را وارد کنید.']);
        if (!$response->isOk()) {
            error_log("Failed to send ask_coin_price message: " . $response->getDescription());
        }
        exit;
    } else {
        $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً از منوی اصلی یک گزینه را انتخاب کنید.']);
        if (!$response->isOk()) {
            error_log("Failed to send invalid choice message: " . $response->getDescription());
        }
        display_main_menu($chat_id);
        exit;
    }
}

function display_main_menu($chat_id) {
    error_log("display_main_menu called for chat_id: $chat_id");
    $keyboard = [
        'keyboard' => [
            [['text' => 'درصد اجرت طلای ساخته شده']],
            [['text' => 'اجرت عددی طلای ساخته شده']],
            [['text' => 'اختلاف عددی سکه تمام'],['text' => 'حباب سکه امام']],
            [['text' => 'ثبت نام']],
        ],
        'resize_keyboard' => true,
        'one_time_keyboard' => false,
    ];
    $response = Request::sendMessage([
        'chat_id' => $chat_id,
        'text'    => 'لطفاً یکی از گزینه‌های زیر را انتخاب کنید:',
        'reply_markup' => json_encode($keyboard)
    ]);
    if (!$response->isOk()) {
        error_log("Failed to send main menu: " . $response->getDescription());
    }
}

function display_registered_menu($chat_id) {
    error_log("display_registered_menu called for chat_id: $chat_id");
    $keyboard = [
        'keyboard' => [
            [['text' => 'درصد اجرت طلای ساخته شده']],
            [['text' => 'اجرت عددی طلای ساخته شده']],
            [['text' => 'اختلاف عددی سکه تمام'],['text' => 'حباب سکه امام']],
        ],
        'resize_keyboard' => true,
        'one_time_keyboard' => false,
    ];
    $response = Request::sendMessage([
        'chat_id' => $chat_id,
        'text'    => 'لطفاً یکی از گزینه‌های زیر را انتخاب کنید:',
        'reply_markup' => json_encode($keyboard)
    ]);
    if (!$response->isOk()) {
        error_log("Failed to send main menu: " . $response->getDescription());
    }
}

function handle_percentage_wage($chat_id, $text) {
    error_log("handle_percentage_wage called for chat_id: $chat_id with text: $text");
    $user_data = get_user_registration($chat_id);

    if ($text == '/start') {
        update_user_registration($chat_id, [
            'current_action' => '',
            'calc_step' => '',
            'calc_price' => '',
            'calc_weight' => '',
        ]);
        display_main_menu($chat_id);
        exit;
    }

    if ($user_data['calc_step'] == 'ask_price') {
        if (is_numeric($text)) {
            update_user_registration($chat_id, ['calc_price' => $text, 'calc_step' => 'ask_weight']);
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً وزن طلا را وارد کنید.']);
            if (!$response->isOk()) {
                error_log("Failed to send ask_weight message: " . $response->getDescription());
            }
            exit;
        } else {
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً یک عدد معتبر برای قیمت طلا وارد کنید.']);
            if (!$response->isOk()) {
                error_log("Failed to send invalid price message: " . $response->getDescription());
            }
            exit;
        }
    } elseif ($user_data['calc_step'] == 'ask_weight') {
        if (is_numeric($text)) {
            $user_data = get_user_registration($chat_id);
            $calc_price = $user_data['calc_price'];
            $calc_weight = $text;
            $dailyGoldPrcApi = get_daily_gold_price();
            if ($dailyGoldPrcApi == 0) {
                $percentage_wage = 0;
                error_log("Daily gold price is zero, cannot calculate percentage wage.");
            } else {
                $percentage_wage = ($calc_price / ($calc_weight * $dailyGoldPrcApi) - 1) * 100;
                $percentage_wage = round($percentage_wage, 2);
            }
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => "درصد اجرت طلای شما: $percentage_wage% می‌باشد."]);
            if (!$response->isOk()) {
                error_log("Failed to send percentage wage message: " . $response->getDescription());
            }

            update_user_registration($chat_id, [
                'current_action' => '',
                'calc_step' => '',
                'calc_price' => '',
                'calc_weight' => '',
            ]);
            exit;
        } else {
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً یک عدد معتبر برای وزن طلا وارد کنید.']);
            if (!$response->isOk()) {
                error_log("Failed to send invalid weight message: " . $response->getDescription());
            }
            exit;
        }
    } else {
        $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً از منوی اصلی یک گزینه را انتخاب کنید.']);
        if (!$response->isOk()) {
            error_log("Failed to send invalid calc_step message: " . $response->getDescription());
        }
        update_user_registration($chat_id, [
            'current_action' => '',
            'calc_step' => '',
            'calc_price' => '',
            'calc_weight' => '',
        ]);
        display_main_menu($chat_id);
        exit;
    }
}

function handle_numeric_wage($chat_id, $text) {
    error_log("handle_numeric_wage called for chat_id: $chat_id with text: $text");
    $user_data = get_user_registration($chat_id);

    if ($text == '/start') {
        update_user_registration($chat_id, [
            'current_action' => '',
            'calc_step' => '',
            'calc_price' => '',
            'calc_weight' => '',
        ]);
        display_main_menu($chat_id);
        exit;
    }

    if ($user_data['calc_step'] == 'ask_price') {
        if (is_numeric($text)) {
            update_user_registration($chat_id, ['calc_price' => $text, 'calc_step' => 'ask_weight']);
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً وزن طلا را وارد کنید.']);
            if (!$response->isOk()) {
                error_log("Failed to send ask_weight message: " . $response->getDescription());
            }
            exit;
        } else {
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً یک عدد معتبر برای قیمت طلا وارد کنید.']);
            if (!$response->isOk()) {
                error_log("Failed to send invalid price message: " . $response->getDescription());
            }
            exit;
        }
    } elseif ($user_data['calc_step'] == 'ask_weight') {
        if (is_numeric($text)) {
            $user_data = get_user_registration($chat_id);
            $calc_price = $user_data['calc_price'];
            $calc_weight = $text;
            $dailyGoldPrcApi = get_daily_gold_price();
            $numeric_wage = $calc_price - ($calc_weight * $dailyGoldPrcApi);
            $numeric_wage = round($numeric_wage, 2);
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => "اجرت عددی طلای شما: $numeric_wage ریال می‌باشد."]);
            if (!$response->isOk()) {
                error_log("Failed to send numeric wage message: " . $response->getDescription());
            }

            update_user_registration($chat_id, [
                'current_action' => '',
                'calc_step' => '',
                'calc_price' => '',
                'calc_weight' => '',
            ]);
            exit;
        } else {
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً یک عدد معتبر برای وزن طلا وارد کنید.']);
            if (!$response->isOk()) {
                error_log("Failed to send invalid weight message: " . $response->getDescription());
            }
            exit;
        }
    } else {
        $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً از منوی اصلی یک گزینه را انتخاب کنید.']);
        if (!$response->isOk()) {
            error_log("Failed to send invalid calc_step message: " . $response->getDescription());
        }
        update_user_registration($chat_id, [
            'current_action' => '',
            'calc_step' => '',
            'calc_price' => '',
            'calc_weight' => '',
        ]);
        display_main_menu($chat_id);
        exit;
    }
}

function handle_coin_difference($chat_id, $text) {
    error_log("handle_coin_difference called for chat_id: $chat_id with text: $text");
    $user_data = get_user_registration($chat_id);

    if ($text == '/start') {
        update_user_registration($chat_id, [
            'current_action' => '',
            'calc_step' => '',
        ]);
        display_main_menu($chat_id);
        exit;
    }

    if ($user_data['calc_step'] == 'ask_coin_price') {
        if (is_numeric($text)) {
			
            $coin_price = $text;
			
            $dailyGoldPrcApi = get_daily_gold_price();
			//error_log("dailyGoldPrcApi:". COIN_GOLD_WEIGH);
            $difference = $coin_price - (COIN_GOLD_WEIGH * $dailyGoldPrcApi);
			error_log("difference: $difference");
            $difference = round($difference, 2);
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => "اختلاف عددی سکه تمام: $difference ریال می‌باشد."]);
            if (!$response->isOk()) {
                error_log("Failed to send coin difference message: " . $response->getDescription());
            }

            update_user_registration($chat_id, [
                'current_action' => '',
                'calc_step' => '',
            ]);
            exit;
        } else {
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً یک عدد معتبر برای قیمت سکه وارد کنید.']);
            if (!$response->isOk()) {
                error_log("Failed to send invalid coin price message: " . $response->getDescription());
            }
            exit;
        }
    } else {
        $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً از منوی اصلی یک گزینه را انتخاب کنید.']);
        if (!$response->isOk()) {
            error_log("Failed to send invalid calc_step message: " . $response->getDescription());
        }
        update_user_registration($chat_id, [
            'current_action' => '',
            'calc_step' => '',
        ]);
        display_main_menu($chat_id);
        exit;
    }
}

function handle_coin_bubble($chat_id, $text) {
    error_log("handle_coin_bubble called for chat_id: $chat_id with text: $text");
    $user_data = get_user_registration($chat_id);

    if ($text == '/start') {
        update_user_registration($chat_id, [
            'current_action' => '',
            'calc_step' => '',
        ]);
        display_main_menu($chat_id);
        exit;
    }

    if ($user_data['calc_step'] == 'ask_coin_price') {
        if (is_numeric($text)) {
            $coin_price = $text;
            $dailyGoldPrcApi = get_daily_gold_price();
            $bubble = $coin_price - (COIN_GOLD_WEIGH * $dailyGoldPrcApi);
            $bubble = round($bubble, 2);
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => "حباب سکه امام: $bubble ریال می‌باشد."]);
            if (!$response->isOk()) {
                error_log("Failed to send coin bubble message: " . $response->getDescription());
            }

            update_user_registration($chat_id, [
                'current_action' => '',
                'calc_step' => '',
            ]);
            exit;
        } else {
            $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً یک عدد معتبر برای قیمت سکه وارد کنید.']);
            if (!$response->isOk()) {
                error_log("Failed to send invalid coin price message: " . $response->getDescription());
            }
            exit;
        }
    } else {
        $response = Request::sendMessage(['chat_id' => $chat_id, 'text' => 'لطفاً از منوی اصلی یک گزینه را انتخاب کنید.']);
        if (!$response->isOk()) {
            error_log("Failed to send invalid calc_step message: " . $response->getDescription());
        }
        update_user_registration($chat_id, [
            'current_action' => '',
            'calc_step' => '',
        ]);
        display_main_menu($chat_id);
        exit;
    }
}

function showprice($chat_id) {
    error_log("showprice called for chat_id: $chat_id");
    $profile_number = get_daily_gold_price();

    $response = Request::sendMessage([
        'chat_id' => $chat_id,
        'text'    => "قیمت لحظه ای طلای 18 عیار: $profile_number ریال"
    ]);
    if (!$response->isOk()) {
        error_log("Failed to send gold price message: " . $response->getDescription());
    }
}

function get_daily_gold_price() {
    return 4500000; }

