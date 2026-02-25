<?php

define('SITE_NAME', 'BekirovSwap');
define('SITE_URL', 'https://cr873507.tw1.ru');
define('ADMIN_EMAIL', 'admin@your-domain.com');

// === Реальные курсы + наценка 2.5% ===

// Функция для USDT/RUB (оставляем как есть)
function getRealUsdtRub() {
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=rub';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // временно
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'BekirovSwap/1.0 (compatible; +https://cr873507.tw1.ru)');
    
    $json = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($curl_error || $json === false || $http_code !== 200) {
        error_log("cURL CoinGecko error: $curl_error | HTTP $http_code");
        return 76.70;  // запасной
    }
    
    $data = json_decode($json, true);
    return $data['tether']['rub'] ?? 76.70;
}

// === Реальные курсы на 25 февраля 2026 + наценка 2.5% ===

// Базовые рыночные курсы (обновляй вручную или через API)
$market_usdt_rub = 76.70;      // USDT → RUB (CoinGecko)
$market_usdt_usd = 1.00;
$market_usdt_eur = 0.848;      // USDT → EUR
$market_btc_usd  = 67200;      // BTC → USD
$market_usd_rub  = 76.65;      // USD → RUB
$market_eur_rub  = 90.26;      // EUR → RUB
$market_usd_eur  = 0.8486;     // USD → EUR (1 USD = 0.8486 EUR)
$market_eur_usd  = 1.1784;     // EUR → USD (1 EUR = 1.1784 USD)

// Наценка 2.5%
$markup_sell = 1.025;   // +2.5% когда отдаёшь крипту/стейбл/доллар → получаешь фиат
$markup_buy  = 0.975;   // -2.5% когда отдаёшь фиат → получаешь крипту/стейбл/доллар

$rates = [
    // USDT_TRC20 как отдаваемая
    'USDT_TRC20' => [
        'RUB' => round($market_usdt_rub * $markup_sell, 2),      // ~78.62
        'USD' => round($market_usdt_usd * $markup_sell, 4),      // ~1.025
        'EUR' => round($market_usdt_eur * $markup_sell, 4),      // ~0.869
        'BTC' => round(1 / $market_btc_usd * $markup_buy, 8),    // BTC за USDT
    ],

    // RUB как отдаваемая
    'RUB' => [
        'USDT_TRC20' => round(1 / ($market_usdt_rub * $markup_buy), 6),
        'USD'        => round(1 / ($market_usd_rub * $markup_buy), 6),
        'EUR'        => round(1 / ($market_eur_rub * $markup_buy), 6),
        'BTC'        => round(1 / ($market_btc_usd * $market_usd_rub * $markup_buy), 8),
    ],

    // BTC как отдаваемая
    'BTC' => [
        'USDT_TRC20' => round($market_btc_usd * $markup_sell, 0),
        'USD'        => round($market_btc_usd * $markup_sell, 0),
        'RUB'        => round($market_btc_usd * $market_usd_rub * $markup_sell, 0),
        'EUR'        => round($market_btc_usd * $market_usd_eur * $markup_sell, 0),
    ],

    // USD как отдаваемая
    'USD' => [
        'USDT_TRC20' => round(1 / ($market_usdt_usd * $markup_buy), 4),
        'RUB'        => round($market_usd_rub * $markup_sell, 2),
        'EUR'        => round($market_usd_eur * $markup_sell, 4),
        'BTC'        => round(1 / ($market_btc_usd * $markup_buy), 8),
    ],

    // EUR как отдаваемая
    'EUR' => [
        'USDT_TRC20' => round(1 / ($market_usdt_eur * $markup_buy), 4),
        'RUB'        => round($market_eur_rub * $markup_sell, 2),
        'USD'        => round($market_eur_usd * $markup_sell, 4),
        'BTC'        => round(1 / ($market_btc_usd * $market_usd_eur * $markup_buy), 8),
    ],
];

// Остальные части файла (limits, reserves) остаются как есть

// Остальные части (limits, reserves) оставь как есть

// Остальные части файла (limits, reserves) остаются как есть

$limits = [
    'USDT_TRC20' => ['min' => 50, 'max' => 50000],
    'RUB'        => ['min' => 5000, 'max' => 2000000],
];

$reserves = [
    'USDT_TRC20' => 1245678.45,
    'RUB'        => 45892000,
    'BTC'        => 12.784,
    'USD'        => 101233,
    'EUR'        => 300002,
];