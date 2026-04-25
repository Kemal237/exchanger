<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'db.php';

// Резервы и лимиты — всегда из БД (актуальные)
$db_reserves = [];
$db_limits   = [];
try {
    $stmt = $pdo->query("SELECT currency, amount, min, max FROM reserves");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $db_reserves[$row['currency']] = (float)$row['amount'];
        $db_limits[$row['currency']]   = ['min' => (float)$row['min'], 'max' => (float)$row['max']];
    }
} catch (Exception $e) {}

// Курсы — из кеша, если свежий; иначе запрос к CoinGecko
$cached = getCachedRates();

if ($cached && !empty($cached['rates'])) {
    $rates = $cached['rates'];
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
        $usd_rub  = $usdt_rub;

        $b = 0.975; $s = 1.025;

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

        // Сохраняем только курсы в кеш (резервы берём из БД)
        saveCachedRates($rates, $cached['reserves'] ?? [], $cached['limits'] ?? []);
    } else {
        $rates = [];
    }
}

// Слияние: DB имеет приоритет над кешем для резервов/лимитов
$reserves = array_merge($cached['reserves'] ?? [], $db_reserves);
$limits   = array_merge($cached['limits']   ?? [], $db_limits);

echo json_encode([
    'rates'    => $rates,
    'reserves' => $reserves,
    'limits'   => $limits,
    'source'   => empty($db_reserves) ? 'cache' : 'db+cache',
]);
exit;
