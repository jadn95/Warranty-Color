<?php

class PluginWarrantycolorMigration
{

    public static function install()
    {
        global $DB;

        $migration = new Migration(
            PLUGIN_WARRANTYCOLOR_VERSION
        );


        if (!$DB->tableExists('glpi_plugin_warrantycolor_configs')) {

            $DB->doQuery("
                CREATE TABLE glpi_plugin_warrantycolor_configs (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");


            $migration->addField(
                'glpi_plugin_warrantycolor_configs',
                'days_before_expiration',
                'integer'
            );

            $migration->addField(
                'glpi_plugin_warrantycolor_configs',
                'color_valid',
                'string'
            );

            $migration->addField(
                'glpi_plugin_warrantycolor_configs',
                'color_warning',
                'string'
            );


            $migration->insertInTable(
                'glpi_plugin_warrantycolor_configs',
                [
                    'days_before_expiration' => 30,
                    'color_valid' => '#2ecc71',
                    'color_warning' => '#ff9900'
                ]
            );
        }


        $migration->executeMigration();

        return true;
    }



    public static function uninstall()
    {
        global $DB;

        $migration = new Migration(
            PLUGIN_WARRANTYCOLOR_VERSION
        );


        if ($DB->tableExists('glpi_plugin_warrantycolor_configs')) {

            $migration->dropTable(
                'glpi_plugin_warrantycolor_configs'
            );

        }


        $migration->executeMigration();

        return true;
    }
}
