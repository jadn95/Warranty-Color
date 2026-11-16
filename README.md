# GLPI Warranty Color

**Warranty Color** est un plugin pour [GLPI](https://glpi-project.org/) permettant de visualiser rapidement l'état de garantie des équipements directement depuis les listes d'assets.

L'objectif est de rendre les garanties arrivant prochainement à échéance immédiatement visibles, sans avoir à ouvrir chaque fiche d'équipement.

---

## ✨ Fonctionnalités

* 🎨 Coloration automatique des lignes des assets selon leur état de garantie.
* 🟢 Une couleur spécifique lorsque l'asset est **encore sous garantie**.
* 🟠 Une couleur d'alerte lorsque la date d'expiration de la garantie **approche**.
* ⚪ Retour à l'affichage normal lorsque la garantie est **expirée**.
* ⚙️ Paramétrage de la couleur et de la durée d'alerte depuis la configuration du plugin.
* 🔧 Application à **tous les types d'assets** disponibles dans GLPI.
* 🧩 Compatible avec les assets natifs de GLPI ainsi qu'avec les **assets personnalisés créés manuellement**.
* 🚀 Fonctionnement automatique dès lors que le plugin est activé.
* ♿ Information **non portée par la couleur seule** : pastille avec icône et info-bulle, légende au-dessus des listes.
* 🧾 Bandeau d'état sur la **fiche de l'équipement**, et pastille à côté de chaque équipement associé sur les **tickets, problèmes et changements**.
* 🔒 Respect des droits : seuls les équipements que l'utilisateur peut voir (type, informations financières, entités) sont signalés.

---

## 🎯 Principe de fonctionnement

Warranty Color analyse la **date de fin de garantie** renseignée sur les assets.

Selon cette date, l'affichage de l'asset est adapté :

| Situation                                  | Affichage                      |
| ------------------------------------------ | ------------------------------ |
| Asset encore sous garantie (ou « à vie »)  | 🟢 Couleur « garantie valide » |
| Garantie arrivant prochainement à échéance | 🟠 Couleur « avertissement »   |
| Garantie expirée                           | Pastille grise discrète, ligne normale |
| Garantie non renseignée                    | Rien                           |

La durée à partir de laquelle un asset doit être considéré comme **proche de l'expiration** est configurable dans les paramètres du plugin.

### Exemple

Avec une durée d'alerte configurée à **30 jours** :

* Garantie dans 6 mois → 🟢 couleur « garantie valide »
* Garantie dans 15 jours → 🟠 couleur « avertissement »
* Garantie expirée → affichage normal

---

## ⚙️ Configuration

La configuration du plugin permet de définir :

* **Nombre de jours avant expiration** : délai à partir duquel la couleur d'avertissement doit être appliquée.
* **Couleur de garantie valide** : couleur utilisée lorsqu'un asset est toujours sous garantie.
* **Couleur d'avertissement** : couleur utilisée lorsque la garantie arrive prochainement à échéance.
* **Teinter toute la ligne** : sinon, seuls la pastille et le repère latéral sont affichés.

La page de configuration propose un aperçu en direct et un bouton « Valeurs par défaut ». Elle est réservée aux utilisateurs ayant le droit de configuration.

Les paramètres peuvent être adaptés selon les besoins de l'organisation.

---

## 🧩 Compatibilité avec les assets personnalisés

L'un des objectifs de Warranty Color est de ne pas se limiter aux types d'équipements natifs de GLPI.

Le plugin s'applique également aux **classes d'assets personnalisées** créées dans GLPI.

Par exemple :

* Tablettes
* Smartphones
* Équipements radio
* Terminaux spécifiques
* Matériel métier
* Tout autre type d'asset personnalisé

Warranty Color peut également prendre en compte leur date de garantie.

Cela permet d'avoir une **vision homogène des garanties sur l'ensemble du parc informatique et matériel**, indépendamment du type d'asset utilisé dans GLPI.

---

## 📦 Installation

1. Télécharger ou cloner le plugin.
2. Copier le dossier du plugin dans le répertoire `plugins` de GLPI :

```text
/var/www/html/glpi/plugins/
```

3. Vérifier que le dossier du plugin s'appelle bien `warrantycolor` (c'est le nom attendu par GLPI) et qu'il appartient à l'utilisateur du serveur web :

