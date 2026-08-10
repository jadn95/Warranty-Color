<?php

class PluginWarrantycolorHook
{

    public static function postItemForm($params)
    {
        file_put_contents(
            '/tmp/warranty_hook_test.log',
            "POST ITEM FORM\n" . print_r($params, true) . "\n",
            FILE_APPEND
        );
    }

}
