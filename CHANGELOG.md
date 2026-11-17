# Changelog

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

## 2.0.0
- Réécriture : configuration dans `glpi_configs`, état calculé côté serveur, pastille + icône + info-bulle, légende, bandeau sur les fiches, éléments associés des tickets.
