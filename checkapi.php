<?php
function verify_national_code($national_code, $mobile) {
    $tokenFile = __DIR__ . '/token.json';

    if (!file_exists($tokenFile)) {
        error_log('فایل توکن پیدا نشد.');
        return false;
    }

    $tokenData = json_decode(file_get_contents($tokenFile), true);
    if (!isset($tokenData['access_token'])) {
        error_log('توکن دسترسی نامعتبر است.');
        return false;
    }

    $api_key = $tokenData['access_token'];
    $api_url = 'https://api.vandar.io/v3/business/::business/customers/authentication/shahkar';

    $customer_data = [
        'mobile'                   => $mobile,
        'individual_national_code' => $national_code,
    ];

    $json_data = json_encode($customer_data);

    $headers = [
        'Accept: application/json',
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data),
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if ($response === false) {
        error_log('خطا در ارسال درخواست: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response_data = json_decode($response, true);

    if ($http_code === 200 && isset($response_data['authentication_status']) && $response_data['authentication_status'] === 'SUCCESSFUL') {
        return true;
    }

    error_log('پاسخ نامعتبر از API: ' . json_encode($response_data));
    return false;
}
