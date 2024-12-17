<?php
function gregorian_to_jalali($g_y, $g_m, $g_d)
{
    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;

    $g_day_no = 365 * $gy + (int) (($gy + 3) / 4) - (int) (($gy + 99) / 100) + (int) (($gy + 399) / 400);

    for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += $g_days_in_month[$i];
    }

    if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))) {
        $g_day_no++;
    }

    $g_day_no += $gd;

    $j_day_no = $g_day_no - 79;

    $j_np = (int) ($j_day_no / 12053);
    $j_day_no %= 12053;

    $jy = 979 + 33 * $j_np + 4 * (int) ($j_day_no / 1461);
    $j_day_no %= 1461;

    if ($j_day_no >= 366) {
        $jy += (int) (($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }

    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
        $j_day_no -= $j_days_in_month[$i];
    }

    $jm = $i + 1;
    $jd = $j_day_no + 1;

    return [$jy, $jm, $jd];
}

function getApiData()
{
    $cacheFile = __DIR__ . '/cache.json';
    $cacheTime = 60;
    $currentTime = time();


    if (!file_exists($cacheFile)) {
        return fetchApiData($cacheFile, $currentTime);
    }


    $cache = json_decode(file_get_contents($cacheFile), true);


    if (json_last_error() !== JSON_ERROR_NONE || !isset($cache['timestamp']) || !isset($cache['data'])) {
        return fetchApiData($cacheFile, $currentTime);
    }


    if ($currentTime - $cache['timestamp'] < $cacheTime) {
        return $cache['data'];
    }


    return fetchApiData($cacheFile, $currentTime);
}

function fetchApiData($cacheFile, $currentTime)
{
    $gpurl = 'https://webservice.tgnsrv.ir/Pr/Get/';


    $optionsgp = [
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
    ];

    $gpcontext = stream_context_create($optionsgp);


    $gpresponse = file_get_contents($gpurl, false, $gpcontext);

    if ($gpresponse === FALSE) {
        die('خطا در دریافت داده‌ها');
    }


    $gpdata = json_decode($gpresponse, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die('خطا در تجزیه JSON');
    }
    file_put_contents($cacheFile, json_encode([
        'timestamp' => $currentTime,
        'data' => $gpdata
    ]));

    return $gpdata;
}




define('GOLD_CARAT', 750);
define('COIN_CARAT', 900);
define('COIN_WEIGH', 8.133);
define('COIN_GOLD_WEIGH', 9.7596);
define('HALF_COIN_WEIGH', 4.060);
define('HALF_COIN_GOLD_WEIGH', 4.872);
define('QUARTER_COIN_WEIGH', 2.030);
define('QUARTER_COIN_GOLD_WEIGH', 2.436);
define('MAZANE_TO_18CARAT', 4.3318);
define('OUNCE_TO_MAZANE', 9.57);


function convert_mazane_to_18carat($mazaneApi)
{
    return $mazaneApi / MAZANE_TO_18CARAT;
}


function convert_ounce_to_mazane($ounceDlrPrcApi, $dlrPrcApi)
{
    return $ounceDlrPrcApi * $dlrPrcApi / OUNCE_TO_MAZANE;
}


function calculate_gold_wage_percentage($prdPrc, $prdWeigh, $dailyGoldPrcApi)
{
    return (($prdPrc / ($prdWeigh * $dailyGoldPrcApi)) - 1) * 100;
}


function calculate_gold_wage_amount($prdPrc, $prdWeigh, $dailyGoldPrcApi)
{
    return $prdPrc - ($prdWeigh * $dailyGoldPrcApi);
}


function calculate_coin_bubble($coinEm86PrcApi, $dailyGoldPrcApi)
{
    return $coinEm86PrcApi - (COIN_GOLD_WEIGH * $dailyGoldPrcApi);
}


