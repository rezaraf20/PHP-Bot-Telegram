<?php
function refreshTokenIfNeeded() {
    $tokenFile = __DIR__ . '/token.json';
    $apiUrl = 'https: api.sample.ir/';
    $checkTimeBeforeExpiration = 3600;  

    if (!file_exists($tokenFile)) {
        error_log("فایل توکن وجود ندارد. لطفاً ابتدا توکن اولیه را ذخیره کنید.");
        return;
    }

    $tokenData = json_decode(file_get_contents($tokenFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("فایل توکن خراب است.");
        return;
    }

    $currentTime = time();

     
    if (!isset($tokenData['expires_at'])) {
        $tokenData['expires_at'] = $currentTime + $tokenData['expires_in'];
    }

     
    if ($currentTime >= ($tokenData['expires_at'] - $checkTimeBeforeExpiration)) {
	 
        echo "در حال تمدید توکن...\n";

         
        $refreshToken = $tokenData['refresh_token'];
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['refreshtoken' => $refreshToken]));

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log("خطای CURL: " . curl_error($ch));
            curl_close($ch);
            return;
        }
        curl_close($ch);

        $newTokenData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("خطا در تجزیه JSON پاسخ API: " . json_last_error_msg());
            return;
        }

        if (!isset($newTokenData['access_token']) || !isset($newTokenData['expires_in'])) {
            error_log("پاسخ نامعتبر از API: " . $response);
            return;
        }

         
        $newTokenData['expires_at'] = $currentTime + $newTokenData['expires_in'];
        file_put_contents($tokenFile, json_encode($newTokenData, JSON_PRETTY_PRINT));

        echo "توکن جدید دریافت و ذخیره شد.\n";
    } else {
        echo "توکن هنوز معتبر است. نیازی به تمدید نیست.\n";
    }
}
