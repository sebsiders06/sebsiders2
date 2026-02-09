# Installation – Mise en ligne et envoi des mails

Guide pas à pas pour mettre le site en ligne et faire en sorte que les demandes de devis soient envoyées à **philippe.clemente@orange.fr**.

---

## Étape 1 : Mettre le site en ligne

1. Choisir un **hébergement web avec PHP** (OVH, o2switch, LWS, o2switch, etc.).
2. Souscrire à une offre (domaine + hébergement, ou hébergement seul).
3. Uploader **tous les fichiers du projet** à la racine du site (ou dans un sous-dossier) :
   - `index.html`
   - `mentions-legales-confidentialite-cgu.html`
   - `send-devis.php`
   - `config-mail.php`
   - `config-mail.example.php`
   - `composer.json`
   - le dossier `image/` (avec `image.png`, `image.jpeg`)

Le site doit être accessible en **https://votredomaine.fr/** (et non en ouvrant les fichiers en local).

---

## Étape 2 : Installer PHPMailer (dossier `vendor/`)

Sans le dossier `vendor/`, l’envoi par SMTP ne fonctionne pas. Deux possibilités :

### Option A : Composer est disponible sur le serveur (SSH)

En SSH, à la racine du projet :

```bash
composer install
```

Cela crée le dossier `vendor/` avec PHPMailer.

### Option B : Composer en local, puis upload

1. Sur votre ordinateur, installer [Composer](https://getcomposer.org/download/).
2. Ouvrir un terminal dans le dossier du projet et lancer :
   ```bash
   composer install
   ```
3. Uploader **tout le dossier `vendor/`** sur le serveur (au même niveau que `send-devis.php`).

---

## Étape 3 : Remplir la configuration email (`config-mail.php`)

Le fichier **config-mail.php** est déjà présent. Il suffit de le **compléter** avec les paramètres SMTP de votre hébergeur.

1. Ouvrir **config-mail.php** dans un éditeur.
2. Renseigner les champs suivants (les infos sont dans l’espace client de l’hébergeur, rubrique **Email** / **SMTP** / **Envoi**) :

| Champ | Exemple | Où le trouver |
|-------|--------|----------------|
| **smtp_host** | `ssl0.ovh.net` ou `smtp.orange.fr` | Doc / espace client hébergeur |
| **smtp_port** | `587` (TLS) ou `465` (SSL) | Souvent 587 |
| **smtp_secure** | `tls` ou `ssl` | `tls` pour 587, `ssl` pour 465 |
| **smtp_username** | `contact@votredomaine.fr` | Votre adresse email sur le domaine |
| **smtp_password** | Mot de passe de la boîte mail | Même mot de passe que la messagerie |
| **from_email** | `contact@votredomaine.fr` | Idéalement la même que smtp_username |
| **from_name** | `Formation SST` | Déjà rempli |

3. Vérifier que **use_smtp** est bien à **true**.
4. Enregistrer le fichier et le ré-uploader sur le serveur si besoin.

**Exemples d’hébergeurs :**

- **OVH** : Espace client → Hébergements → Email → Paramètres SMTP (souvent `ssl0.ovh.net`, port 587, TLS).
- **o2switch** : Manager → Emails → Paramètres de réception / envoi (serveur SMTP indiqué).
- **Orange (offre Pro)** : Paramètres de la boîte mail → Serveur sortant SMTP (`smtp.orange.fr`, port 587).

---

## Étape 4 : Tester l’envoi

1. Aller sur votre site : **https://votredomaine.fr/**
2. Aller à la section **Contact & demande de devis**.
3. Remplir le formulaire (nom, email, message) et cliquer sur **Envoyer ma demande**.
4. Vérifier :
   - Un message de succès s’affiche sur la page.
   - La boîte **philippe.clemente@orange.fr** reçoit le mail (vérifier aussi le dossier **spam**).

Si le message d’erreur « L’envoi a échoué » s’affiche :

- Vérifier que **config-mail.php** est bien rempli et que **use_smtp** est à **true**.
- Vérifier que le dossier **vendor/** est bien présent sur le serveur.
- Consulter les **logs d’erreur PHP** sur l’hébergement (souvent dans « Logs » / « Erreurs » du panel).

---

## Récapitulatif

| Étape | Action |
|-------|--------|
| 1 | Mettre le site en ligne (hébergement PHP). |
| 2 | Avoir le dossier **vendor/** (composer install en local ou sur le serveur). |
| 3 | Remplir **config-mail.php** avec les paramètres SMTP de l’hébergeur. |
| 4 | Tester le formulaire et vérifier la réception sur philippe.clemente@orange.fr. |

Une fois ces quatre étapes faites, les mails sont envoyés correctement.
