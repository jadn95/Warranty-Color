<?php

use GlpiPlugin\Warrantycolor\Config;

include __DIR__ . '/../../../inc/includes.php';

Session::checkRight('config', UPDATE);

if (isset($_POST['update'])) {
    $errors = Config::save($_POST);
    if ($errors) {
        foreach ($errors as $error) {
            Session::addMessageAfterRedirect(htmlspecialchars($error), false, ERROR);
        }
    } else {
        Session::addMessageAfterRedirect(__('Configuration enregistrée', 'warrantycolor'));
    }
    Html::back();
}

$conf = Config::get();
$d    = Config::DEFAULTS;
$e    = static fn($v): string => htmlspecialchars((string) $v);

Html::header(__('Warranty Color', 'warrantycolor'), $_SERVER['PHP_SELF'], 'config', 'plugins');

echo "<div class='container-fluid'><div class='card' style='max-width: 48rem'><div class='card-header'><h3 class='card-title'>"
    . $e(__('Warranty Color — configuration', 'warrantycolor')) . "</h3></div><div class='card-body'>";

echo "<form method='post' action='" . $e($_SERVER['PHP_SELF']) . "' id='wc-config-form'"
    . " data-default-days='{$d['days_before_expiration']}' data-default-valid='{$d['color_valid']}'"
    . " data-default-warning='{$d['color_warning']}' data-default-full-row='{$d['full_row']}'>";
// GLPI 12 gère le jeton CSRF automatiquement ; seule la 11 attend encore le champ.
if (version_compare(GLPI_VERSION, '12', '<')) {
    echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
}

echo "<div class='mb-3'><label class='form-label' for='wc-days'>" . $e(__('Alerte avant expiration (jours)', 'warrantycolor')) . "</label>"
    . "<input id='wc-days' name='days_before_expiration' type='number' class='form-control' style='max-width: 8rem' required"
    . " min='" . Config::DAYS_MIN . "' max='" . Config::DAYS_MAX . "' value='" . $e($conf['days_before_expiration']) . "' aria-describedby='wc-days-help'>"
    . "<div class='form-text' id='wc-days-help'>" . $e(__('Une garantie qui expire dans ce délai est signalée en orange.', 'warrantycolor')) . "</div></div>";

foreach (['color_valid' => [__('Couleur « garantie valide »', 'warrantycolor'), 'wc-valid'], 'color_warning' => [__('Couleur « expiration proche »', 'warrantycolor'), 'wc-warning']] as $name => [$label, $id]) {
    echo "<div class='mb-3'><label class='form-label' for='$id'>" . $e($label) . "</label>"
        . "<div class='d-flex align-items-center gap-2'><input id='$id' name='$name' type='color' class='form-control form-control-color' value='" . $e($conf[$name]) . "'>"
        . "<code data-hex-for='$name'>" . $e($conf[$name]) . "</code></div></div>";
}

echo "<div class='mb-3'><label class='form-check'><input type='checkbox' class='form-check-input' name='full_row' value='1'" . ($conf['full_row'] ? ' checked' : '') . ">"
    . "<span class='form-check-label'>" . $e(__('Teinter toute la ligne (sinon : pastille et repère latéral seulement)', 'warrantycolor')) . "</span></label></div>";

// Aperçu en direct
echo "<div class='mb-3'><div class='form-label'>" . $e(__('Aperçu', 'warrantycolor')) . "</div>"
    . "<table class='table table-sm table-striped wc-preview' id='wc-preview'><tbody>";
foreach ([
    ['valid', 'ti-shield-check', __('Garantie valide', 'warrantycolor')],
    ['warning', 'ti-alert-triangle', sprintf(__('Expire dans %d jours ou moins', 'warrantycolor'), $conf['days_before_expiration'])],
] as [$state, $icon, $text]) {
    echo "<tr data-warranty='$state'><td>PC-" . strtoupper($state) . "<span class='warranty-badge' data-state='$state' role='img' aria-label='" . $e($text) . "' tabindex='0' data-tip='" . $e($text) . "'><i class='ti $icon' aria-hidden='true'></i></span></td><td>" . $e($text) . "</td></tr>";
}
echo "<tr><td>PC-EXPIRED<span class='warranty-badge' data-state='expired' role='img' aria-label='" . $e(__('Expirée', 'warrantycolor')) . "' tabindex='0' data-tip='" . $e(__('Expirée', 'warrantycolor')) . "'><i class='ti ti-shield-off' aria-hidden='true'></i></span></td><td>" . $e(__('Expirée', 'warrantycolor')) . "</td></tr>";
echo "</tbody></table></div>";

echo "<div class='d-flex gap-2'><button type='submit' name='update' class='btn btn-primary'>" . $e(_sx('button', 'Save')) . "</button>"
    . "<button type='button' id='wc-reset' class='btn btn-outline-secondary'>" . $e(__('Valeurs par défaut', 'warrantycolor')) . "</button></div>";

echo "</form></div></div></div>";

Html::footer();
