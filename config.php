<?php

define('SITE_NAME', 'BekirovSwap');
define('SITE_URL', 'https://cr873507.tw1.ru');
define('ADMIN_EMAIL', 'admin@your-domain.com');

// Реальный курс USDT/RUB с CoinGecko через cURL + наценка

function getRealUsdtRub() {
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=rub';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // временно для теста
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'BekirovSwap/1.0 (compatible; +https://cr873507.tw1.ru)');
    
    $json = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($curl_error || $json === false || $http_code !== 200) {
        error_log("cURL CoinGecko error: $curl_error | HTTP $http_code");
        return 7.70;  // запасной
    }
    
    $data = json_decode($json, true);
    
    if (isset($data['tether']['rub']) && is_numeric($data['tether']['rub'])) {
        return floatval($data['tether']['rub']);
    }
    
    error_log('CoinGecko invalid response: ' . $json);
    return 7.70;
}

$market_usdt_rub = getRealUsdtRub();

$markup_sell_usdt = 1.025;   // +2.5% при продаже USDT
$markup_buy_usdt  = 0.975;   // -2.5% при покупке USDT

$rates = [
    'USDT_TRC20' => [
        'RUB' => round($market_usdt_rub * $markup_sell_usdt, 2),
        'USD' => 1.00,
        'EUR' => 0.94,
    ],
    'RUB' => [
        'USDT_TRC20' => round(1 / ($market_usdt_rub * $markup_buy_usdt), 6),
    ],
    'BTC' => [
        'USDT_TRC20' => 98000,
    ],
];

$limits = [
    'USDT_TRC20' => ['min' => 50, 'max' => 50000],
    'RUB'        => ['min' => 5000, 'max' => 2000000],
];

$reserves = [
    'USDT_TRC20' => 1245678.45,
    'RUB'        => 45892000,
    'BTC'        => 12.784,
];