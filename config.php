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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // временно — в продакшене верни true
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Swap/1.0 (compatible; +https://cr873507.tw1.ru)');
    
    $json = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Если API не ответил — возвращаем запасные значения
    if ($curl_error || $json === false || $http_code !== 200) {
        error_log("cURL CoinGecko error: $curl_error | HTTP $http_code");
        return [
            'tether'  => ['rub' => 90.00, 'usd' => 1.00, 'eur' => 0.92],
            'bitcoin' => ['usd' => 80000.00, 'eur' => 74000.00],
        ];
    }
    
    $data = json_decode($json, true) ?? [];
    
    // Безопасное извлечение с дефолтами
    $tether  = $data['tether']  ?? ['rub' => 90.00, 'usd' => 1.00, 'eur' => 0.92];
    $bitcoin = $data['bitcoin'] ?? ['usd' => 80000.00, 'eur' => 74000.00];
    
    // Дефолтные значения для usd и eur
    $usd_rub = $tether['rub'] ?? 90.00;
    $eur_rub = ($tether['eur'] ?? 0.92) > 0 ? $usd_rub / ($tether['eur'] ?? 0.92) : 97.83;
    
    $result = [
        'tether'  => $tether,
        'bitcoin' => $bitcoin,
        'usd'     => ['rub' => $usd_rub],
        'eur'     => ['rub' => $eur_rub],
    ];
    
    return $result;
}

// Получаем курсы
$real_rates = getRealRates();

// Безопасное получение значений (никогда не будет null/0)
$market_usdt_rub = $real_rates['tether']['rub']     ?? 90.00;
$market_usdt_usd = $real_rates['tether']['usd']     ?? 1.00;
$market_usdt_eur = $real_rates['tether']['eur']     ?? 0.92;
$market_btc_usd  = $real_rates['bitcoin']['usd']    ?? 80000.00;
$market_btc_eur  = $real_rates['bitcoin']['eur']    ?? 74000.00;
$market_usd_rub  = $real_rates['usd']['rub']        ?? 90.00;
$market_eur_rub  = $real_rates['eur']['rub']        ?? 97.83;

$market_usd_eur  = $market_usdt_eur;
$market_eur_usd  = $market_usdt_eur > 0 ? 1 / $market_usdt_eur : 1.087;

// Наценка
$markup_sell = 1.025;
$markup_buy  = 0.975;

// Курсы — теперь все деления защищены
$rates = [
    'USDT_TRC20' => [
        'RUB' => round($market_usdt_rub * $markup_sell, 2),
        'USD' => round($market_usdt_usd * $markup_sell, 4),
        'EUR' => round($market_usdt_eur * $markup_sell, 4),
        'BTC' => $market_btc_usd > 0 ? round(1 / $market_btc_usd * $markup_buy, 8) : 0.0000125,
    ],
    'RUB' => [
        'USDT_TRC20' => $market_usdt_rub > 0 ? round(1 / ($market_usdt_rub * $markup_buy), 6) : 0.01111,
        'USD'        => $market_usd_rub > 0 ? round(1 / ($market_usd_rub * $markup_buy), 6) : 0.01111,
        'EUR'        => $market_eur_rub > 0 ? round(1 / ($market_eur_rub * $markup_buy), 6) : 0.01025,
        'BTC'        => ($market_btc_usd && $market_usd_rub) ? round(1 / ($market_btc_usd * $market_usd_rub * $markup_buy), 8) : 0.000000125,
    ],
    'BTC' => [
        'USDT_TRC20' => round($market_btc_usd * $markup_sell, 0),
        'USD'        => round($market_btc_usd * $markup_sell, 0),
        'RUB'        => round($market_btc_usd * $market_usd_rub * $markup_sell, 0),
        'EUR'        => round($market_btc_eur * $markup_sell, 0),
    ],
    'USD' => [
        'USDT_TRC20' => $market_usdt_usd > 0 ? round(1 / ($market_usdt_usd * $markup_buy), 4) : 0.99,
        'RUB'        => round($market_usd_rub * $markup_sell, 2),
        'EUR'        => $market_usd_eur > 0 ? round($market_usd_eur * $markup_sell, 4) : 0.92,
        'BTC'        => $market_btc_usd > 0 ? round(1 / ($market_btc_usd * $markup_buy), 8) : 0.0000125,
    ],
    'EUR' => [
        'USDT_TRC20' => $market_usdt_eur > 0 ? round(1 / ($market_usdt_eur * $markup_buy), 4) : 1.087,
        'RUB'        => round($market_eur_rub * $markup_sell, 2),
        'USD'        => $market_eur_usd > 0 ? round($market_eur_usd * $markup_sell, 4) : 1.087,
        'BTC'        => ($market_btc_usd && $market_usd_eur) ? round(1 / ($market_btc_usd * $market_usd_eur * $markup_buy), 8) : 0.0000135,
    ],
];

// Лимиты
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