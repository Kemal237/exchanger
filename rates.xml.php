<?php
require 'config.php';
header('Content-Type: text/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<rates>
  <?php foreach ($rates as $from => $to_list): ?>
    <?php foreach ($to_list as $to => $rate): ?>
      <item>
        <from><?= htmlspecialchars($from) ?></from>
        <to><?= htmlspecialchars($to) ?></to>
        <in><?= number_format($rate, 6, '.', '') ?></in>
        <out>0</out> <!-- или рассчитайте обратный курс -->
        <reserve><?= number_format($reserves[$to] ?? 0, 2, '.', '') ?></reserve>
        <manual>0</manual>
      </item>
    <?php endforeach; ?>
  <?php endforeach; ?>
</rates>