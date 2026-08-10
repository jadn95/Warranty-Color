<?php

include('../../../inc/includes.php');
include_once('../inc/config.class.php');


$config = PluginWarrantycolorConfig::getConfig();


if (isset($_POST['update'])) {

//    Session::checkCSRF($_POST);
file_put_contents(
    '/tmp/warranty_config_save.log',
    date('Y-m-d H:i:s') . " SAVE appelé\n",
    FILE_APPEND
);

    PluginWarrantycolorConfig::saveConfig(
        [
            'days_before_expiration' => intval($_POST['days_before_expiration']),
            'color_valid'            => $_POST['color_valid'],
            'color_warning'          => $_POST['color_warning']
        ]
    );


    Html::back();
}


Html::header(
    'Warranty Color',
    $_SERVER['PHP_SELF'],
    'config',
    'plugins'
);


echo "<div class='center'>";


echo "<form method='post' action='config.php'>";


echo Html::hidden('_glpi_csrf_token', [
    'value' => Session::getNewCSRFToken()
]);


echo "<table class='tab_cadre_fixe'>";


echo "<tr>";
echo "<th colspan='2'>Configuration Warranty Color</th>";
echo "</tr>";


// Nombre de jours avant expiration

echo "<tr>";
echo "<td>Nombre de jours avant expiration</td>";
echo "<td>";

echo Html::input('days_before_expiration', [
    'value' => $config['days_before_expiration'],
    'type'  => 'number',
    'min'   => 0
]);

echo "</td>";
echo "</tr>";


// Couleur garantie valide

echo "<tr>";
echo "<td>Couleur garantie valide</td>";
echo "<td>";

echo Html::input('color_valid', [
    'value' => $config['color_valid'],
    'type'  => 'color'
]);

echo "</td>";
echo "</tr>";


// Couleur expiration proche

echo "<tr>";
echo "<td>Couleur expiration proche</td>";
echo "<td>";

echo Html::input('color_warning', [
    'value' => $config['color_warning'],
    'type'  => 'color'
]);

echo "</td>";
echo "</tr>";


echo "</table>";


echo "<br>";


echo "<button type='submit' name='update' class='submit'>";
echo "Enregistrer";
echo "</button>";


echo "</form>";


echo "</div>";


Html::footer();
