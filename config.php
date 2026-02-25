<?php
// config.php

define('SITE_NAME', 'BekirovSwap');
define('SITE_URL', 'https://your-domain.com');
define('ADMIN_EMAIL', 'admin@your-domain.com');

// Фейковые курсы (в реальности — из API или базы)
$rates = [
    'USDT_TRC20' => ['RUB' => 105.50, 'USD' => 1.00, 'EUR' => 0.94],
    'BTC'        => ['USDT_TRC20' => 98000, 'RUB' => 10320000],
    'RUB'        => ['USDT_TRC20' => 0.00948, 'USD' => 0.0095],
    // добавляйте свои направления
];

// Минимальная / максимальная сумма
$limits = [
    'USDT_TRC20' => ['min' => 50, 'max' => 50000],
    'RUB'        => ['min' => 5000, 'max' => 2000000],
    // ...
];

// Резервы (для отображения и BestChange)
$reserves = [
    'USDT_TRC20' => 1245678.45,
    'RUB'        => 45892000,
    'BTC'        => 12.784,
];