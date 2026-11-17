# GLPI Warranty Color

Plugin GLPI (11 et 12) qui repère l'état de garantie des équipements directement dans les listes et sur leur fiche.

| État | Affichage |
| --- | --- |
| Garantie valide (ou « à vie ») | pastille verte + repère latéral + teinte de ligne (option) |
| Expire dans N jours ou moins (30 par défaut) | pastille orange + repère latéral + teinte de ligne (option) |
| Expirée | pastille grise discrète, ligne normale |
| Non renseignée | rien |

L'information ne repose pas sur la couleur seule : chaque pastille porte une **icône** et une **info-bulle** (« Garantie jusqu'au 17-07-2028 (dans 609 j) »), et une **légende** s'affiche au-dessus des listes : **cliquez sur un état** (par exemple « Expire dans 30 jours ou moins ») pour lister tous les équipements concernés, sur toutes les pages. Sur la fiche d'un équipement, un bandeau rappelle l'état.

Sur les fiches de ticket, problème et changement, la même pastille apparaît à côté de chaque équipement associé.

Fonctionne avec les équipements natifs et les assets personnalisés ayant la capacité « Gestion financière ».

## Installation

```bash
cp -r warrantycolor /var/www/glpi/plugins/        # ou marketplace/
chown -R www-data:www-data /var/www/glpi/plugins/warrantycolor
```

Configuration > Plugins > Warranty Color > Installer, puis Activer (ou `php bin/console plugin:install warrantycolor -u <admin>` puis `plugin:activate warrantycolor`).

Mise à jour depuis la 1.x : la configuration (table propre au plugin) est reprise automatiquement dans la configuration native de GLPI, puis la table est supprimée.

## Configuration

Configuration > Plugins > Warranty Color : délai d'alerte (1 à 3650 jours), couleurs « valide » et « expiration proche », teinte de toute la ligne ou pastille seule, **états à ne pas signaler** (par exemple « Perdu » ou « Retiré »), aperçu en direct, bouton « Valeurs par défaut ». Réservée aux utilisateurs ayant le droit de configuration.

## Fonctionnement et sécurité

GLPI n'offre pas de crochet pour colorer les lignes d'une liste. Un script (`public/js/warrantycolor.js`) repère les liens de fiche des tableaux de résultats et interroge `front/status.php` par lots de 40 adresses. Côté serveur, une seule requête par type d'équipement ; seuls les équipements que l'utilisateur a le droit de voir (droit sur le type, sur les informations financières, et entités actives) sont renvoyés.

Calcul : date de début + durée en mois (comme `Infocom::getWarrantyExpir`). Durée −1 = à vie ; durée 0 ou date absente/invalide = non renseignée.

## Développement

- `src/` : espace de noms `GlpiPlugin\Warrantycolor` (`Warranty` : calcul pur et accès base, `Config`, `Migration`).
- `php tests/compute_test.php` : tests du calcul d'état (sans base).
- Le clic sur la légende lance une recherche GLPI native (option « Date d'expiration de la garantie ») qui remplace les filtres de la liste ; « Réinitialiser » dans la recherche GLPI revient à la liste complète.
- Textes en français encapsulés dans `__('…', 'warrantycolor')` : prêts pour des fichiers `locales/*.po`.
- Pas de borne de version maximale : à revalider à chaque version majeure de GLPI.

Licence GPLv3 — auteur : Andy Tkaczyk.