function calculate_coin_imam_bubble($coinEm86PrcApi, $dailyGoldPrcApi)
{
    return $coinEm86PrcApi / $dailyGoldPrcApi;
}


function calculate_half_coin_bubble($half86EmCoinPrcApi, $dailyGoldPrcApi)
{
    return $half86EmCoinPrcApi - (HALF_COIN_GOLD_WEIGH * $dailyGoldPrcApi);
}


function calculate_quarter_coin_bubble($quarterEm86CoinPrcApi, $dailyGoldPrcApi)
{
    return $quarterEm86CoinPrcApi - (QUARTER_COIN_GOLD_WEIGH * $dailyGoldPrcApi);
}


function calculate_bubble_percentage($bubbleAmount, $coinPrice)
{
    return ($bubbleAmount / $coinPrice) * 100;
}



function get_live_prices()
{



    $gpdata = getApiData();

    $gptimeRead = $gpdata['TimeRead'];
    list($gpdate, $gptime) = explode(' ', $gptimeRead);


    function convert_gregorian_to_jalali($gptimeRead)
    {
        list($gpdate, $gptime) = explode(' ', $gptimeRead);
        list($gy, $gm, $gd) = explode('/', $gpdate);
        list($jy, $jm, $jd) = gregorian_to_jalali($gy, $gm, $gd);

        return sprintf('%04d/%02d/%02d %s', $jy, $jm, $jd, $gptime);
    }
    $jalali_date_time = convert_gregorian_to_jalali($gptimeRead);
    $lang = require __DIR__ . '/lang.php';
    global $gpmessage;
    global $gpayar;
    global $gpEmam;
    $gpayar = str_replace(',', '', $gpdata['YekGram18']);
    $gpEmam = str_replace(',', '', $gpdata['SekehEmam']);


    $gpmessage = "

- 🔸 هر گرم 18 عیار: " . number_format($gpdata['YekGram18']) . " تومان
- 🔸 خرید طلا 18 عیار: " . number_format($gpdata['KharidMotefaregheh18']) . " تومان
- 🔸 تعویض طلا 18 عیار: " . number_format($gpdata['TavizMotefaregheh18']) . " تومان
- 🔸 هر گرم 20 عیار: " . number_format($gpdata['YekGram20']) . " تومان
- 🔸 هر گرم 21 عیار: " . number_format($gpdata['YekGram21']) . " تومان

- 🥇 سکه تمام: " . number_format($gpdata['SekehTamam'] * 1000) . " تومان
- 🥇 سکه امامی: " . number_format($gpdata['SekehEmam'] * 1000) . " تومان
- 🥇 سکه نیم: " . number_format($gpdata['SekehNim'] * 1000) . " تومان
- 🥇 سکه ربع: " . number_format($gpdata['SekehRob'] * 1000) . " تومان
- 🥇 سکه گرمی: " . number_format($gpdata['SekehGerami'] * 1000) . " تومان

- 🇺🇸 دلار: " . number_format($gpdata['Dollar']) . " تومان
- 💶 یورو: " . number_format($gpdata['Euro']) . " تومان
- 🇦🇪 درهم: " . number_format($gpdata['Derham']) . " تومان

- 🇺🇸 اونس طلا: " . number_format($gpdata['OunceTala']) . " دلار
- 💰 مظنه: " . number_format(convert_ounce_to_mazane($gpdata['OunceTala'], $gpdata['Dollar'])) . " تومان

- 🔸 هر گرم 18 عیار: " . number_format($gpdata['YekGram18']) . " تومان
- 🥇 سکه تمام: " . number_format($gpdata['SekehTamam'] * 1000) . " تومان
- 🇺🇸 دلار: " . number_format($gpdata['Dollar']) . " تومان

" . $lang['bot_username'] . "
";
    $gpmessage .= sprintf($lang['extra-pm'], $jalali_date_time);
    return $gpmessage;
}

function get_gold_price()
{
    $gpdata = getApiData();


    return number_format($gpdata['YekGram18']);
}

