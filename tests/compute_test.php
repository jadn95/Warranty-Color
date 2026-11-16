<?php

/**
 * Test autonome du calcul d'état (sans base) :  php tests/compute_test.php
 */

require_once __DIR__ . '/../src/Warranty.php';

use GlpiPlugin\Warrantycolor\Warranty;

$today = new DateTimeImmutable('2026-11-16');
$cases = [
    // date, durée (mois), délai d'alerte, état attendu
    ['2026-01-01', 12, 30, 'valid'],        // fin le 2027-01-01 : dans 46 j
    ['2025-12-20', 12, 30, 'valid'],        // fin le 2026-12-20 : dans 34 j
    ['2025-12-01', 12, 30, 'warning'],      // fin le 2026-12-01 : dans 15 j
    ['2025-11-16', 12, 30, 'warning'],      // fin aujourd'hui : 0 j
    ['2025-11-15', 12, 30, 'expired'],      // fin hier
    ['2020-01-01', 0, 30, 'none'],          // durée 0 : non renseignée
    ['2020-01-01', -1, 30, 'valid'],        // à vie
    [null, 12, 30, 'none'],
    ['0000-00-00', 12, 30, 'none'],
    ['2024-00-00', 12, 30, 'none'],         // date corrompue : ignorée, pas d'exception
    ['2026-01-31', 1, 30, 'expired'],       // 31 janvier + 1 mois = 3 mars 2026
];
$fail = 0;
foreach ($cases as [$date, $duration, $warn, $expected]) {
    $got = Warranty::computeState($date, $duration, $today, $warn)['state'];
    $ok  = $got === $expected;
    $fail += $ok ? 0 : 1;
    printf("%s  %-12s %3d mois  -> %-8s (attendu %s)\n", $ok ? 'OK ' : 'ERR', var_export($date, true), $duration, $got, $expected);
}
exit($fail ? 1 : 0);
