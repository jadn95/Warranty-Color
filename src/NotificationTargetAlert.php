<?php

namespace GlpiPlugin\Warrantycolor;

use Dropdown;
use Notification;
use NotificationTarget;

/**
 * Cible de notification des mails de fin de garantie (nom imposé par GLPI : NotificationTarget + nom de la classe Alert).
 * Destinataires : l'utilisateur de l'équipement (cible native « Utilisateur de l'équipement », champ users_id de l'alerte)
 * et, si la configuration le demande, l'administrateur de l'entité (service informatique) et des adresses de copie saisies.
 */
class NotificationTargetAlert extends NotificationTarget
{
    /** Identifiant de la cible « adresses en copie de la configuration » (hors des identifiants natifs de GLPI). */
    public const TARGET_CC = 9101;

    public function getEvents()
    {
        return [
            Alert::EVENT_FIRST    => __('Fin de garantie prochaine', 'warrantycolor'),
            Alert::EVENT_REMINDER => __('Rappel de fin de garantie', 'warrantycolor'),
        ];
    }

    public function addNotificationTargets($entity)
    {
        $this->addTarget(Notification::ITEM_USER, __('Utilisateur de l\'équipement', 'warrantycolor'));
        $this->addTarget(Notification::ENTITY_ADMINISTRATOR, __('Entity administrator'));
        $this->addTarget(self::TARGET_CC, __('Adresses en copie (configuration du plugin)', 'warrantycolor'));
    }

    public function addSpecificTargets($data, $options)
    {
        global $CFG_GLPI;

        if ((int) ($data['items_id'] ?? 0) !== self::TARGET_CC || !$this->isMailMode()) {
            return;
        }
        foreach (Config::ccList() as $address) {
            $this->addToRecipientsList(['email' => $address, 'name' => $address, 'language' => $CFG_GLPI['language'] ?? '']);
        }
    }

    public function addDataForTemplate($event, $options = [])
    {
        $w = $options['warranty'] ?? [];

        $this->data['##item.type##']          = (string) ($w['type'] ?? '');
        $this->data['##item.name##']          = (string) ($w['name'] ?? '');
        $this->data['##item.serial##']        = (string) ($w['serial'] ?? '');
        $this->data['##user.name##']          = (string) ($w['user_name'] ?? '');
        $this->data['##user.firstname##']     = (string) ($w['firstname'] ?? '');
        $this->data['##warranty.end##']       = (string) ($w['end'] ?? '');
        $this->data['##warranty.days##']      = (string) ($w['days'] ?? '');
        $this->data['##warranty.report_by##'] = (string) ($w['report_by'] ?? '');
        $this->data['##warranty.report_days##'] = (string) ($w['report_days'] ?? '');
        $this->data['##entity.name##']        = Dropdown::getDropdownName('glpi_entities', (int) ($options['entities_id'] ?? 0));

        $this->getTags();
        foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
            if (!isset($this->data[$tag])) {
                $this->data[$tag] = $values['label'];
            }
        }
    }

    /** Balises proposées dans les gabarits de notification (et documentées dans la configuration du plugin). */
    public static function tagLabels(): array
    {
        return [
            'item.type'          => __('Type d\'équipement', 'warrantycolor'),
            'item.name'          => __('Nom de l\'équipement', 'warrantycolor'),
            'item.serial'        => __('Numéro de série', 'warrantycolor'),
            'user.name'          => __('Nom complet de l\'utilisateur', 'warrantycolor'),
            'user.firstname'     => __('Prénom de l\'utilisateur', 'warrantycolor'),
            'warranty.end'       => __('Dernier jour de garantie', 'warrantycolor'),
            'warranty.days'      => __('Jours restants avant la fin', 'warrantycolor'),
            'warranty.report_by' => __('Date limite de signalement d\'un problème', 'warrantycolor'),
            'warranty.report_days' => __('Nombre de jours entre cette date limite et la fin', 'warrantycolor'),
            'entity.name'        => __('Entité', 'warrantycolor'),
        ];
    }

    public function getTags()
    {
        foreach (self::tagLabels() as $tag => $label) {
            $this->addTagToList(['tag' => $tag, 'label' => $label, 'value' => true]);
        }
        asort($this->tag_descriptions);
    }
}
