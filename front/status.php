<?php

/**
 * Renvoie l'état de garantie des équipements affichés dans une liste (lecture seule).
 *   GET ?item[]=Computer:12&href[]=/front/printer.form.php?id=3 ...   (item = Type:id, href = adresse de fiche ; clés renvoyées telles quelles)
 *   => {"config":{...},"labels":{...},"items":{"<href>":{"state":"valid","color":"#2ecc71","until":"2027-03-12","days":120,"label":"..."}}}
 */

use GlpiPlugin\Warrantycolor\Config;
use GlpiPlugin\Warrantycolor\Warranty;

include __DIR__ . '/../../../inc/includes.php';

Session::checkLoginUser();
session_write_close();      // lecture seule : libère le verrou de session, les lots demandés en parallèle ne s'attendent plus

header('Content-Type: application/json; charset=utf-8');

if (!Plugin::isPluginActive('warrantycolor')) {
    http_response_code(404);
    echo '{}';
    return;
}

$keys = array_values(array_filter(array_merge((array) ($_GET['item'] ?? []), (array) ($_GET['href'] ?? [])), 'is_string'));
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
        'list'    => __('Lister tous les équipements : %s', 'warrantycolor'),
    ],
    'colors' => ['valid' => $conf['color_valid'], 'warning' => $conf['color_warning']],
    'types'  => Infocom::canView() ? Warranty::getItemtypes() : [],       // types dont les listes reçoivent la légende
    'items'  => (object) Warranty::forLinks($keys),
], JSON_UNESCAPED_UNICODE);
