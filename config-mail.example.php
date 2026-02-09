<?php
/**
 * Configuration d'envoi d'emails (SMTP ou mail()).
 *
 * Pour activer l'envoi par SMTP (recommandé pour une bonne délivrabilité) :
 * 1. Copiez ce fichier en config-mail.php
 * 2. Remplissez les valeurs ci-dessous avec les identifiants SMTP de votre hébergeur
 *    (OVH, o2switch, Orange Pro, etc. fournissent un serveur SMTP dans l'espace client).
 *
 * Si config-mail.php n'existe pas ou si use_smtp = false, le script utilisera mail().
 */

return array(
    // true = utiliser SMTP (recommandé), false = utiliser mail() PHP
    'use_smtp' => false,

    // Serveur SMTP (ex: ssl0.ovh.net, smtp.orange.fr, mail.votredomaine.com)
    'smtp_host' => '',

    // Port : 587 (TLS), 465 (SSL), 25 (non sécurisé, à éviter)
    'smtp_port' => 587,

    // Protocole : 'tls' ou 'ssl' (laisser vide pour port 25)
    'smtp_secure' => 'tls',

    // true pour authentification SMTP (recommandé)
    'smtp_auth' => true,

    // Identifiant SMTP (souvent votre adresse email complète)
    'smtp_username' => '',

    // Mot de passe SMTP (celui de la boîte mail ou mot de passe d'application)
    'smtp_password' => '',

    // Adresse utilisée comme expéditeur (idéalement une adresse sur votre domaine)
    'from_email' => 'noreply@votredomaine.fr',

    // Nom affiché comme expéditeur
    'from_name' => 'Formation SST',
);
