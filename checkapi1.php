<?php
function verify_shaba_number_with_birthdate($shaba_number, $birth_date, $national_code) {
    $tokenFile = __DIR__ . '/token.json';
    $tokenData = json_decode(file_get_contents($tokenFile), true);
    $api_key = $tokenData['access_token'];
    $api_url = 'https://api.vandar.io/v3/business/::business/customers/authentication/iban';

    $customer_data = [
        'iban'                      => $shaba_number,
        'national_code'  			=> $national_code,
        'birth_date'                => $birth_date,
    ];

    $headers = [
        'Accept: application/json',
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json',
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($customer_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return ['success' => false, 'error' => curl_error($ch)];
    }
file_put_contents(__DIR__ . '/logs.log', print_r([
    'shaba_number' => $shaba_number,
    'birth_date' => $birth_date,
    'national_code' => $national_code,
    'response' => $response
], true), FILE_APPEND);

    curl_close($ch);

    return json_decode($response, true);
}
