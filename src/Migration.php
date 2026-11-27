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

        self::createAlertTable();
        // Action automatique quotidienne des mails de fin de garantie (sans effet tant que l'option n'est pas activée dans la configuration).
        \CronTask::register(Alert::class, Alert::CRON, DAY_TIMESTAMP, [
            'comment' => __('Envoie aux utilisateurs un mail avant la fin de garantie de leur équipement', 'warrantycolor'),
            'state'   => \CronTask::STATE_WAITING,
            'mode'    => \CronTask::MODE_INTERNAL,
        ]);
        Notifications::install();

        return true;
    }

    /** Table de suivi des mails envoyés (un par équipement, dernier jour de garantie et étape). */
    private static function createAlertTable(): void
    {
        global $DB;

        if ($DB->tableExists(Alert::TABLE)) {
            return;
        }
        $charset   = \DBConnection::getDefaultCharset();
        $collation = \DBConnection::getDefaultCollation();
        $sign      = \DBConnection::getDefaultPrimaryKeySignOption();
        $DB->doQuery("CREATE TABLE `" . Alert::TABLE . "` (
            `id` int {$sign} NOT NULL AUTO_INCREMENT,
            `entities_id` int {$sign} NOT NULL DEFAULT 0,
            `itemtype` varchar(255) NOT NULL,
            `items_id` int {$sign} NOT NULL DEFAULT 0,
            `users_id` int {$sign} NOT NULL DEFAULT 0,
            `warranty_end` date NOT NULL,
            `stage` varchar(10) NOT NULL DEFAULT 'first',
            `mode` varchar(10) NOT NULL DEFAULT 'sent',
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`itemtype`(190), `items_id`, `warranty_end`, `stage`),
            KEY `entities_id` (`entities_id`),
            KEY `users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC");
    }

    public static function uninstall(): bool
    {
        global $DB;

        Notifications::uninstall();
        \CronTask::unregister('warrantycolor');
        if ($DB->tableExists(Alert::TABLE)) {
            $DB->doQuery('DROP TABLE `' . Alert::TABLE . '`');
        }
        \Config::deleteConfigurationValues(Config::CONTEXT, array_keys(Config::DEFAULTS));

        return true;
    }
}
