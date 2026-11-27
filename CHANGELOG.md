# Changelog

## 2.2.2
- Teinte de ligne complète : s'étend à toute la ligne (la bande alternée du thème en recouvrait une partie).
- Info-bulle des éléments cliquables de la légende : couleur de l'état, comme celle des pastilles (et non le bleu de GLPI).

## 2.2.1
- **Mails automatiques** à l'utilisateur affecté à un équipement dont la garantie arrive à échéance (même délai d'alerte que la pastille orange), avec la date limite pour signaler un problème (la moitié du délai) afin de faire jouer l'assurance.
  - Action automatique quotidienne `WarrantyAlerts` (Configuration > Actions automatiques), notifications et gabarits GLPI natifs (`Alert`, `NotificationTargetAlert`), table de suivi `glpi_plugin_warrantycolor_alerts` : un mail par équipement et par période de garantie (une garantie prolongée redéclenche un mail).
  - Options : activation, **mode simulation** (activé par défaut : rien n'est envoyé), copie à l'administrateur de l'entité (service informatique), **autres adresses en copie** saisies dans la configuration (10 au plus, validées, sans doublon), second mail à la moitié du délai, objet et texte du mail modifiables (balises `##item.name##`, `##warranty.end##`…).
  - Garde-fous : simulation affichée dans la configuration, bouton « Marquer les équipements concernés comme déjà notifiés » (évite l'envoi massif au premier lancement), 200 mails par passage au maximum, équipements sans utilisateur actif ou sans e-mail ignorés et listés, états exclus respectés, case absente d'une requête = valeur enregistrée.
- Page de configuration en deux onglets (**Affichage** / **Mails automatiques**) dans un seul formulaire ; onglet mémorisé dans l'adresse (`#mails`), pastille « modifié » par onglet.
- Avertissement quand la copie à l'administrateur est activée sans adresse d'administrateur dans l'entité racine.
- Traductions : 111 textes.

## 2.1.1
- Les navigateurs gardaient l'ancien `config.js`/CSS après une mise à jour (GLPI dérive le paramètre `?v=` des fichiers de la version du plugin) : la version est incrémentée. **Règle : changer `PLUGIN_WARRANTYCOLOR_VERSION` à chaque modification de `public/`**, sinon un Ctrl+F5 est nécessaire.

## 2.1.0
- Légende **cliquable** : un clic sur « Garantie valide », « Expire dans N jours » ou « Expirée » ouvre une recherche GLPI native (option « Date d'expiration de la garantie ») listant tous les équipements du type dans cet état, triés par date de fin. La légende s'affiche sur toute liste d'équipements, même sans équipement signalé sur la page (type lu sur le formulaire de recherche de GLPI).
- Date affichée alignée sur la fiche GLPI : dernier jour de garantie = début + durée − 1 jour ; expirée à partir du lendemain.
- Nouvelle option **« États à ne pas signaler »** : les équipements retirés, perdus… n'ont ni pastille, ni bandeau, et sont absents des listes de la légende.
- Repérage des lignes plus robuste : case de sélection native de GLPI (`item[Type][id]`) et colonne Nom (`data-searchopt-content-id`), repli sur le lien de la fiche.
- Corrections : requête invalide → erreur 400 (au lieu de 500) ; légende sur les types personnalisés (adresse de liste déjà paramétrée).
- Info-bulle flottante (jamais rognée par le tableau), fermable avec Échap ; les pastilles ne sont plus des arrêts de tabulation.
- Correction : enregistrement de la configuration (« XML not well formed » sous GLPI 12, formulaire envoyé à une mauvaise adresse).
- Performance : `status.php` libère le verrou de session (lots demandés en parallèle) ; chargement par lots factorisé.
- Repli visuel pour les navigateurs sans `color-mix()`.
- Tests : bornes du délai d'alerte.
- Twig : la page de configuration (`templates/config.html.twig`) et le bandeau des fiches (`templates/banner.html.twig`) utilisent les modèles Twig de GLPI (échappement automatique, plus de HTML assemblé à la main).
- Page de configuration reconstruite avec les **macros de formulaire de GLPI** (`largeTitle`, `numberField`, `colorField` avec sélecteur Spectrum, `dropdownArrayField` Select2 multiple, `checkboxField`), sections Seuil / Couleurs / Exclusions / Affichage / Aperçu, aides en infobulle « ? », pied de carte natif (Sauvegarder à droite). L'aperçu écoute les événements jQuery de ces composants.
- Page de configuration (revue UX/accessibilité) : aides lisibles en thème sombre, états à exclure en cases à cocher (plus de Ctrl+clic), aperçu qui suit le délai saisi, message annoncé après « Valeurs par défaut », avertissement avant de quitter avec des modifications non enregistrées (`data-track-changes`), icônes sur les boutons et les alertes, styles dans la feuille du plugin.
- Prérequis : `plugin_warrantycolor_check_prerequisites` vérifie `Infocom` et le moteur Twig de GLPI (message affiché, plugin désactivé par GLPI sinon). La page de configuration affiche des **avertissements** (droit sur les informations financières manquant, aucun type d'équipement avec informations financières, états exclus supprimés).
- Compatibilité déclarée : GLPI 12.0 (testé sur 12.0.2) ; la gestion du jeton CSRF de GLPI 11 est conservée au cas où, sans support annoncé.
- Garantie à vie (durée −1) vérifiée sur des données réelles : pastille, bandeau, légende et liste « valide ».
- Conformité GLPI : `front/*.php` n'inclut plus `inc/includes.php` (déjà chargé par GLPI 11 et 12) ; dossier `locales/` (modèle `warrantycolor.pot`, `fr_FR.po`/`.mo`) ; descripteur `warrantycolor.xml` pour le catalogue des plugins ; `logo-40.png` pour le descripteur.

## 2.0.0
- Réécriture : configuration dans `glpi_configs`, état calculé côté serveur, pastille + icône + info-bulle, légende, bandeau sur les fiches, éléments associés des tickets.
