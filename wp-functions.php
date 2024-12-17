<?php
function get_user_mobile($chat_id) {
     
    $user = get_users([
        'meta_key'   => 'telegram_chat_id',
        'meta_value' => $chat_id,
        'number'     => 1,
    ]);

    if (!empty($user)) {
        return get_user_meta($user[0]->ID, 'digits_phone', true);
    }

    return null;
}

function save_user_mobile($chat_id, $mobile) {
     
    $username = $mobile;  
    $email = $username . '@example.com';

     
    $user = get_user_by('login', $username);

    if (!$user) {
         
        $user_id = wp_create_user($username, wp_generate_password(), $email);
        if (is_wp_error($user_id)) {
            error_log('خطا در ایجاد کاربر: ' . $user_id->get_error_message());
            return false;
        }

         
        $user = new WP_User($user_id);
        $user->set_role('customer');
    } else {
        $user_id = $user->ID;
    }

     
    update_user_meta($user_id, 'mobile', $mobile);
    update_user_meta($user_id, 'billing_phone', $mobile);
    update_user_meta($user_id, 'digits_phone_no', $mobile);
    update_user_meta($user_id, 'digits_phone', $mobile);
    update_user_meta($user_id, 'digt_countrycode', '+98');  
    update_user_meta($user_id, 'telegram_chat_id', $chat_id);  

     
    global $wpdb;
    $table_name = $wpdb->prefix . 'digits_mobile_otp';

     
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE user_id = %d", $user_id));

    if ($exists) {
         
        $wpdb->update(
            $table_name,
            ['mobile' => $mobile],
            ['user_id' => $user_id]
        );
    } else {
         
        $wpdb->insert(
            $table_name,
            [
                'user_id'      => $user_id,
                'mobile'       => $mobile,
                'country_code' => '+98',  
            ]
        );
    }

    return true;
}

function save_user_data($chat_id, $data) {
     
    $username = isset($data['mobile']) ? $data['mobile'] : 'user_' . $chat_id;
    $email = $username . '@example.com';

     
    $user = get_user_by('login', $username);

    if (!$user) {
         
        $user_id = wp_create_user($username, wp_generate_password(), $email);
        if (is_wp_error($user_id)) {
            error_log('خطا در ایجاد کاربر: ' . $user_id->get_error_message());
            return false;
        }

         
        $user = new WP_User($user_id);
        $user->set_role('customer');
    } else {
        $user_id = $user->ID;
    }

     
    if (isset($data['mobile'])) {
        update_user_meta($user_id, 'mobile', $data['mobile']);
        update_user_meta($user_id, 'billing_phone', $data['mobile']);
        update_user_meta($user_id, 'digits_phone_no', $data['mobile']);
        update_user_meta($user_id, 'digits_phone', $data['mobile']);
        update_user_meta($user_id, 'digt_countrycode', '+98');  
    }
    if (isset($data['telegram_chat_id'])) {
        update_user_meta($user_id, 'telegram_chat_id', $chat_id);
    }
    if (isset($data['national_code'])) {
        update_user_meta($user_id, 'national_code', $data['national_code']);
    }
    if (isset($data['shaba_number'])) {
        update_user_meta($user_id, 'shaba_number', $data['shaba_number']);
    }
    if (isset($data['name'])) {
        wp_update_user(['ID' => $user_id, 'first_name' => $data['name']]);
    }
    if (isset($data['surname'])) {
        wp_update_user(['ID' => $user_id, 'last_name' => $data['surname']]);
    }
    if (isset($data['address'])) {
        update_user_meta($user_id, 'address', $data['address']);
    }
	if (isset($data['birth_date'])) {
        update_user_meta($user_id, 'birth_date', $data['birth_date']);
    }

     
    if (isset($data['mobile'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'digits_mobile_otp';

         
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE user_id = %d", $user_id));

        if ($exists) {
            $wpdb->update(
                $table_name,
                ['mobile' => $data['mobile']],
                ['user_id' => $user_id]
            );
        } else {
            $wpdb->insert(
                $table_name,
                [
                    'user_id'      => $user_id,
                    'mobile'       => $data['mobile'],
                    'country_code' => '+98',
                ]
            );
        }
    }

    return true;
}
?>
