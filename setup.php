<?php

use Glpi\Plugin\Hooks;

define('PLUGIN_WARRANTYCOLOR_VERSION', '2.1.0');
define('PLUGIN_WARRANTYCOLOR_MIN_GLPI', '12.0.0');

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

/**
 * Prérequis au-delà de ceux déclarés dans plugin_version_warrantycolor() (version de GLPI, PHP).
 * GLPI appelle cette fonction sans argument et capture ce qu'elle affiche pour l'expliquer à l'administrateur ;
 * elle est appelée à chaque chargement du plugin : pas de requête en base ici.
 * Un échec désactive le plugin jusqu'à réactivation manuelle : on ne contrôle donc que des composants de GLPI,
 * jamais les fichiers du plugin lui-même (un déploiement en cours ne doit pas le désactiver).
 */
function plugin_warrantycolor_check_prerequisites(): bool
{
    $required = [
        \Infocom::class                                => __('informations financières de GLPI (garantie)', 'warrantycolor'),
        \Glpi\Application\View\TemplateRenderer::class => __('moteur de modèles Twig de GLPI', 'warrantycolor'),
    ];
    foreach ($required as $class => $label) {
        if (!class_exists($class)) {
            printf(__('Warranty Color : composant manquant — %s.', 'warrantycolor'), $label);

            return false;
        }
    }

    return true;
}

function plugin_warrantycolor_check_config($verbose = false): bool
{
    return true;
}
