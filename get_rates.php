<?php
header('Content-Type: application/json');
require_once 'config.php';

$real_rates = getRealRates();

if ($real_rates && !empty($real_rates['tether']) && !empty($real_rates['bitcoin'])) {
    $market_usdt_rub = $real_rates['tether']['rub']   ?? null;
    $market_usdt_usd = $real_rates['tether']['usd']   ?? null;
    $market_btc_usd  = $real_rates['bitcoin']['usd']  ?? null;

    if ($market_usdt_rub !== null && $market_btc_usd !== null) {
        $market_usd_rub = $market_usdt_rub;

        $markup_sell = 1.025;
        $markup_buy  = 0.975;

        $rates = [
            'USDT_TRC20' => [
                'RUB' => round($market_usdt_rub * $markup_sell, 2),
                'BTC' => $market_btc_usd > 0 ? round(1 / $market_btc_usd * $markup_buy, 8) : null,
            ],
            'RUB' => [
                'USDT_TRC20' => $market_usdt_rub > 0 ? round(1 / ($market_usdt_rub * $markup_buy), 6) : null,
                'BTC'        => ($market_btc_usd && $market_usd_rub) ? round(1 / ($market_btc_usd * $market_usd_rub * $markup_buy), 8) : null,
            ],
            'BTC' => [
                'USDT_TRC20' => $market_btc_usd > 0 ? round($market_btc_usd * $markup_sell, 0) : null,
                'RUB'        => ($market_btc_usd && $market_usd_rub) ? round($market_btc_usd * $market_usd_rub * $markup_sell, 0) : null,
            ],
        ];

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

        echo json_encode([
            'rates'    => $rates,
            'reserves' => $reserves,
            'limits'   => $limits,
            'source'   => 'api'
        ]);
        exit;
    }
}

$cached = getCachedRates();

if ($cached) {
    echo json_encode([
        'rates'    => $cached['rates'],
        'reserves' => $cached['reserves'],
        'limits'   => $cached['limits'],
        'source'   => 'cache'
    ]);
} else {
    echo json_encode([
        'rates'    => $rates ?? [],
        'reserves' => $reserves ?? [],
        'limits'   => $limits ?? [],
        'source'   => 'fallback'
    ]);
}

exit;