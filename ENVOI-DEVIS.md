# Envoi des demandes de devis par email

Pour que **philippe.clemente@orange.fr** reçoive bien les mails envoyés depuis le formulaire, suivez ces étapes.

## 1. Installer les dépendances PHP (PHPMailer)

Sur le serveur ou en local, à la racine du projet :

```bash
composer install
```

Cela crée le dossier `vendor/` avec PHPMailer. Sans cette étape, le script utilisera uniquement `mail()` PHP (moins fiable sur beaucoup d’hébergements).

## 2. Configurer l’envoi par SMTP (recommandé)

L’envoi par SMTP est plus fiable que `mail()` et limite les refus / passage en spam.

1. Le fichier `config-mail.php` existe déjà (sinon copiez `config-mail.example.php` en `config-mail.php`).

2. Ouvrez `config-mail.php` et renseignez les paramètres SMTP fournis par votre hébergeur (OVH, o2switch, Orange Pro, etc.) :
   - **use_smtp** : `true`
   - **smtp_host** : serveur SMTP (ex. `ssl0.ovh.net`, `smtp.orange.fr`)
   - **smtp_port** : en général `587` (TLS) ou `465` (SSL)
   - **smtp_secure** : `tls` ou `ssl`
   - **smtp_username** : souvent votre adresse email
   - **smtp_password** : mot de passe de la boîte mail ou mot de passe d’application
   - **from_email** : adresse d’envoi (idéalement sur votre domaine, ex. `noreply@votredomaine.fr`)
   - **from_name** : `Formation SST`

3. Ne commitez pas `config-mail.php` (il est dans `.gitignore`) pour ne pas exposer vos identifiants.

## 3. Sans SMTP (fallback)

Si vous ne créez pas `config-mail.php` ou si **use_smtp** est `false` :

- Le script utilise la fonction `mail()` de PHP avec des en-têtes optimisés (Reply-To, Message-ID, Date, etc.).
- La délivrabilité dépend de la configuration du serveur (Sendmail/Postfix) et des enregistrements SPF/DKIM du domaine.

## 4. Vérifier la réception

Après mise en ligne :

1. Envoyez une demande de devis depuis le formulaire.
2. Vérifiez la boîte **philippe.clemente@orange.fr** (réception et dossier spam).
3. En cas d’échec : activer SMTP (étape 2) et, si besoin, configurer SPF/DKIM pour le domaine d’envoi (voir documentation de l’hébergeur).
