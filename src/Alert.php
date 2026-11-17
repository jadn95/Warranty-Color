<?php

namespace GlpiPlugin\Warrantycolor;

use CommonDBTM;
use CronTask;
use NotificationEvent;
use User;
use UserEmail;

/**
 * Mail automatique à l'utilisateur d'un équipement dont la garantie arrive à échéance.
 *
 * Chaque ligne de la table glpi_plugin_warrantycolor_alerts garde la trace d'un envoi (ou d'un marquage « déjà notifié ») pour
 * un équipement, un dernier jour de garantie et une étape (mail initial ou rappel) : on n'envoie qu'une fois par période de
 * garantie, et une garantie prolongée redéclenche un mail. L'objet sert aussi de support à la notification GLPI native
 * (gabarits modifiables, file d'attente, langue de l'utilisateur) : voir NotificationTargetAlert.
 */
class Alert extends CommonDBTM
{
    public const TABLE       = 'glpi_plugin_warrantycolor_alerts';
    public const CRON        = 'WarrantyAlerts';
    public const MAX_PER_RUN = 200;           // limite d'envois par exécution : le reste part le lendemain

    public const EVENT_FIRST    = 'warranty_end';
    public const EVENT_REMINDER = 'warranty_end_reminder';

    public static string $rightname = 'config';
    public bool $dohistory   = false;

    public static function getTable($classname = null): string
    {
        return self::TABLE;
    }

    public static function getTypeName($nb = 0): string
    {
        return __('Mail de fin de garantie', 'warrantycolor');
    }

    public static function cronInfo($name): array
    {
        return ['description' => __('Envoie aux utilisateurs un mail avant la fin de garantie de leur équipement', 'warrantycolor')];
    }

    /** Notifications GLPI actives (réglage global) : sans elles, rien ne peut partir. */
    public static function notificationsReady(): bool
    {
        global $CFG_GLPI;

        return !empty($CFG_GLPI['use_notifications']) && !empty($CFG_GLPI['notifications_mailing']);
    }

    /** Action automatique quotidienne (Configuration > Actions automatiques). */
    public static function cronWarrantyAlerts(CronTask $task): int
    {
        $conf = Config::get();
        if (!$conf['notify_enabled']) {
            $task->log(__('Mails automatiques désactivés dans la configuration du plugin.', 'warrantycolor'));

            return 0;
        }
        $simulation = (bool) $conf['notify_simulation'];
        if (!$simulation && !self::notificationsReady()) {
            $task->log(__('Notifications GLPI désactivées : rien n\'est envoyé.', 'warrantycolor'));

            return 0;
        }

        if (!$simulation) {
            Notifications::sync();      // gabarit et destinataires GLPI alignés sur la configuration avant tout envoi réel
        }
        $report = self::run(!$simulation);
        $task->setVolume($report['sent']);
        $task->log($simulation
            ? sprintf(__('Simulation : %1$d mail(s) seraient envoyés, %2$d équipement(s) ignoré(s).', 'warrantycolor'), count($report['due']), count($report['ignored']))
            : sprintf(
                __('%1$d mail(s) envoyé(s), %2$d équipement(s) ignoré(s), %3$d erreur(s)%4$s.', 'warrantycolor'),
                $report['sent'],
                count($report['ignored']),
                $report['errors'],
                $report['capped'] ? __(' ; limite par exécution atteinte, suite demain', 'warrantycolor') : ''
            ));

        return ($report['sent'] > 0 || ($simulation && $report['due'])) ? 1 : 0;
    }

