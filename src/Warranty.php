<?php

namespace GlpiPlugin\Warrantycolor;

/**
 * Calcul de l'état de garantie des équipements (table glpi_infocoms : warranty_date + warranty_duration en mois).
 *
 * États : valid (garantie en cours), warning (expire dans le délai d'alerte), expired, none (non renseignée).
 */
final class Warranty
{
    public const MAX_LINKS = 300;

    /** @var array<string,string>|null motifs d'adresse de fiche par type d'équipement */
    private static ?array $patterns = null;

    /**
     * Types d'équipements gérant les informations financières (natifs et assets personnalisés).
     *
     * @return class-string<\CommonDBTM>[]
     */
    public static function getItemtypes(): array
    {
        global $CFG_GLPI;

        return array_values(array_filter(array_unique($CFG_GLPI['infocom_types'] ?? []), 'class_exists'));
    }

    /**
     * Calcul pur (sans accès base) de l'état d'une garantie.
     *
     * @return array{state:string,until:?string,days:?int}  until : date de fin AAAA-MM-JJ (null = à vie ou inconnue)
     */
    public static function computeState(?string $date, int $duration, \DateTimeImmutable $today, int $warnDays): array
    {
        $none = ['state' => 'none', 'until' => null, 'days' => null];

        // Date complète et plausible uniquement (« 0000-00-00 » ou « 2024-00-00 » seraient « corrigées » silencieusement par PHP).
        if ($date === null || !preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])/', $date) || str_starts_with($date, '0000')) {
            return $none;
        }
        try {
            $start = new \DateTimeImmutable($date);
        } catch (\Exception) {
            return $none;                                   // date corrompue : on ignore la ligne
        }

        if ($duration < 0) {                                // « À vie » dans GLPI
            return ['state' => 'valid', 'until' => null, 'days' => null];
        }
        if ($duration === 0) {                              // pas de durée : garantie non renseignée
            return $none;
        }

        $end   = $start->modify('+' . $duration . ' months');   // même calcul que Infocom::getWarrantyExpir
        $days  = (int) $today->diff($end)->format('%r%a');
        $state = $days < 0 ? 'expired' : ($days <= $warnDays ? 'warning' : 'valid');

        return ['state' => $state, 'until' => $end->format('Y-m-d'), 'days' => $days];
    }

    /** Texte lisible pour une info calculée (info-bulle, bandeau). */
    public static function label(array $info): string
    {
        $until = $info['until'] !== null ? \Html::convDate($info['until']) : '';

        return match ($info['state']) {
            'valid'   => $info['until'] === null
                ? __('Garantie à vie', 'warrantycolor')
                : sprintf(__('Garantie jusqu\'au %1$s (dans %2$d j)', 'warrantycolor'), $until, $info['days']),
            'warning' => sprintf(__('Garantie bientôt expirée : fin le %1$s (dans %2$d j)', 'warrantycolor'), $until, $info['days']),
            'expired' => sprintf(__('Garantie expirée le %s', 'warrantycolor'), $until),
            default   => __('Garantie non renseignée', 'warrantycolor'),
        };
    }

    /**
     * État de garantie d'un équipement donné (fiche) ou null si l'utilisateur n'a pas le droit de le savoir.
     *
     * @return array{state:string,until:?string,days:?int,label:string}|null
     */
    public static function forItem(string $itemtype, int $id): ?array
    {
        if (!\Infocom::canView()) {
            return null;
        }
        $rows = self::infocoms($itemtype, [$id]);
        $conf = Config::get();
        $info = self::computeState($rows[$id]['warranty_date'] ?? null, (int) ($rows[$id]['warranty_duration'] ?? 0), new \DateTimeImmutable('today'), $conf['days_before_expiration']);
        $info['label'] = self::label($info);

        return $info;
    }

    /**
     * États pour une liste d'adresses de fiches (« /front/computer.form.php?id=12 »), une requête par type d'équipement.
     * Seuls les équipements que l'utilisateur a le droit de voir (type + entité) sont renvoyés.
     *
     * @param string[] $hrefs
     * @return array<string,array{state:string,color:?string,until:?string,days:?int,label:string}> adresse => état
     */
    public static function forLinks(array $hrefs): array
    {
        if (!\Infocom::canView()) {
            return [];
        }

        $byType = [];                                       // type => [id => [adresses]]
        foreach (array_slice($hrefs, 0, self::MAX_LINKS) as $href) {
            $resolved = self::resolveLink($href);
            if ($resolved !== null) {
                $byType[$resolved[0]][$resolved[1]][] = $href;
            }
        }

        $conf  = Config::get();
        $today = new \DateTimeImmutable('today');
        $out   = [];
        foreach ($byType as $type => $byId) {
            if (!$type::canView()) {
                continue;
            }
            $ids     = self::visibleIds($type, array_keys($byId));
            $infocom = self::infocoms($type, $ids);
            foreach ($ids as $id) {
                $info = self::computeState($infocom[$id]['warranty_date'] ?? null, (int) ($infocom[$id]['warranty_duration'] ?? 0), $today, $conf['days_before_expiration']);
                if ($info['state'] === 'none') {
                    continue;
                }
                $info['color'] = match ($info['state']) {
                    'valid'   => $conf['color_valid'],
                    'warning' => $conf['color_warning'],
                    default   => null,
                };
                $info['label'] = self::label($info);
                foreach ($byId[$id] as $href) {
                    $out[$href] = $info;
                }
            }
        }

        return $out;
    }

    /**
     * Retrouve (type, id) à partir d'une adresse de fiche, quel que soit le préfixe d'installation (root_doc).
     *
     * @return array{0:string,1:int}|null
     */
    public static function resolveLink(string $href): ?array
    {
        global $CFG_GLPI;

        if (self::$patterns === null) {
            self::$patterns = [];
            foreach (self::getItemtypes() as $type) {
                $sample = $type::getFormURLWithID(987654321, false);
                self::$patterns[$type] = '#^' . str_replace('987654321', '(\d+)', preg_quote($sample, '#')) . '(?:&|$)#';
            }
        }

        $href = preg_replace('#^https?://[^/]+#', '', $href);
        $root = $CFG_GLPI['root_doc'] ?? '';
        if ($root !== '' && str_starts_with($href, $root . '/')) {
            $href = substr($href, strlen($root));
        }
        foreach (self::$patterns as $type => $re) {
            if (preg_match($re, $href, $m)) {
                return [$type, (int) $m[1]];
            }
        }

        return null;
    }

    /**
     * Identifiants visibles par l'utilisateur (entités actives) parmi ceux demandés.
     *
     * @param int[] $ids
     * @return int[]
     */
    private static function visibleIds(string $type, array $ids): array
    {
        global $DB;

        $table = $type::getTable();
        $where = ['id' => $ids];
        if ($DB->fieldExists($table, 'entities_id')) {
            $where += getEntitiesRestrictCriteria($table);
        }

        return array_map('intval', array_column(iterator_to_array($DB->request(['SELECT' => 'id', 'FROM' => $table, 'WHERE' => $where])), 'id'));
    }

    /**
     * @param int[] $ids
     * @return array<int,array{warranty_date:?string,warranty_duration:int}> items_id => ligne
     */
    private static function infocoms(string $type, array $ids): array
    {
        global $DB;

        if (!$ids) {
            return [];
        }
        $out = [];
        foreach ($DB->request(['SELECT' => ['items_id', 'warranty_date', 'warranty_duration'], 'FROM' => 'glpi_infocoms', 'WHERE' => ['itemtype' => $type, 'items_id' => $ids]]) as $row) {
            $out[(int) $row['items_id']] = $row;
        }

        return $out;
    }
}
