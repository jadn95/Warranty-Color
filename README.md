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

---

## 🎯 Principe de fonctionnement

Warranty Color analyse la **date de fin de garantie** renseignée sur les assets.

Selon cette date, l'affichage de l'asset est adapté :

| Situation                                  | Affichage                      |
| ------------------------------------------ | ------------------------------ |
| Asset encore sous garantie                 | 🟢 Couleur « garantie valide » |
| Garantie arrivant prochainement à échéance | 🟠 Couleur « avertissement »   |
| Garantie expirée                           | Affichage normal               |

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

3. Vérifier que le dossier du plugin est correctement nommé.
4. Depuis GLPI, accéder à :

**Configuration → Plugins**

5. Installer **Warranty Color**.
6. Activer le plugin.

Une fois activé, le plugin applique automatiquement la coloration aux assets concernés.

---

## 🔌 Désactivation

Le plugin peut être désactivé depuis :

**Configuration → Plugins → Warranty Color**

La désactivation permet de conserver le plugin installé tout en désactivant son fonctionnement.

---

## 💻 Compatibilité

Warranty Color est conçu pour fonctionner avec les versions récentes de GLPI.

La version minimale de GLPI requise dépend de la version du plugin utilisée.

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

## 📜 Licence

Ce plugin est distribué sous licence **GPLv3**.

Voir le fichier [`LICENSE`](LICENSE) du projet pour connaître les conditions complètes de distribution, de modification et de redistribution.

---

## 👨‍💻 Auteur

**Andy Tkaczyk**

Plugin développé pour améliorer la gestion et le suivi des garanties dans GLPI.
