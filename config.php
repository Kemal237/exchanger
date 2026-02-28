<?php

define('SITE_NAME', 'Swap');
define('SITE_URL', 'https://cr873507.tw1.ru');
define('ADMIN_EMAIL', 'admin@your-domain.com');

// === Реальные курсы с CoinGecko + наценка 2.5% ===

function getRealRates() {
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=tether,bitcoin&vs_currencies=rub,usd,eur';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // временно — верни true, если работает
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Swap/1.0 (compatible; +https://cr873507.tw1.ru)');
    
    $json = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($curl_error || $json === false || $http_code !== 200) {
        error_log("cURL CoinGecko error: $curl_error | HTTP $http_code");
        return [
            'tether'   => ['rub' => 76.70, 'usd' => 1.00, 'eur' => 0.848],
            'bitcoin'  => ['usd' => 67200, 'eur' => 57056],
        ];
    }
    
    $data = json_decode($json, true);
    
    $result = [
        'tether'  => $data['tether']  ?? ['rub' => 76.70, 'usd' => 1.00, 'eur' => 0.848],
        'bitcoin' => $data['bitcoin'] ?? ['usd' => 67200, 'eur' => 57056],
    ];
    
    $result['usd'] = ['rub' => $result['tether']['rub']];
    $result['eur'] = ['rub' => $result['usd']['rub'] / $result['tether']['eur']];
    
    return $result;
}

$real_rates = getRealRates();

$market_usdt_rub = $real_rates['tether']['rub'];
$market_usdt_usd = $real_rates['tether']['usd'];
$market_usdt_eur = $real_rates['tether']['eur'];
$market_btc_usd  = $real_rates['bitcoin']['usd'];
$market_btc_eur  = $real_rates['bitcoin']['eur'];
$market_usd_rub  = $real_rates['usd']['rub'];
$market_eur_rub  = $real_rates['eur']['rub'];
$market_usd_eur  = $market_usdt_eur;
$market_eur_usd  = 1 / $market_usdt_eur;

$markup_sell = 1.025;
$markup_buy  = 0.975;

$rates = [
    'USDT_TRC20' => [
        'RUB' => round($market_usdt_rub * $markup_sell, 2),
        'USD' => round($market_usdt_usd * $markup_sell, 4),
        'EUR' => round($market_usdt_eur * $markup_sell, 4),
        'BTC' => round(1 / $market_btc_usd * $markup_buy, 8),
    ],
    'RUB' => [
        'USDT_TRC20' => round(1 / ($market_usdt_rub * $markup_buy), 6),
        'USD'        => round(1 / ($market_usd_rub * $markup_buy), 6),
        'EUR'        => round(1 / ($market_eur_rub * $markup_buy), 6),
        'BTC'        => round(1 / ($market_btc_usd * $market_usd_rub * $markup_buy), 8),
    ],
    'BTC' => [
        'USDT_TRC20' => round($market_btc_usd * $markup_sell, 0),
        'USD'        => round($market_btc_usd * $markup_sell, 0),
        'RUB'        => round($market_btc_usd * $market_usd_rub * $markup_sell, 0),
        'EUR'        => round($market_btc_eur * $markup_sell, 0),
    ],
    'USD' => [
        'USDT_TRC20' => round(1 / ($market_usdt_usd * $markup_buy), 4),
        'RUB'        => round($market_usd_rub * $markup_sell, 2),
        'EUR'        => round($market_usd_eur * $markup_sell, 4),
        'BTC'        => round(1 / ($market_btc_usd * $markup_buy), 8),
    ],
    'EUR' => [
        'USDT_TRC20' => round(1 / ($market_usdt_eur * $markup_buy), 4),
        'RUB'        => round($market_eur_rub * $markup_sell, 2),
        'USD'        => round($market_eur_usd * $markup_sell, 4),
        'BTC'        => round(1 / ($market_btc_usd * $market_usd_eur * $markup_buy), 8),
    ],
];

// Лимиты теперь задаются здесь — для каждой валюты отдельно
$limits = [
    'USDT_TRC20' => ['min' => 50,    'max' => 55000],
    'RUB'        => ['min' => 5000,  'max' => 2000000],
    'BTC'        => ['min' => 0.001, 'max' => 10],
    'USD'        => ['min' => 50,    'max' => 60000],
    'EUR'        => ['min' => 50,    'max' => 70000],
];

// Резервы
$reserves = [
    'USDT_TRC20' => 1245678.45,
    'RUB'        => 45892000,
    'BTC'        => 12.78451637,
    'USD'        => 101233,
    'EUR'        => 300002,
];