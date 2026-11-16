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
    ];

    public const DAYS_MIN = 1;
    public const DAYS_MAX = 3650;

    /** @return array{days_before_expiration:int,color_valid:string,color_warning:string,full_row:int} */
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

        \Config::setConfigurationValues(self::CONTEXT, self::sanitize([
            'days_before_expiration' => $days,
            'color_valid'            => $data['color_valid'],
            'color_warning'          => $data['color_warning'],
            'full_row'               => !empty($data['full_row']) ? 1 : 0,
        ]));

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
        ];
    }
}
