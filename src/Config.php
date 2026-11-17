<?php

namespace GlpiPlugin\Warrantycolor;

/**
 * Configuration du plugin, stockée dans la table native glpi_configs (contexte « plugin:warrantycolor »).
 */
final class Config
{
    public const CONTEXT = 'plugin:warrantycolor';

    public const DEFAULTS = [
        'days_before_expiration' => 30,
        'color_valid'            => '#2ecc71',
        'color_warning'          => '#ff9900',
        'full_row'               => 1,
        'excluded_states'        => '',          // ids d'états GLPI (séparés par des virgules) : équipements non signalés
        // Mails automatiques à l'utilisateur de l'équipement
        'notify_enabled'         => 0,
        'notify_simulation'      => 1,           // simulation : rien n'est envoyé (réglage par défaut, par prudence)
        'notify_copy_admin'      => 0,           // copie à l'administrateur de l'entité (service informatique)
        'notify_reminder'        => 0,           // second mail à la moitié du délai
        'notify_cc'              => '',          // adresses supplémentaires en copie (séparées par des virgules)
        'notify_subject'         => '',          // vide = texte par défaut (traduit)
        'notify_body'            => '',
    ];

    public const DAYS_MIN = 1;
    public const DAYS_MAX = 3650;
    public const SUBJECT_MAX = 200;
    public const BODY_MAX = 5000;
    public const CC_MAX = 10;

    /** @return array{days_before_expiration:int,color_valid:string,color_warning:string,full_row:int,excluded_states:int[],notify_enabled:int,notify_simulation:int,notify_copy_admin:int,notify_reminder:int,notify_cc:string,notify_subject:string,notify_body:string} */
    public static function get(): array
    {
        try {
            $stored = \Config::getConfigurationValues(self::CONTEXT);
        } catch (\Throwable) {
            $stored = [];
        }
        $conf = self::sanitize($stored + self::DEFAULTS);
        $conf['notify_subject'] = $conf['notify_subject'] !== '' ? $conf['notify_subject'] : self::defaultSubject();
        $conf['notify_body']    = $conf['notify_body'] !== '' ? $conf['notify_body'] : self::defaultBody();

        return $conf;
    }

    /** Délai (jours avant la fin) après lequel un problème ne peut plus être pris en charge : la moitié du délai d'alerte. */
    public static function reportDays(int $alertDays): int
    {
        return max(1, intdiv($alertDays, 2));
    }

    /**
     * Découpe une saisie d'adresses (virgule, point-virgule, espace ou retour à la ligne).
     *
     * @return array{valid:string[],invalid:string[]}
     */
    public static function parseAddresses(string $raw): array
    {
        $valid = $invalid = [];
        foreach (preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $address) {
            if (filter_var($address, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($address) <= 254) {
                $valid[strtolower($address)] = strtolower($address);
            } else {
                $invalid[] = $address;
            }
        }

        return ['valid' => array_values($valid), 'invalid' => $invalid];
    }

    /** @return string[] adresses en copie, validées */
    public static function ccList(): array
    {
        return self::parseAddresses(self::get()['notify_cc'])['valid'];
    }

    public static function defaultSubject(): string
    {
        return __('Fin de garantie de votre ##item.type## ##item.name##', 'warrantycolor');
    }

    public static function defaultBody(): string
    {
        return __("Bonjour ##user.firstname##,\n\nVotre ##item.type## « ##item.name## » (n° de série : ##item.serial##) arrive en fin de garantie le ##warranty.end##, dans ##warranty.days## jours.\n\nSi vous constatez un problème sur ce matériel (panne, dysfonctionnement, casse), merci de le signaler au service informatique avant le ##warranty.report_by## (##warranty.report_days## jours avant la fin de garantie), afin que nous puissions faire jouer l'assurance.\n\nCordialement,\nLe service informatique", 'warrantycolor');
    }

    /**
     * @param array<string,mixed> $data
     * @return string[] erreurs de validation (vide = enregistré)
     */
    public static function save(array $data): array
    {
        $errors = [];
        $days = filter_var($data['days_before_expiration'] ?? null, FILTER_VALIDATE_INT);
        if ($days === false || $days < self::DAYS_MIN || $days > self::DAYS_MAX) {
            $errors[] = sprintf(__('Le délai d\'alerte doit être compris entre %1$d et %2$d jours.', 'warrantycolor'), self::DAYS_MIN, self::DAYS_MAX);
        }
        foreach (['color_valid' => __('Garantie valide', 'warrantycolor'), 'color_warning' => __('Expiration proche', 'warrantycolor')] as $key => $label) {
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($data[$key] ?? ''))) {
                $errors[] = sprintf(__('Couleur invalide : %s', 'warrantycolor'), $label);
            }
        }
        // Mails automatiques : traités seulement si le formulaire les contient (présence de l'objet du mail).
        $withNotify = array_key_exists('notify_subject', $data);
        if ($withNotify) {
            $subject = trim((string) $data['notify_subject']);
            $body    = trim(str_replace("\r\n", "\n", (string) ($data['notify_body'] ?? '')));
            $cc = self::parseAddresses((string) ($data['notify_cc'] ?? ''));
            if ($cc['invalid']) {
                $errors[] = sprintf(__('Adresse(s) de copie invalide(s) : %s', 'warrantycolor'), implode(', ', $cc['invalid']));
            }
            if (count($cc['valid']) > self::CC_MAX) {
                $errors[] = sprintf(__('Au plus %d adresses en copie.', 'warrantycolor'), self::CC_MAX);
            }
            if (mb_strlen($subject) > self::SUBJECT_MAX) {
                $errors[] = sprintf(__('L\'objet du mail est limité à %d caractères.', 'warrantycolor'), self::SUBJECT_MAX);
            }
            if (mb_strlen($body) > self::BODY_MAX) {
                $errors[] = sprintf(__('Le texte du mail est limité à %d caractères.', 'warrantycolor'), self::BODY_MAX);
            }
        }
        if ($errors) {
            return $errors;
        }

