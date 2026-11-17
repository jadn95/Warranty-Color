# GLPI Warranty Color

Plugin GLPI (12.0) qui repère l'état de garantie des équipements directement dans les listes et sur leur fiche.

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

## Mails automatiques (onglet « Mails automatiques »)

Chaque jour, l'action automatique `WarrantyAlerts` (Configuration > Actions automatiques) repère les équipements dont la garantie arrive à échéance dans le délai d'alerte et envoie à l'**utilisateur affecté** un mail lui demandant de signaler tout problème avant la moitié du délai, pour que le service informatique puisse faire jouer l'assurance.

- **Prérequis :** notifications GLPI activées (Configuration > Notifications, mode e-mail) et SMTP configuré. Pour la copie au service informatique (ou à d'autres adresses saisies dans la configuration, 10 au plus), renseigner l'adresse de l'administrateur de l'entité (Administration > Entités > Notifications).
- **Mise en route prudente :** le mode **simulation** est activé par défaut (rien n'est envoyé, la configuration affiche ce qui partirait). Avant la première activation, cliquer sur « Marquer les équipements concernés comme déjà notifiés » pour éviter un envoi massif, puis décocher la simulation.
- **Règles :** un mail par équipement et par période de garantie (une garantie prolongée redéclenche un mail) ; rappel optionnel à la moitié du délai ; 200 mails au maximum par passage ; équipements sans utilisateur actif ou sans adresse e-mail ignorés (listés dans la simulation) ; états exclus respectés ; équipements à la corbeille ou gabarits écartés.
- **Texte :** objet et texte modifiables dans la configuration (balises `##item.type##`, `##item.name##`, `##item.serial##`, `##user.firstname##`, `##warranty.end##`, `##warranty.days##`, `##warranty.report_by##`, `##warranty.report_days##`…) ; ils sont recopiés dans les gabarits de notification GLPI (visibles dans Configuration > Notifications). Les mails passent par la file d'attente de GLPI.

## Fonctionnement et sécurité

GLPI n'offre pas de crochet pour colorer les lignes d'une liste. Un script (`public/js/warrantycolor.js`) repère les liens de fiche des tableaux de résultats et interroge `front/status.php` par lots de 40 adresses. Côté serveur, une seule requête par type d'équipement ; seuls les équipements que l'utilisateur a le droit de voir (droit sur le type, sur les informations financières, et entités actives) sont renvoyés.

Calcul : dernier jour de garantie = date de début + durée en mois − 1 jour (la « date d'expiration » affichée par la fiche GLPI) ; expirée à partir du lendemain. Durée −1 = à vie ; durée 0 ou date absente/invalide = non renseignée.

## Développement

- `src/` : espace de noms `GlpiPlugin\Warrantycolor` (`Warranty` : calcul pur et accès base, `Config`, `Migration`).
- `php tests/compute_test.php` : tests du calcul d'état (sans base).
- Le clic sur la légende lance une recherche GLPI native (option « Date d'expiration de la garantie ») qui remplace les filtres de la liste ; « Réinitialiser » dans la recherche GLPI revient à la liste complète.
- Textes en français encapsulés dans `__('…', 'warrantycolor')` ; `locales/warrantycolor.pot` sert de modèle pour une nouvelle langue (`msginit -l en_GB -i locales/warrantycolor.pot -o locales/en_GB.po`, puis `msgfmt` pour le `.mo`).
- `templates/` : modèles Twig (page de configuration construite avec les macros `components/form/fields_macros.html.twig` de GLPI, bandeau) rendus par `TemplateRenderer` ; après ajout ou modification d'un modèle, `php bin/console cache:clear`.
- Mails automatiques : `src/Alert.php` (repérage, anti-doublon, action automatique), `src/NotificationTargetAlert.php` (destinataires et balises), `src/Notifications.php` (gabarits et notifications GLPI créés à l'installation, supprimés à la désinstallation), table `glpi_plugin_warrantycolor_alerts`.
- `warrantycolor.xml` : descripteur pour le catalogue des plugins GLPI (version, compatibilité, licence).
- Compatibilité : GLPI 12.0 (testé sur 12.0.2), PHP 8.2 ; pas de borne maximale, à revalider à chaque version majeure. Le code garde la gestion du jeton CSRF de GLPI 11 « au cas où », sans que cette version soit supportée ni testée.
- Prérequis contrôlés au chargement (`plugin_warrantycolor_check_prerequisites`) : informations financières (`Infocom`) et moteur Twig de GLPI. S'ils manquent, GLPI désactive le plugin et affiche le message ; il faut le réactiver ensuite.

Licence GPLv3 — auteur : Andy Tkaczyk.
