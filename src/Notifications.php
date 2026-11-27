<?php

namespace GlpiPlugin\Warrantycolor;

use Notification;

/**
 * Gabarits, notifications et destinataires GLPI des mails de fin de garantie.
 * Le texte du mail se règle dans la configuration du plugin : sync() le recopie dans le gabarit GLPI
 * (le gabarit reste visible dans Configuration > Notifications), de même que la copie à l'administrateur de l'entité.
 */
final class Notifications
{
    /** étape => [événement, nom du gabarit, nom de la notification] */
    private const DEFINITIONS = [
        'first'    => [Alert::EVENT_FIRST, 'Warranty Color — fin de garantie', 'Fin de garantie prochaine (Warranty Color)'],
        'reminder' => [Alert::EVENT_REMINDER, 'Warranty Color — rappel de fin de garantie', 'Rappel de fin de garantie (Warranty Color)'],
    ];

    /** Crée (une seule fois) gabarits, notifications et cible « utilisateur », puis aligne le texte et les copies sur la configuration. */
    public static function install(): void
    {
        global $DB;

        $now = date('Y-m-d H:i:s');
        foreach (self::DEFINITIONS as [$event, $templateName, $notificationName]) {
            $templateId = self::templateId($templateName);
            if ($templateId === 0) {
                $DB->insert('glpi_notificationtemplates', ['name' => $templateName, 'itemtype' => Alert::class, 'date_creation' => $now, 'date_mod' => $now]);
                $templateId = (int) $DB->insertId();
            }

            $notificationId = self::notificationId($event);
            if ($notificationId === 0) {
                $DB->insert('glpi_notifications', [
                    'name'           => $notificationName,
                    'entities_id'    => 0,
                    'itemtype'       => Alert::class,
                    'event'          => $event,
                    'is_recursive'   => 1,
                    'is_active'      => 1,
                    'allow_response' => 0,
                    'date_creation'  => $now,
                    'date_mod'       => $now,
                ]);
                $notificationId = (int) $DB->insertId();
            }

            if (!countElementsInTable('glpi_notifications_notificationtemplates', ['notifications_id' => $notificationId, 'mode' => 'mailing'])) {
                $DB->insert('glpi_notifications_notificationtemplates', ['notifications_id' => $notificationId, 'mode' => 'mailing', 'notificationtemplates_id' => $templateId]);
            }
            self::ensureTarget($notificationId, Notification::ITEM_USER, true);
        }

        self::sync();
    }

    public static function uninstall(): void
    {
        global $DB;

        foreach (self::DEFINITIONS as [$event, $templateName]) {
            $notificationId = self::notificationId($event);
            if ($notificationId > 0) {
                $DB->delete('glpi_notificationtargets', ['notifications_id' => $notificationId]);
                $DB->delete('glpi_notifications_notificationtemplates', ['notifications_id' => $notificationId]);
                $DB->delete('glpi_notifications', ['id' => $notificationId]);
            }
            $templateId = self::templateId($templateName);
            if ($templateId > 0) {
                $DB->delete('glpi_notificationtemplatetranslations', ['notificationtemplates_id' => $templateId]);
                $DB->delete('glpi_notificationtemplates', ['id' => $templateId]);
            }
        }
    }

    /** Recopie l'objet, le texte et la copie à l'administrateur de la configuration vers les objets de notification GLPI. */
    public static function sync(): void
    {
        global $DB;

        $conf = Config::get();
        foreach (self::DEFINITIONS as $stage => [$event, $templateName]) {
            $templateId = self::templateId($templateName);
            if ($templateId > 0) {
                $subject = $stage === 'first'
                    ? $conf['notify_subject']
                    : sprintf(__('Rappel : %s', 'warrantycolor'), $conf['notify_subject']);
                $values = [
                    'subject'      => mb_substr($subject, 0, 255),
                    'content_text' => $conf['notify_body'],
                    'content_html' => nl2br(htmlspecialchars($conf['notify_body'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false),
                ];
                $where = ['notificationtemplates_id' => $templateId, 'language' => ''];
                if (countElementsInTable('glpi_notificationtemplatetranslations', $where)) {
                    $DB->update('glpi_notificationtemplatetranslations', $values, $where);
                } else {
                    $DB->insert('glpi_notificationtemplatetranslations', $values + $where);
                }
            }

            $notificationId = self::notificationId($event);
            if ($notificationId > 0) {
                self::ensureTarget($notificationId, Notification::ITEM_USER, true);
                self::ensureTarget($notificationId, Notification::ENTITY_ADMINISTRATOR, (bool) $conf['notify_copy_admin']);
                self::ensureTarget($notificationId, NotificationTargetAlert::TARGET_CC, Config::ccList() !== []);
            }
        }
    }

    private static function templateId(string $name): int
    {
        global $DB;

        $row = $DB->request(['SELECT' => 'id', 'FROM' => 'glpi_notificationtemplates', 'WHERE' => ['name' => $name, 'itemtype' => Alert::class], 'LIMIT' => 1])->current();

        return (int) ($row['id'] ?? 0);
    }

    private static function notificationId(string $event): int
    {
        global $DB;

        $row = $DB->request(['SELECT' => 'id', 'FROM' => 'glpi_notifications', 'WHERE' => ['itemtype' => Alert::class, 'event' => $event], 'LIMIT' => 1])->current();

        return (int) ($row['id'] ?? 0);
    }

    /** Ajoute ou retire une cible utilisateur (type « utilisateur », identifiant de cible GLPI). */
    private static function ensureTarget(int $notificationId, int $target, bool $wanted): void
    {
        global $DB;

        $where = ['notifications_id' => $notificationId, 'type' => Notification::USER_TYPE, 'items_id' => $target];
        $exists = countElementsInTable('glpi_notificationtargets', $where) > 0;
        if ($wanted && !$exists) {
            $DB->insert('glpi_notificationtargets', $where + ['is_exclusion' => 0]);
        } elseif (!$wanted && $exists) {
            $DB->delete('glpi_notificationtargets', $where);
        }
    }
}
