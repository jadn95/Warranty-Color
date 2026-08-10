<?php

class PluginWarrantycolorConfig extends CommonDBTM
{

    public static function getConfig()
    {
        global $DB;

        $result = $DB->request([
            'FROM' => 'glpi_plugin_warrantycolor_configs',
            'LIMIT' => 1
        ]);

        return $result->current();
    }


    public static function saveConfig($data)
    {
        global $DB;

        $config = self::getConfig();

        if (!empty($config['id'])) {

            $DB->update(
                'glpi_plugin_warrantycolor_configs',
                [
                    'days_before_expiration' => $data['days_before_expiration'],
                    'color_valid'            => $data['color_valid'],
                    'color_warning'          => $data['color_warning']
                ],
                [
                    'id' => $config['id']
                ]
            );

        } else {

            $DB->insert(
                'glpi_plugin_warrantycolor_configs',
                [
                    'days_before_expiration' => $data['days_before_expiration'],
                    'color_valid'            => $data['color_valid'],
                    'color_warning'          => $data['color_warning']
                ]
            );
        }

        return true;
    }

}
