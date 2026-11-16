<?php

namespace GlpiPlugin\Warrantycolor;

final class Migration
{
    /** Installation et mise à jour (rappelée par GLPI à chaque changement de version : doit être idempotente). */
    public static function install(): bool
    {
        global $DB;

        $current = \Config::getConfigurationValues(Config::CONTEXT);
        $values  = $current + Config::DEFAULTS;

        // Reprise de la table propre aux versions 1.x, puis suppression.
        if ($DB->tableExists('glpi_plugin_warrantycolor_configs')) {
            if (!$current) {
                $old = $DB->request(['FROM' => 'glpi_plugin_warrantycolor_configs', 'LIMIT' => 1])->current();
                if ($old) {
                    foreach (['days_before_expiration', 'color_valid', 'color_warning'] as $key) {
                        if (isset($old[$key])) {
                            $values[$key] = $old[$key];
                        }
                    }
                }
            }
            $DB->doQuery('DROP TABLE `glpi_plugin_warrantycolor_configs`');
        }

        \Config::setConfigurationValues(Config::CONTEXT, $values);

        return true;
    }

    public static function uninstall(): bool
    {
        \Config::deleteConfigurationValues(Config::CONTEXT, array_keys(Config::DEFAULTS));

        return true;
    }
}