```bash
chown -R www-data:www-data /var/www/html/glpi/plugins/warrantycolor
```
4. Depuis GLPI, accéder à :

**Configuration → Plugins**

5. Installer **Warranty Color**.
6. Activer le plugin.

Une fois activé, le plugin applique automatiquement la coloration aux assets concernés.

L'installation et l'activation peuvent aussi se faire en ligne de commande :

```bash
php bin/console plugin:install warrantycolor -u <utilisateur-admin>
php bin/console plugin:activate warrantycolor
```

**Mise à jour depuis la 1.x** : la configuration (table propre au plugin) est reprise automatiquement dans la configuration native de GLPI, puis la table est supprimée.

---

## 🔌 Désactivation

Le plugin peut être désactivé depuis :

**Configuration → Plugins → Warranty Color**

La désactivation permet de conserver le plugin installé tout en désactivant son fonctionnement.

---

La désinstallation supprime la configuration du plugin ; les dates de garantie des équipements ne sont jamais modifiées.

---

## 🧯 Dépannage

* **Aucune ligne n'est colorée** : le plugin ne colore que les équipements dont la garantie est renseignée (date de début **et** durée supérieure à 0 dans l'onglet *Gestion*). Une liste d'équipements sans garantie renseignée reste normale.
* **Un équipement expiré n'est pas teinté** : c'est voulu, seule une pastille grise discrète est affichée.
* **L'ancien affichage persiste après une mise à jour** : actualiser la page en vidant le cache (Ctrl + F5), le navigateur conserve les anciens scripts.
* **Les pastilles n'apparaissent pas pour un type d'asset personnalisé** : vérifier que la capacité « Gestion financière » est activée dans la définition de l'asset.
* **Une information semble manquante** : le plugin respecte les droits de l'utilisateur, un équipement d'une entité non visible n'est pas signalé.

---

## 💻 Compatibilité

Warranty Color est conçu pour fonctionner avec les versions récentes de GLPI.

La version 2.0 requiert **GLPI 11.0 ou supérieur** et **PHP 8.2 ou supérieur**. Elle a été testée sur GLPI 12.0.2. Aucune borne de version maximale n'est déclarée : elle est à revalider à chaque version majeure de GLPI.

---

## 💡 Philosophie du plugin

Warranty Color a été conçu pour répondre à un besoin simple :

> **Identifier rapidement les équipements dont la garantie arrive prochainement à échéance.**

Dans un parc comportant plusieurs centaines ou milliers d'équipements, consulter individuellement les fiches pour vérifier les dates de garantie peut être long.

La coloration permet donc d'obtenir une **information visuelle immédiate** directement depuis les listes d'assets.

### Code couleur par défaut

🟢 **Vert** → L'asset est toujours sous garantie.

🟠 **Orange** → La garantie arrive prochainement à échéance.

⚪ **Normal** → La garantie est expirée.

Les couleurs et le délai d'alerte peuvent être personnalisés dans les paramètres du plugin.

---

## 🛠️ Fonctionnement technique

GLPI n'offrant pas de crochet pour colorer les lignes d'une liste, un script (`public/js/warrantycolor.js`) repère les liens de fiche des tableaux de résultats et interroge `front/status.php` par lots d'adresses. Côté serveur, une seule requête par type d'équipement est exécutée.

Calcul : date de début de garantie + durée en mois. Durée −1 = à vie ; durée 0, date absente ou invalide = non renseignée.

`php tests/compute_test.php` exécute les tests du calcul d'état (sans base de données).

---

## 📜 Licence

Ce plugin est distribué sous licence **GPLv3**.

Voir le fichier [`LICENSE`](LICENSE) du projet pour connaître les conditions complètes de distribution, de modification et de redistribution.

---

## 👨‍💻 Auteur

**Andy Tkaczyk**

Plugin développé pour améliorer la gestion et le suivi des garanties dans GLPI.
