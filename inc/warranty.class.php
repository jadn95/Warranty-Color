<?php

class PluginWarrantycolorWarranty
{

    public static function getWarrantyStatus($itemtype, $items_id)
    {

        global $DB;

        $iterator = $DB->request([
            'FROM' => 'glpi_infocoms',
            'WHERE' => [
                'itemtype' => $itemtype,
                'items_id' => $items_id
            ],
            'SELECT' => [
                'warranty_date',
                'warranty_duration'
            ]
        ]);


$warranty_date = null;
$warranty_duration = 0;

foreach ($iterator as $row) {
    $warranty_date = $row['warranty_date'];
    $warranty_duration = $row['warranty_duration'];
    break;
}

        if (empty($warranty_date) || $warranty_date == '0000-00-00') {
            return [
                'status' => 'expired',
                'color' => null
            ];
        }


        $config = PluginWarrantycolorConfig::getConfig();


$today = new DateTime();

$expiration = new DateTime($warranty_date);

if ($warranty_duration > 0) {
    $expiration->modify('+' . $warranty_duration . ' months');
}

if ($expiration < $today) {
            return [
                'status' => 'expired',
                'color' => null
            ];
        }


        $days = $today->diff($expiration)->days;


        if ($days <= $config['days_before_expiration']) {
            return [
                'status' => 'warning',
                'color' => $config['color_warning']
            ];
        }


        return [
            'status' => 'valid',
            'color' => $config['color_valid']
        ];
    }
}
