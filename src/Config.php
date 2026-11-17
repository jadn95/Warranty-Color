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
    ];

    public const DAYS_MIN = 1;
    public const DAYS_MAX = 3650;

    /** @return array{days_before_expiration:int,color_valid:string,color_warning:string,full_row:int,excluded_states:int[]} */
    public static function get(): array
    {
        try {
            $stored = \Config::getConfigurationValues(self::CONTEXT);
        } catch (\Throwable) {
            $stored = [];
        }

        return self::sanitize($stored + self::DEFAULTS);
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
        if ($errors) {
            return $errors;
        }

        $clean = self::sanitize([
            'days_before_expiration' => $days,
            'color_valid'            => $data['color_valid'],
            'color_warning'          => $data['color_warning'],
            'full_row'               => !empty($data['full_row']) ? 1 : 0,
            'excluded_states'        => $data['excluded_states'] ?? [],
        ]);
        $clean['excluded_states'] = implode(',', $clean['excluded_states']);      // glpi_configs ne stocke que du texte
        \Config::setConfigurationValues(self::CONTEXT, $clean);

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
        ];
    }

    /** @return int[] identifiants entiers positifs, sans doublon (liste ou texte « 1,2 ») */
    private static function ids(mixed $v): array
    {
        $list = is_array($v) ? $v : explode(',', (string) $v);

        return array_values(array_unique(array_filter(array_map('intval', $list), static fn(int $i): bool => $i > 0)));
    }
}
