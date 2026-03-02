<?php
header('Content-Type: application/json');
require_once 'config.php';

// Пытаемся получить свежие курсы
$real_rates = getRealRates();

if ($real_rates && !empty($real_rates['tether']) && !empty($real_rates['bitcoin'])) {
    // Пересчитываем $rates, $reserves, $limits (вставь сюда свой текущий код пересчёта)
    $market_usdt_rub = $real_rates['tether']['rub']     ?? null;
    $market_usdt_usd = $real_rates['tether']['usd']     ?? null;
    $market_usdt_eur = $real_rates['tether']['eur']     ?? null;
    $market_btc_usd  = $real_rates['bitcoin']['usd']    ?? null;
    $market_btc_eur  = $real_rates['bitcoin']['eur']    ?? null;

    if ($market_usdt_rub !== null && $market_usdt_usd !== null && $market_usdt_eur !== null &&
        $market_btc_usd !== null && $market_btc_eur !== null) {

        $usd_rub = $market_usdt_rub;
        $eur_rub = $market_usdt_eur > 0 ? $usd_rub / $market_usdt_eur : null;

        $market_usd_eur = $market_usdt_eur;
        $market_eur_usd = $market_usdt_eur > 0 ? 1 / $market_usdt_eur : null;

        $markup_sell = 1.025;
        $markup_buy  = 0.975;

        $rates = [
            'USDT_TRC20' => [
                'RUB' => round($market_usdt_rub * $markup_sell, 2),
                'USD' => round($market_usdt_usd * $markup_sell, 4),
                'EUR' => round($market_usdt_eur * $markup_sell, 4),
                'BTC' => $market_btc_usd > 0 ? round(1 / $market_btc_usd * $markup_buy, 8) : null,
            ],
            'RUB' => [
                'USDT_TRC20' => $market_usdt_rub > 0 ? round(1 / ($market_usdt_rub * $markup_buy), 6) : null,
                'USD'        => $market_usd_rub > 0 ? round(1 / ($market_usd_rub * $markup_buy), 6) : null,
                'EUR'        => $eur_rub > 0 ? round(1 / ($eur_rub * $markup_buy), 6) : null,
                'BTC'        => ($market_btc_usd && $market_usd_rub) ? round(1 / ($market_btc_usd * $market_usd_rub * $markup_buy), 8) : null,
            ],
            'BTC' => [
                'USDT_TRC20' => $market_btc_usd > 0 ? round($market_btc_usd * $markup_sell, 0) : null,
                'USD'        => $market_btc_usd > 0 ? round($market_btc_usd * $markup_sell, 0) : null,
                'RUB'        => ($market_btc_usd && $market_usd_rub) ? round($market_btc_usd * $market_usd_rub * $markup_sell, 0) : null,
                'EUR'        => $market_btc_eur > 0 ? round($market_btc_eur * $markup_sell, 0) : null,
            ],
            'USD' => [
                'USDT_TRC20' => $market_usdt_usd > 0 ? round(1 / ($market_usdt_usd * $markup_buy), 4) : null,
                'RUB'        => $market_usd_rub > 0 ? round($market_usd_rub * $markup_sell, 2) : null,
                'EUR'        => $market_usd_eur > 0 ? round($market_usd_eur * $markup_sell, 4) : null,
                'BTC'        => $market_btc_usd > 0 ? round(1 / ($market_btc_usd * $markup_buy), 8) : null,
            ],
            'EUR' => [
                'USDT_TRC20' => $market_usdt_eur > 0 ? round(1 / ($market_usdt_eur * $markup_buy), 4) : null,
                'RUB'        => $eur_rub > 0 ? round($eur_rub * $markup_sell, 2) : null,
                'USD'        => $market_eur_usd > 0 ? round($market_eur_usd * $markup_sell, 4) : null,
                'BTC'        => ($market_btc_usd && $market_usd_eur) ? round(1 / ($market_btc_usd * $market_usd_eur * $markup_buy), 8) : null,
            ],
        ];

        $limits = [
            'USDT_TRC20' => ['min' => 50,    'max' => 55000],
            'RUB'        => ['min' => 5000,  'max' => 2000000],
            'BTC'        => ['min' => 0.001, 'max' => 10],
            'USD'        => ['min' => 50,    'max' => 60000],
            'EUR'        => ['min' => 50,    'max' => 70000],
        ];

        $reserves = [
            'USDT_TRC20' => 1245678.45,
            'RUB'        => 45892000,
            'BTC'        => 12.78451637,
            'USD'        => 101233,
            'EUR'        => 300002,
        ];

        // Сохраняем успешный результат в кэш
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

// Если API не вернул нормальные данные — берём из кэша
$cached = getCachedRates();

if ($cached) {
    echo json_encode([
        'rates'    => $cached['rates'],
        'reserves' => $cached['reserves'],
        'limits'   => $cached['limits'],
        'source'   => 'cache'
    ]);
} else {
    // Если кэша нет — возвращаем дефолтные значения (только при первом запуске)
    echo json_encode([
        'rates'    => $rates,
        'reserves' => $reserves,
        'limits'   => $limits,
        'source'   => 'fallback'
    ]);
}

exit;