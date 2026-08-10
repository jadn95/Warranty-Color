<?php

class PluginWarrantycolorDisplay
{

    public static function getColor($itemtype, $items_id)
    {

        $result = PluginWarrantycolorWarranty::getWarrantyStatus(
            $itemtype,
            $items_id
        );

        if (!empty($result['color'])) {
            return $result['color'];
        }

        return null;
    }


    public static function getStyle($itemtype, $items_id)
    {

        $color = self::getColor($itemtype, $items_id);

        if ($color) {
            return 'style="color:' . $color . ';"';
        }

        return '';

    }

}
