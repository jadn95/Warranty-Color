<?php

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Warrantycolor\Config;
use GlpiPlugin\Warrantycolor\Migration;
use GlpiPlugin\Warrantycolor\Warranty;

function plugin_warrantycolor_install(): bool
{
    return Migration::install();
}

function plugin_warrantycolor_uninstall(): bool
{
    return Migration::uninstall();
}

/**
 * Bandeau « garantie » au-dessus du formulaire d'un équipement.
 *
 * @param array{item: CommonDBTM, options: array} $params
 */
function plugin_warrantycolor_preItemForm(array $params): void
{
    $item = $params['item'] ?? null;
    if (!($item instanceof CommonDBTM) || $item->isNewItem() || !in_array($item::class, Warranty::getItemtypes(), true)) {
        return;
    }

    $info = Warranty::forItem($item::class, (int) $item->getID());
    if ($info === null || $info['state'] === 'none') {
        return;
    }

    $conf   = Config::get();
    $colors = ['valid' => $conf['color_valid'], 'warning' => $conf['color_warning'], 'expired' => null];
    $icons  = ['valid' => 'ti-shield-check', 'warning' => 'ti-alert-triangle', 'expired' => 'ti-shield-off'];
    // Mêmes couleurs que dans les listes (configuration du plugin) ; style défini dans warrantycolor.css.
    TemplateRenderer::getInstance()->display('@warrantycolor/banner.html.twig', [
        'state' => $info['state'],
        'color' => $colors[$info['state']],
        'icon'  => $icons[$info['state']],
        'label' => $info['label'],
    ]);
}
