<?php

define('SITE_NAME', 'Swap');
define('SITE_URL', 'https://cr873507.tw1.ru');
define('ADMIN_EMAIL', 'admin@your-domain.com');

// === Кэширование последних успешных курсов ===
define('CACHE_FILE', __DIR__ . '/cache_rates.json');
define('CACHE_TTL', 3600);

function getCachedRates() {
    if (!file_exists(CACHE_FILE)) return null;

    $content = file_get_contents(CACHE_FILE);
    $data = json_decode($content, true);

    if (!$data || !isset($data['timestamp']) || (time() - $data['timestamp']) > CACHE_TTL) {
        return null;
    }
    return $data;
}

function saveCachedRates($rates, $reserves, $limits) {
    $data = [
        'timestamp' => time(),
        'rates'     => $rates,
        'reserves'  => $reserves,
        'limits'    => $limits
    ];
    file_put_contents(CACHE_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// === Реальные курсы с CoinGecko + наценка 2.5% ===
function getRealRates() {
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=tether,bitcoin&vs_currencies=rub,usd,eur';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Swap/1.0 (compatible; +https://cr873507.tw1.ru)');
    
    $json = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($curl_error || $json === false || $http_code !== 200) {
        error_log("[" . date('Y-m-d H:i:s') . "] cURL CoinGecko error: $curl_error | HTTP $http_code");
        return null;
    }
    
    $data = json_decode($json, true) ?? [];
    
    if (empty($data['tether']) || empty($data['bitcoin'])) {
        return null;
    }

    $tether  = $data['tether'];
    $bitcoin = $data['bitcoin'];

    $usd_rub = $tether['rub'] ?? null;
    $eur_rub = ($tether['eur'] ?? null) > 0 ? $usd_rub / $tether['eur'] : null;

    if ($usd_rub === null || $eur_rub === null) {
        return null;
    }

    return [
        'tether'  => $tether,
        'bitcoin' => $bitcoin,
        'usd'     => ['rub' => $usd_rub],
        'eur'     => ['rub' => $eur_rub],
    ];
}

// Загрузка курсов (твой существующий код)
$cached = getCachedRates();
if ($cached) {
    $rates    = $cached['rates'];
    $reserves = $cached['reserves'];
    $limits   = $cached['limits'];
} else {
    $real_rates = getRealRates();

    if ($real_rates) {
        $market_usdt_rub = $real_rates['tether']['rub']   ?? 90.00;
        $market_usdt_usd = $real_rates['tether']['usd']   ?? 1.00;
        $market_btc_usd  = $real_rates['bitcoin']['usd']  ?? 80000.00;
        $market_usd_rub  = $real_rates['usd']['rub']      ?? 90.00;

        $markup_sell = 1.025;
        $markup_buy  = 0.975;

        $rates = [
            'USDT_TRC20' => [
                'RUB' => round($market_usdt_rub * $markup_sell, 2),
                'BTC' => $market_btc_usd > 0 ? round(1 / $market_btc_usd * $markup_buy, 8) : 0.0000125,
            ],
            'RUB' => [
                'USDT_TRC20' => $market_usdt_rub > 0 ? round(1 / ($market_usdt_rub * $markup_buy), 6) : 0.01111,
                'BTC'        => ($market_btc_usd && $market_usd_rub) ? round(1 / ($market_btc_usd * $market_usd_rub * $markup_buy), 8) : 0.000000125,
            ],
            'BTC' => [
                'USDT_TRC20' => round($market_btc_usd * $markup_sell, 0),
                'RUB'        => round($market_btc_usd * $market_usd_rub * $markup_sell, 0),
            ],
        ];

        // Добавляем обратные курсы
        $reverse_rates = [];
        foreach ($rates as $from => $toArray) {
            foreach ($toArray as $to => $value) {
                if ($value > 0) {
                    if (!isset($reverse_rates[$to])) $reverse_rates[$to] = [];
                    $reverse_rates[$to][$from] = 1 / $value;
                }
            }
        }
        foreach ($reverse_rates as $from => $toArray) {
            if (!isset($rates[$from])) $rates[$from] = [];
            foreach ($toArray as $to => $value) {
                if (!isset($rates[$from][$to]) || $rates[$from][$to] <= 0) {
                    $rates[$from][$to] = $value;
                }
            }
        }

        $limits = [
            'USDT_TRC20' => ['min' => 50,    'max' => 55000],
            'RUB'        => ['min' => 5000,  'max' => 2000000],
            'BTC'        => ['min' => 0.001, 'max' => 10],
        ];

        $reserves = [
            'USDT_TRC20' => 1245678.45,
            'RUB'        => 45892000,
            'BTC'        => 12.78451637,
        ];

        saveCachedRates($rates, $reserves, $limits);
    }
}

// ================================================
// НОВЫЙ КОД: Сохранение данных обмена перед редиректом на логин
// ================================================

if (!function_exists('savePendingExchange')) {
    function savePendingExchange() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            session_start(); // на всякий случай
            $_SESSION['pending_exchange'] = [
                'give'        => $_POST['give_currency'] ?? 'USDT_TRC20',
                'get'         => $_POST['get_currency']  ?? 'RUB',
                'amount_give' => floatval($_POST['amount_give'] ?? 100),
            ];
        }
    }
}

// Вызываем функцию, если пришёл POST от формы обмена
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['give_currency'])) {
    savePendingExchange();
}