    /**
     * Repère les équipements concernés et, selon le mode, envoie les mails ou marque l'existant comme déjà notifié.
     *
     * @param bool $send     true : envoie les mails ; false : simulation (rien n'est écrit ni envoyé)
     * @param bool $baseline true : marque les équipements concernés comme déjà notifiés, sans envoyer
     * @return array{due:array<int,array<string,mixed>>,ignored:array<int,array<string,mixed>>,sent:int,marked:int,errors:int,capped:bool}
     */
    public static function run(bool $send, bool $baseline = false): array
    {
        global $DB;

        $conf     = Config::get();
        $today    = new \DateTimeImmutable('today');
        $days     = (int) $conf['days_before_expiration'];
        $reportN  = Config::reportDays($days);
        $out      = ['due' => [], 'ignored' => [], 'sent' => 0, 'marked' => 0, 'errors' => 0, 'capped' => false];
        $users    = [];

        foreach (Warranty::getItemtypes() as $type) {
            $table = $type::getTable();
            if (!$DB->tableExists($table) || !$DB->fieldExists($table, 'users_id')) {
                continue;
            }

            // Équipements de ce type dont la garantie arrive à échéance dans le délai d'alerte.
            $soon = [];
            foreach ($DB->request(['SELECT' => ['items_id', 'warranty_date', 'warranty_duration'], 'FROM' => 'glpi_infocoms', 'WHERE' => ['itemtype' => $type, ['NOT' => ['warranty_date' => null]], 'warranty_duration' => ['>', 0]]]) as $row) {
                $info = Warranty::computeState($row['warranty_date'], (int) $row['warranty_duration'], $today, $days);
                if ($info['state'] === 'warning') {
                    $soon[(int) $row['items_id']] = $info;
                }
            }

            foreach (array_chunk(array_keys($soon), 500) as $chunk) {
                $where = ['id' => $chunk, 'users_id' => ['>', 0]];
                foreach (['is_deleted', 'is_template'] as $flag) {
                    if ($DB->fieldExists($table, $flag)) {
                        $where[$flag] = 0;
                    }
                }
                if ($conf['excluded_states'] && $DB->fieldExists($table, 'states_id')) {
                    $where[] = ['NOT' => ['states_id' => $conf['excluded_states']]];
                }
                $select = ['id', 'name', 'users_id', 'entities_id'];
                if ($DB->fieldExists($table, 'serial')) {
                    $select[] = 'serial';
                }

                $done = [];                // items_id => dernier jour => étape => mode
                foreach ($DB->request(['SELECT' => ['items_id', 'warranty_end', 'stage', 'mode'], 'FROM' => self::TABLE, 'WHERE' => ['itemtype' => $type, 'items_id' => $chunk]]) as $row) {
                    $done[(int) $row['items_id']][$row['warranty_end']][$row['stage']] = $row['mode'];
                }

                foreach ($DB->request(['SELECT' => $select, 'FROM' => $table, 'WHERE' => $where]) as $item) {
                    $id    = (int) $item['id'];
                    $info  = $soon[$id];
                    $until = (string) $info['until'];
                    $left  = (int) $info['days'];
                    $have  = $done[$id][$until] ?? [];

                    $stage = null;
                    if (!isset($have['first'])) {
                        $stage = 'first';
                    } elseif ($conf['notify_reminder'] && $left <= $reportN && !isset($have['reminder'])) {
                        $stage = 'reminder';
                    }
                    if ($stage === null) {
                        continue;
                    }

                    $user = $users[$item['users_id']] ??= self::loadUser((int) $item['users_id']);
                    $reportBy = max($today, (new \DateTimeImmutable($until))->modify('-' . $reportN . ' days'));
                    $entry = [
                        'itemtype'    => $type,
                        'items_id'    => $id,
                        'entities_id' => (int) $item['entities_id'],
                        'users_id'    => (int) $item['users_id'],
                        'name'        => (string) $item['name'],
                        'user'        => $user['name'],
                        'email'       => $user['email'],
                        'until'       => $until,
                        'days'        => $left,
                        'stage'       => $stage,
                        'tags'        => [
                            'type'        => (string) $type::getTypeName(1),
                            'name'        => (string) $item['name'],
                            'serial'      => (string) ($item['serial'] ?? ''),
                            'user_name'   => $user['name'],
                            'firstname'   => $user['firstname'],
                            'end'         => \Html::convDate($until),
                            'days'        => $left,
                            'report_by'   => \Html::convDate($reportBy->format('Y-m-d')),
                            'report_days' => (int) $reportBy->diff(new \DateTimeImmutable($until))->format('%a'),
                        ],
                    ];

                    if (!$user['ok']) {
                        $out['ignored'][] = $entry + ['reason' => $user['reason']];
                        continue;
                    }
                    $out['due'][] = $entry;

                    if ($baseline) {
                        self::record($entry, 'first', 'baseline');
                        if ($left <= $reportN) {
                            self::record($entry, 'reminder', 'baseline');
                        }
                        ++$out['marked'];
                    } elseif ($send) {
                        if ($out['sent'] >= self::MAX_PER_RUN) {
                            $out['capped'] = true;
                        } elseif (self::deliver($entry, $conf['notify_reminder'] && $left <= $reportN)) {
                            ++$out['sent'];
                        } else {
                            ++$out['errors'];
                        }
                    }
                }
            }
        }

        return $out;
    }

