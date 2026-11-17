<?php

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Warrantycolor\Config;
use GlpiPlugin\Warrantycolor\Warranty;

global $CFG_GLPI, $DB;        // les pages de plugin sont exécutées dans une fonction : les globales ne sont pas toujours visibles

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

// Adresse réelle de la page : PHP_SELF vaut « /index.php » sous GLPI 12, ce qui enverrait le formulaire au point d'entrée d'inventaire (« XML not well formed »).
$self = $CFG_GLPI['root_doc'] . '/plugins/warrantycolor/front/config.php';

$statesMap = [];                // id => nom, pour la liste déroulante des états
foreach ($DB->request(['SELECT' => ['id', 'completename'], 'FROM' => 'glpi_states', 'ORDER' => 'completename']) as $row) {
    $statesMap[(int) $row['id']] = (string) $row['completename'];
}

Html::header(__('Warranty Color', 'warrantycolor'), $self, 'config', 'plugins');

TemplateRenderer::getInstance()->display('@warrantycolor/config.html.twig', [
    'action'     => $self,
    'conf'       => Config::get(),
    'defaults'   => Config::DEFAULTS,
    'days_min'   => Config::DAYS_MIN,
    'days_max'   => Config::DAYS_MAX,
    'states_map' => $statesMap,
    'warnings'   => Warranty::diagnostics(),
    'needs_csrf' => version_compare(GLPI_VERSION, '12', '<'),      // GLPI 12 gère le jeton CSRF ; la 11 attend encore le champ
]);

Html::footer();
