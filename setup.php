<?php

use Glpi\Plugin\Hooks;

define('PLUGIN_WARRANTYCOLOR_VERSION', '2.0.0');
define('PLUGIN_WARRANTYCOLOR_MIN_GLPI', '11.0.0');

function plugin_init_warrantycolor(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['config_page']['warrantycolor'] = 'front/config.php';

    // Coloration des listes : feuille de style + script (appel de front/status.php pour les lignes affichées).
    $PLUGIN_HOOKS[Hooks::ADD_CSS]['warrantycolor']        = ['css/warrantycolor.css'];
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['warrantycolor'] = ['js/warrantycolor.js', 'js/config.js'];

    // Bandeau d'état de la garantie en haut de la fiche d'un équipement.
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_FORM]['warrantycolor'] = 'plugin_warrantycolor_preItemForm';
}

function plugin_version_warrantycolor(): array
{
    return [
        'name'         => 'Warranty Color',
        'version'      => PLUGIN_WARRANTYCOLOR_VERSION,
        'author'       => 'TKACZYK Andy',
        'license'      => 'GPLv3',
        'homepage'     => 'https://github.com/jadn95/Warranty-Color',
        'requirements' => [
            // Pas de borne « max » : l'API utilisée est stable ; à revalider à chaque version majeure.
            'glpi' => ['min' => PLUGIN_WARRANTYCOLOR_MIN_GLPI],
            'php'  => ['min' => '8.2'],
        ],
    ];
}

function plugin_warrantycolor_check_prerequisites(): bool
{
    return true;
}

function plugin_warrantycolor_check_config($verbose = false): bool
{
    return true;
}
