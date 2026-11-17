<?php

use GlpiPlugin\Warrantycolor\Config;
use GlpiPlugin\Warrantycolor\Warranty;

include __DIR__ . '/../../../inc/includes.php';

Session::checkLoginUser();

$type  = (string) ($_GET['itemtype'] ?? '');
$state = (string) ($_GET['state'] ?? '');

if (!in_array($type, Warranty::getItemtypes(), true) || !in_array($state, ['valid', 'warning', 'expired'], true)) {
    throw new \Glpi\Exception\Http\BadRequestHttpException();
}
if (!$type::canView() || !Infocom::canView()) {
    Html::displayRightError();
}

// Recherche native de GLPI : option 120 « Date d'expiration de la garantie » (début + durée) et option 51 « Durée de la garantie ».
// Droits, entités, corbeille et gabarits sont gérés par la recherche elle-même ; la durée > 0 écarte « à vie » (-1) et « non renseignée » (0).
const WC_END = 120;
const WC_DURATION = 51;

$today = new DateTimeImmutable('today');
$warn  = Config::get()['days_before_expiration'];
$day   = static fn(int $offset): string => $today->modify(($offset >= 0 ? '+' : '') . $offset . ' days')->format('Y-m-d');
$crit  = static fn(int $field, string $searchtype, $value, string $link = 'AND'): array => ['link' => $link, 'field' => $field, 'searchtype' => $searchtype, 'value' => $value];
$hasDuration = $crit(WC_DURATION, 'morethan', 0);
const WC_STATE = 31;        // option de recherche « Statut » (états GLPI)

// L'option 120 vaut « début + durée » ; le dernier jour de garantie (affiché par GLPI et par le plugin) est la veille.
$criteria = match ($state) {
    // dernier jour avant aujourd'hui
    'expired' => [$crit(WC_END, 'lessthan', $day(1)), $hasDuration],
    // dernier jour entre aujourd'hui et aujourd'hui + délai d'alerte (bornes incluses)
    'warning' => [$crit(WC_END, 'morethan', $day(0)), $crit(WC_END, 'lessthan', $day($warn + 2)), $hasDuration],
    // dernier jour après le délai d'alerte, ou garantie à vie
    'valid'   => [[
        'link'     => 'AND',
        'criteria' => [
            ['link' => 'AND', 'criteria' => [$crit(WC_END, 'morethan', $day($warn + 1)), $hasDuration]],
            $crit(WC_DURATION, 'equals', -1, 'OR'),
        ],
    ]],
};

foreach (Config::get()['excluded_states'] as $stateId) {       // états exclus dans la configuration (ex. retiré, perdu)
    $criteria[] = $crit(WC_STATE, 'notequals', $stateId);
}

$url = $type::getSearchURL(false);          // les assets personnalisés ont déjà un paramètre (?class=…)
Html::redirect($url . (str_contains($url, '?') ? '&' : '?') . Toolbox::append_params([
    'criteria' => $criteria,
    'reset'    => 'reset',
    'sort'     => WC_END,
    'order'    => 'ASC',
]));