        $new = [
            'days_before_expiration' => $days,
            'color_valid'            => $data['color_valid'],
            'color_warning'          => $data['color_warning'],
            'full_row'               => !empty($data['full_row']) ? 1 : 0,
            'excluded_states'        => $data['excluded_states'] ?? [],
        ];
        $stored = \Config::getConfigurationValues(self::CONTEXT) + self::DEFAULTS;
        if ($withNotify) {
            // Une case absente de la requête garde sa valeur enregistrée (jamais de passage involontaire en envoi réel).
            $flag = static fn(string $key): int => array_key_exists($key, $data) ? (!empty($data[$key]) ? 1 : 0) : (int) $stored[$key];
            $new += [
                'notify_enabled'    => $flag('notify_enabled'),
                'notify_simulation' => $flag('notify_simulation'),
                'notify_copy_admin' => $flag('notify_copy_admin'),
                'notify_reminder'   => $flag('notify_reminder'),
                'notify_cc'         => implode(',', $cc['valid']),
                // un texte identique au texte par défaut est stocké vide : il suivra la langue et les évolutions du plugin
                'notify_subject'    => $subject === self::defaultSubject() ? '' : $subject,
                'notify_body'       => $body === self::defaultBody() ? '' : $body,
            ];
        }
        $clean = self::sanitize($new + $stored);
        $clean['excluded_states'] = implode(',', $clean['excluded_states']);      // glpi_configs ne stocke que du texte
        \Config::setConfigurationValues(self::CONTEXT, $clean);

        if ($withNotify) {
            Notifications::sync();          // gabarits et destinataires GLPI alignés sur la configuration
        }

        return [];
    }

    /** @param array<string,mixed> $v */
    private static function sanitize(array $v): array
    {
        $color = static fn($c, string $default): string => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $c) ? strtolower((string) $c) : $default;

        return [
            'days_before_expiration' => max(self::DAYS_MIN, min(self::DAYS_MAX, (int) $v['days_before_expiration'])),
            'color_valid'            => $color($v['color_valid'], self::DEFAULTS['color_valid']),
            'color_warning'          => $color($v['color_warning'], self::DEFAULTS['color_warning']),
            'full_row'               => (int) (bool) $v['full_row'],
            'excluded_states'        => self::ids($v['excluded_states'] ?? ''),
            'notify_enabled'         => (int) (bool) ($v['notify_enabled'] ?? 0),
            'notify_simulation'      => (int) (bool) ($v['notify_simulation'] ?? 1),
            'notify_copy_admin'      => (int) (bool) ($v['notify_copy_admin'] ?? 0),
            'notify_reminder'        => (int) (bool) ($v['notify_reminder'] ?? 0),
            'notify_cc'              => mb_substr((string) ($v['notify_cc'] ?? ''), 0, 3000),
            'notify_subject'         => mb_substr((string) ($v['notify_subject'] ?? ''), 0, self::SUBJECT_MAX),
            'notify_body'            => mb_substr((string) ($v['notify_body'] ?? ''), 0, self::BODY_MAX),
        ];
    }

    /** @return int[] identifiants entiers positifs, sans doublon (liste ou texte « 1,2 ») */
    private static function ids(mixed $v): array
    {
        $list = is_array($v) ? $v : explode(',', (string) $v);

        return array_values(array_unique(array_filter(array_map('intval', $list), static fn(int $i): bool => $i > 0)));
    }
}
