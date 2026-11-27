<?php

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Warrantycolor\Alert;
use GlpiPlugin\Warrantycolor\Config;
use GlpiPlugin\Warrantycolor\NotificationTargetAlert;
use GlpiPlugin\Warrantycolor\Warranty;

global $CFG_GLPI, $DB;        // les pages de plugin sont exécutées dans une fonction : les globales ne sont pas toujours visibles

Session::checkRight('config', UPDATE);

// Adresse réelle de la page : PHP_SELF vaut « /index.php » sous GLPI 12, ce qui enverrait le formulaire au point d'entrée d'inventaire (« XML not well formed »).
$self = $CFG_GLPI['root_doc'] . '/plugins/warrantycolor/front/config.php';
$tab  = in_array($_POST['_tab'] ?? '', ['display', 'mails'], true) ? $_POST['_tab'] : 'display';

if (isset($_POST['wc_baseline'])) {
    // Marque les équipements concernés comme déjà notifiés (aucun mail) ; la configuration du formulaire n'est pas enregistrée.
    $report = Alert::run(false, true);
    Session::addMessageAfterRedirect(sprintf(__('%d équipement(s) marqué(s) comme déjà notifiés.', 'warrantycolor'), $report['marked']));
    Html::redirect($self . '#mails');
}

if (isset($_POST['update'])) {
    $errors = Config::save($_POST);
    if ($errors) {
        foreach ($errors as $error) {
            Session::addMessageAfterRedirect(htmlspecialchars($error), false, ERROR);
        }
    } else {
        Session::addMessageAfterRedirect(__('Configuration enregistrée', 'warrantycolor'));
    }
    Html::redirect($self . '#' . $tab);       // on revient sur l'onglet d'où vient l'enregistrement
}

$statesMap = [];                // id => nom, pour la liste déroulante des états
foreach ($DB->request(['SELECT' => ['id', 'completename'], 'FROM' => 'glpi_states', 'ORDER' => 'completename']) as $row) {
    $statesMap[(int) $row['id']] = (string) $row['completename'];
}

$conf    = Config::get();
$lastRun = Alert::lastRun();

Html::header(__('Warranty Color', 'warrantycolor'), $self, 'config', 'plugins');

TemplateRenderer::getInstance()->display('@warrantycolor/config.html.twig', [
    'action'            => $self,
    'conf'              => $conf,
    'defaults'          => Config::DEFAULTS,
    'days_min'          => Config::DAYS_MIN,
    'days_max'          => Config::DAYS_MAX,
    'states_map'        => $statesMap,
    'warnings'          => Warranty::diagnostics(),
    'needs_csrf'        => version_compare(GLPI_VERSION, '12', '<'),      // GLPI 12 gère le jeton CSRF ; la 11 attend encore le champ
    'active_tab'        => 'display',                                      // l'onglet « mails » est ouvert par le script (adresse #mails)
    'notify_ready'      => Alert::notificationsReady(),
    'report_days'       => Config::reportDays((int) $conf['days_before_expiration']),
    'sim'               => Alert::run(false),                              // simulation : ce qui partirait maintenant
    'tags'              => NotificationTargetAlert::tagLabels(),
    'last_run'          => $lastRun,
    'notifications_url' => $CFG_GLPI['root_doc'] . '/front/setup.notification.php',
    'crontask_url'      => $lastRun ? $CFG_GLPI['root_doc'] . '/front/crontask.form.php?id=' . $lastRun['id'] : '',
    'default_subject'   => Config::defaultSubject(),
    'default_body'      => Config::defaultBody(),
]);

Html::footer();