    /** Dernier passage de l'action automatique (date et messages), ou null si elle n'est pas enregistrée. */
    public static function lastRun(): ?array
    {
        global $DB;

        $task = new CronTask();
        if (!$task->getFromDBbyName(self::class, self::CRON)) {
            return null;
        }
        $messages = [];
        foreach ($DB->request(['SELECT' => ['date', 'content'], 'FROM' => 'glpi_crontasklogs', 'WHERE' => ['crontasks_id' => $task->fields['id'], ['NOT' => ['content' => null]]], 'ORDER' => 'id DESC', 'LIMIT' => 3]) as $row) {
            $messages[] = ['date' => $row['date'], 'content' => (string) $row['content']];
        }

        return ['id' => (int) $task->fields['id'], 'lastrun' => $task->fields['lastrun'], 'state' => (int) $task->fields['state'], 'messages' => $messages];
    }

    /** @return array{ok:bool,reason:string,name:string,firstname:string,email:string} */
    private static function loadUser(int $id): array
    {
        $user = new User();
        if (!$user->getFromDB($id)) {
            return ['ok' => false, 'reason' => __('utilisateur introuvable', 'warrantycolor'), 'name' => '', 'firstname' => '', 'email' => ''];
        }
        $first = (string) $user->fields['firstname'];
        $name  = trim($first . ' ' . $user->fields['realname']) ?: (string) $user->fields['name'];
        $email = (string) UserEmail::getDefaultForUser($id);

        $reason = '';
        if (!$user->fields['is_active'] || $user->fields['is_deleted']) {
            $reason = __('utilisateur inactif', 'warrantycolor');
        } elseif ($email === '') {
            $reason = __('aucune adresse e-mail', 'warrantycolor');
        }

        return ['ok' => $reason === '', 'reason' => $reason, 'name' => $name, 'firstname' => $first !== '' ? $first : $name, 'email' => $email];
    }

    /** Enregistre une étape (envoi, marquage ou saut) ; false si elle existe déjà (exécution parallèle). */
    private static function record(array $e, string $stage, string $mode): int|false
    {
        global $DB;

        try {
            $DB->insert(self::TABLE, [
                'entities_id'   => $e['entities_id'],
                'itemtype'      => $e['itemtype'],
                'items_id'      => $e['items_id'],
                'users_id'      => $e['users_id'],
                'warranty_end'  => $e['until'],
                'stage'         => $stage,
                'mode'          => $mode,
                'date_creation' => date('Y-m-d H:i:s'),
            ]);

            return (int) $DB->insertId();
        } catch (\Throwable) {
            return false;
        }
    }

    private static function deliver(array $e, bool $reminderAlsoDue): bool
    {
        $id = self::record($e, $e['stage'], 'sent');
        if ($id === false) {
            return false;                       // déjà envoyé (exécution parallèle)
        }
        $alert = new self();
        if (!$alert->getFromDB($id)) {
            return false;
        }
        NotificationEvent::raiseEvent(
            $e['stage'] === 'first' ? self::EVENT_FIRST : self::EVENT_REMINDER,
            $alert,
            ['entities_id' => $e['entities_id'], 'warranty' => $e['tags']]
        );
        if ($e['stage'] === 'first' && $reminderAlsoDue) {
            self::record($e, 'reminder', 'skipped');        // déjà dans la fenêtre du rappel : pas de second mail le lendemain
        }

        return true;
    }
}
