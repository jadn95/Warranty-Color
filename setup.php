<?php

file_put_contents(
    '/tmp/warranty_setup_debug.log',
    date('Y-m-d H:i:s')." SETUP CHARGE\n",
    FILE_APPEND
);

define('PLUGIN_WARRANTYCOLOR_VERSION', '1.0.0');


function plugin_init_warrantycolor()
{
    global $PLUGIN_HOOKS;

    file_put_contents(
        '/tmp/warranty_init.log',
        date('Y-m-d H:i:s') . " INIT OK\n",
        FILE_APPEND
    );


    include_once(__DIR__ . '/inc/config.class.php');
    include_once(__DIR__ . '/inc/warranty.class.php');
    include_once(__DIR__ . '/inc/hook.class.php');
    include_once(__DIR__ . '/inc/display.class.php');


    $PLUGIN_HOOKS['config_page']['warrantycolor'] = 'front/config.php';


    $PLUGIN_HOOKS['post_show_item']['warrantycolor'] = 'PluginWarrantycolorHook::postShowItem';
}

function plugin_version_warrantycolor()
{
    return [
        'name'           => 'Warranty Color',
        'version'        => PLUGIN_WARRANTYCOLOR_VERSION,
        'author'         => 'TKACZYK Andy',
        'license'        => 'GPLv3',
        'requirements'   => [
            'glpi' => [
                'min' => '11.0.0'
            ]
        ]
    ];
}


function plugin_warrantycolor_check_prerequisites()
{
    return true;
}


function plugin_warrantycolor_check_config()
{
    return true;
}


function plugin_warrantycolor_install()
{
    include_once(__DIR__ . '/inc/migration.class.php');
    return PluginWarrantycolorMigration::install();
}


function plugin_warrantycolor_uninstall()
{
    include_once(__DIR__ . '/inc/migration.class.php');
    return PluginWarrantycolorMigration::uninstall();
}
