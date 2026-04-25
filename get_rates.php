<?php
header('Content-Type: application/json');
require_once 'config.php';

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

    $b = 0.975;
    $s = 1.025;

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

    echo json_encode([
        'rates'    => $rates,
        'reserves' => $reserves,
        'limits'   => $limits,
        'source'   => 'api',
    ]);
    exit;
}

$cached = getCachedRates();

if ($cached) {
    echo json_encode([
        'rates'    => $cached['rates'],
        'reserves' => $cached['reserves'],
        'limits'   => $cached['limits'],
        'source'   => 'cache',
    ]);
} else {
    echo json_encode([
        'rates'    => [],
        'reserves' => [],
        'limits'   => [],
        'source'   => 'fallback',
    ]);
}

exit;
