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

// === Реальные курсы с CoinGecko (USDT, USDC, ETH, SOL, BTC vs RUB и USD) ===
function getRealRates() {
    $url = 'https://api.coingecko.com/api/v3/simple/price'
         . '?ids=tether,usd-coin,ethereum,solana,bitcoin'
         . '&vs_currencies=rub,usd';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Swap/1.0 (compatible; +https://cr873507.tw1.ru)');

    $json = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err || $json === false || $code !== 200) {
        error_log("[" . date('Y-m-d H:i:s') . "] CoinGecko error: $err | HTTP $code");
        return null;
    }

    $data = json_decode($json, true) ?? [];

    foreach (['tether', 'usd-coin', 'ethereum', 'solana', 'bitcoin'] as $id) {
        if (empty($data[$id]['rub']) || empty($data[$id]['usd'])) {
            error_log("[" . date('Y-m-d H:i:s') . "] CoinGecko missing: $id");
            return null;
        }
    }

    return $data;
}

// Загрузка курсов
$cached = getCachedRates();
if ($cached) {
    $rates    = $cached['rates'];
    $reserves = $cached['reserves'];
    $limits   = $cached['limits'];
} else {
    $real = getRealRates();

    if ($real) {
        $usdt_rub = $real['tether']['rub'];
        $usdt_usd = $real['tether']['usd'];
        $usdc_rub = $real['usd-coin']['rub'];
        $usdc_usd = $real['usd-coin']['usd'];
        $eth_rub  = $real['ethereum']['rub'];
        $eth_usd  = $real['ethereum']['usd'];
        $sol_rub  = $real['solana']['rub'];
        $sol_usd  = $real['solana']['usd'];
        $btc_rub  = $real['bitcoin']['rub'];
        $btc_usd  = $real['bitcoin']['usd'];
        $usd_rub  = $usdt_rub; // USDT ≈ USD

        $b = 0.975; // наценка покупки (пользователь отдаёт крипту — мы платим меньше)
        $s = 1.025; // наценка продажи (пользователь получает крипту — он платит больше)

        $rates = [
            'USDT_TRC20' => [
                'RUB'  => round($usdt_rub * $b, 2),
                'USD'  => round($usdt_usd * $b, 4),
                'USDC' => $usdc_usd > 0 ? round($usdt_usd / ($usdc_usd * $s), 4) : 0,
                'ETH'  => $eth_usd  > 0 ? round($usdt_usd / ($eth_usd  * $s), 6) : 0,
                'SOL'  => $sol_usd  > 0 ? round($usdt_usd / ($sol_usd  * $s), 4) : 0,
                'BTC'  => $btc_usd  > 0 ? round($usdt_usd / ($btc_usd  * $s), 8) : 0,
            ],
            'USDC' => [
                'RUB'       => round($usdc_rub * $b, 2),
                'USD'       => round($usdc_usd * $b, 4),
                'USDT_TRC20'=> $usdt_usd > 0 ? round($usdc_usd / ($usdt_usd * $s), 4) : 0,
                'ETH'       => $eth_usd  > 0 ? round($usdc_usd / ($eth_usd  * $s), 6) : 0,
                'SOL'       => $sol_usd  > 0 ? round($usdc_usd / ($sol_usd  * $s), 4) : 0,
                'BTC'       => $btc_usd  > 0 ? round($usdc_usd / ($btc_usd  * $s), 8) : 0,
            ],
            'ETH' => [
                'RUB'       => round($eth_rub * $b, 0),
                'USD'       => round($eth_usd * $b, 2),
                'USDT_TRC20'=> round($eth_usd * $b, 2),
                'USDC'      => round($eth_usd * $b, 2),
                'SOL'       => $sol_usd > 0 ? round($eth_usd / ($sol_usd * $s), 2) : 0,
                'BTC'       => $btc_usd > 0 ? round($eth_usd / ($btc_usd * $s), 6) : 0,
            ],
            'SOL' => [
                'RUB'       => round($sol_rub * $b, 2),
                'USD'       => round($sol_usd * $b, 2),
                'USDT_TRC20'=> round($sol_usd * $b, 2),
                'USDC'      => round($sol_usd * $b, 2),
                'ETH'       => $eth_usd > 0 ? round($sol_usd / ($eth_usd * $s), 6) : 0,
                'BTC'       => $btc_usd > 0 ? round($sol_usd / ($btc_usd * $s), 8) : 0,
            ],
            'BTC' => [
                'RUB'       => round($btc_rub * $b, 0),
                'USD'       => round($btc_usd * $b, 2),
                'USDT_TRC20'=> round($btc_usd * $b, 2),
                'USDC'      => round($btc_usd * $b, 2),
                'ETH'       => $eth_usd > 0 ? round($btc_usd / ($eth_usd * $s), 4) : 0,
                'SOL'       => $sol_usd > 0 ? round($btc_usd / ($sol_usd * $s), 2) : 0,
            ],
            'RUB' => [
                'USDT_TRC20'=> $usdt_rub > 0 ? round(1 / ($usdt_rub * $s), 6) : 0,
                'USDC'      => $usdc_rub > 0 ? round(1 / ($usdc_rub * $s), 6) : 0,
                'ETH'       => $eth_rub  > 0 ? round(1 / ($eth_rub  * $s), 8) : 0,
                'SOL'       => $sol_rub  > 0 ? round(1 / ($sol_rub  * $s), 6) : 0,
                'BTC'       => $btc_rub  > 0 ? round(1 / ($btc_rub  * $s), 10): 0,
                'USD'       => $usd_rub  > 0 ? round(1 / ($usd_rub  * $s), 4) : 0,
            ],
            'USD' => [
                'USDT_TRC20'=> $usdt_usd > 0 ? round(1 / ($usdt_usd * $s), 4) : 0,
                'USDC'      => $usdc_usd > 0 ? round(1 / ($usdc_usd * $s), 4) : 0,
                'ETH'       => $eth_usd  > 0 ? round(1 / ($eth_usd  * $s), 8) : 0,
                'SOL'       => $sol_usd  > 0 ? round(1 / ($sol_usd  * $s), 6) : 0,
                'BTC'       => $btc_usd  > 0 ? round(1 / ($btc_usd  * $s), 8) : 0,
                'RUB'       => round($usd_rub * $b, 2),
            ],
        ];

        $limits = [
            'USDT_TRC20' => ['min' => 50,    'max' => 55000],
            'USDC'       => ['min' => 50,    'max' => 55000],
            'ETH'        => ['min' => 0.01,  'max' => 100],
            'SOL'        => ['min' => 0.5,   'max' => 10000],
            'BTC'        => ['min' => 0.001, 'max' => 10],
            'RUB'        => ['min' => 5000,  'max' => 2000000],
            'USD'        => ['min' => 50,    'max' => 50000],
        ];

        $reserves = [
            'USDT_TRC20' => 1245678.45,
            'USDC'       => 500000.00,
            'ETH'        => 150.00,
            'SOL'        => 5000.00,
            'BTC'        => 12.78451637,
            'RUB'        => 45892000,
            'USD'        => 500000.00,
        ];

        saveCachedRates($rates, $reserves, $limits);
    }
}

// ================================================
// ДИНАМИЧЕСКИЕ РЕЗЕРВЫ ИЗ БАЗЫ ДАННЫХ
// ================================================

function getReserve($currency) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT amount FROM reserves WHERE currency = ?");
    $stmt->execute([$currency]);
    $amount = $stmt->fetchColumn();
    return $amount !== false ? (float)$amount : 0.0;
}

function updateReserve($currency, $amount_to_subtract) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO reserves (currency, amount) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE amount = amount - ?
    ");
    $stmt->execute([$currency, $amount_to_subtract, $amount_to_subtract]);
    return true;
}

function hasEnoughReserve($currency, $required_amount) {
    $current = getReserve($currency);
    return $current >= $required_amount;
}

// ================================================
// Сохранение данных обмена перед редиректом на логин
// ================================================

if (!function_exists('savePendingExchange')) {
    function savePendingExchange() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            session_start();
            $_SESSION['pending_exchange'] = [
                'give'        => $_POST['give_currency'] ?? 'USDT_TRC20',
                'get'         => $_POST['get_currency']  ?? 'RUB',
                'amount_give' => floatval($_POST['amount_give'] ?? 100),
            ];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['give_currency'])) {
    savePendingExchange();
}