function get_coin_data()
{
    $gpdata = getApiData();
    $coinEm86PrcApi = $gpdata['SekehEmam'] * 1000;
    $coinTamPrcApi = $gpdata['SekehTamam'] * 1000;
    $coinNimPrcApi = $gpdata['SekehNim'] * 1000;
    $coinRobPrcApi = $gpdata['SekehRob'] * 1000;
    $dailyGoldPrcApi = $gpdata['YekGram18'];
    $gpttimeRead = $gpdata['TimeRead'];
    list($gpdate, $gptime) = explode(' ', $gpttimeRead);
    function convert_gregorian_to_jalali($gpttimeRead)
    {
        list($gpdate, $gptime) = explode(' ', $gpttimeRead);
        list($gy, $gm, $gd) = explode('/', $gpdate);
        list($jy, $jm, $jd) = gregorian_to_jalali($gy, $gm, $gd);

        return sprintf('%04d/%02d/%02d %s', $jy, $jm, $jd, $gptime);
    }
    $jalali_date_time = convert_gregorian_to_jalali($gpttimeRead);

    $tamamBubble = calculate_coin_bubble($coinTamPrcApi, $dailyGoldPrcApi);
    $tamamBubblePercentage = calculate_bubble_percentage($tamamBubble, $coinTamPrcApi);
    $imamBubble = calculate_coin_imam_bubble($coinEm86PrcApi, $dailyGoldPrcApi);
    $imamBubblePercentage = calculate_bubble_percentage($imamBubble, $coinEm86PrcApi);
    $nimBubble = calculate_half_coin_bubble($coinNimPrcApi, $dailyGoldPrcApi);
    $nimBubblePercentage = calculate_bubble_percentage($nimBubble, $coinNimPrcApi);
    $robBubble = calculate_quarter_coin_bubble($coinRobPrcApi, $dailyGoldPrcApi);
    $robBubblePercentage = calculate_bubble_percentage($robBubble, $coinRobPrcApi);

    return [
        'priceimam' => $gpdata['SekehEmam'] * 1000,
        'pricetamam' => $gpdata['SekehTamam'] * 1000,
        'pricenim' => $gpdata['SekehNim'] * 1000,
        'pricerob' => $gpdata['SekehRob'] * 1000,
        'yekprice' => $dailyGoldPrcApi,
        'bubbleimam' => $imamBubble,
        'bubble_percentage_imam' => round($imamBubblePercentage, 2),
        'bubbletamam' => $tamamBubble,
        'bubble_percentagetamam' => round($tamamBubblePercentage, 2),
        'bubblenim' => $nimBubble,
        'bubble_percentagenim' => round($nimBubblePercentage, 2),
        'bubblerob' => $robBubble,
        'bubble_percentagerob' => round($robBubblePercentage, 2),
        'time' => $jalali_date_time,
    ];
}

function calculate_wage_percentage($prdPrc, $prdWeigh, $dailyGoldPrcApi)
{
    if ($prdPrc <= 0 || $prdWeigh <= 0 || $dailyGoldPrcApi <= 0) {
        throw new InvalidArgumentException('مقادیر ورودی باید معتبر و بزرگتر از صفر باشند.');
    }

    $wage_percentage = (($prdPrc / ($prdWeigh * $dailyGoldPrcApi)) - 1) * 100;
    return round($wage_percentage, 2);
}

function calculate_wage_amount($prdPrc, $prdWeigh, $dailyGoldPrcApi)
{
    if ($prdPrc <= 0 || $prdWeigh <= 0 || $dailyGoldPrcApi <= 0) {
        throw new InvalidArgumentException('مقادیر ورودی باید معتبر و بزرگتر از صفر باشند.');
    }

    $wage_amount = $prdPrc - ($prdWeigh * $dailyGoldPrcApi);
    return $wage_amount;
}


