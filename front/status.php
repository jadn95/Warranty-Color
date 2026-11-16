<?php

/**
 * Renvoie l'état de garantie des équipements affichés dans une liste (lecture seule).
 *   GET ?href[]=/front/computer.form.php?id=12&href[]=...
 *   => {"config":{...},"labels":{...},"items":{"<href>":{"state":"valid","color":"#2ecc71","until":"2027-03-12","days":120,"label":"..."}}}
 */

use GlpiPlugin\Warrantycolor\Config;
use GlpiPlugin\Warrantycolor\Warranty;

include __DIR__ . '/../../../inc/includes.php';

Session::checkLoginUser();

header('Content-Type: application/json; charset=utf-8');

if (!Plugin::isPluginActive('warrantycolor')) {
    http_response_code(404);
    echo '{}';
    return;
}

$hrefs = array_values(array_filter((array) ($_GET['href'] ?? []), 'is_string'));
$conf  = Config::get();

echo json_encode([
    'config' => [
        'days'     => $conf['days_before_expiration'],
        'full_row' => (bool) $conf['full_row'],
    ],
    'labels' => [
        'valid'   => __('Garantie valide', 'warrantycolor'),
        'warning' => sprintf(__('Expire dans %d jours ou moins', 'warrantycolor'), $conf['days_before_expiration']),
        'expired' => __('Expirée', 'warrantycolor'),
        'title'   => __('Garantie', 'warrantycolor'),
    ],
    'colors' => ['valid' => $conf['color_valid'], 'warning' => $conf['color_warning']],
    'items'  => (object) Warranty::forLinks($hrefs),
], JSON_UNESCAPED_UNICODE);
