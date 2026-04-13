<?php
/**
 * Envoi des demandes de devis par email.
 * Reçoit POST : nom, email, telephone, sujet, message.
 * Utilise PHPMailer + SMTP si configuré (config-mail.php), sinon mail() avec en-têtes optimisés.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
if ($origin && preg_match('#^https?://' . preg_quote($host, '#') . '(:\d+)?$#', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'message' => 'Méthode non autorisée.'));
    exit;
}

$nom = isset($_POST['nom']) ? trim((string) $_POST['nom']) : '';
$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$telephone = isset($_POST['telephone']) ? trim((string) $_POST['telephone']) : '';
$sujetForm = isset($_POST['sujet']) ? trim((string) $_POST['sujet']) : '';
$message = isset($_POST['message']) ? trim((string) $_POST['message']) : '';

$nom = mb_substr($nom, 0, 200);
$email = mb_substr($email, 0, 254);
$telephone = mb_substr($telephone, 0, 30);
$sujetForm = mb_substr($sujetForm, 0, 200);
$message = mb_substr($message, 0, 2000);

$errors = array();
if ($nom === '') {
    $errors[] = 'Veuillez indiquer votre nom.';
}
if ($email === '') {
    $errors[] = 'Veuillez indiquer votre adresse email.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Veuillez saisir une adresse email valide.';
}
if ($telephone !== '' && !preg_match('/^[0-9+\s().-]{6,30}$/', $telephone)) {
    $errors[] = 'Veuillez saisir un numéro de téléphone valide.';
}
if ($sujetForm === '') {
    $errors[] = 'Veuillez indiquer le sujet de votre demande.';
}
if ($message === '') {
    $errors[] = 'Veuillez rédiger votre message.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'message' => implode(' ', $errors)));
    exit;
}

$destinataire = 'philippe.clemente@orange.fr';
$sujet = 'Demande de devis – ' . $sujetForm;

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$telephoneAffiche = $telephone !== '' ? $telephone : 'Non renseigné';

$rows = array(
    array('Nom', e($nom)),
    array('Email', e($email)),
    array('Téléphone', e($telephoneAffiche)),
    array('Sujet', e($sujetForm)),
    array('Message', nl2br(e($message))),
);

$tableRows = '';
foreach ($rows as $row) {
    $tableRows .= '<tr>';
    $tableRows .= '<td style="padding:8px;border:1px solid #d0d7de;background:#f8fafc;font-weight:700;width:160px;">' . e($row[0]) . '</td>';
    $tableRows .= '<td style="padding:8px;border:1px solid #d0d7de;background:#ffffff;">' . $row[1] . '</td>';
    $tableRows .= '</tr>';
}

$corpsHtml = '<!DOCTYPE html><html lang="fr"><body style="margin:0;padding:24px;font-family:Arial,sans-serif;color:#1f2937;background:#ffffff;">';
$corpsHtml .= '<h2 style="margin:0 0 16px;font-size:20px;color:#1e3a5f;">Nouvelle demande de devis</h2>';
$corpsHtml .= '<p style="margin:0 0 16px;">Une demande de devis a été envoyée depuis le site Formation SST.</p>';
$corpsHtml .= '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:720px;border:1px solid #d0d7de;">';
$corpsHtml .= $tableRows;
$corpsHtml .= '</table>';
$corpsHtml .= '</body></html>';

$corpsTexte = "Une demande de devis a été envoyée depuis le site Formation SST.\n\n";
$corpsTexte .= "Nom : " . $nom . "\n";
$corpsTexte .= "Email : " . $email . "\n";
$corpsTexte .= "Téléphone : " . $telephoneAffiche . "\n";
$corpsTexte .= "Sujet : " . $sujetForm . "\n\n";
$corpsTexte .= "Message :\n" . $message . "\n";

// Chargement de la config optionnelle (SMTP)
$config = array();
$configPath = __DIR__ . '/config-mail.php';
if (is_file($configPath)) {
    $config = (array) include $configPath;
}

$sent = false;

// 1) PHPMailer + SMTP si disponible et configuré
$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload) && !empty($config['use_smtp']) && !empty($config['smtp_host'])) {
    try {
        require_once $autoload;
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $langPath = __DIR__ . '/vendor/phpmailer/phpmailer/language/';
        if (is_dir($langPath)) {
            $mail->setLanguage('fr', $langPath);
        }
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->Port = (int) (isset($config['smtp_port']) ? $config['smtp_port'] : 587);
        $mail->SMTPAuth = !empty($config['smtp_auth']);
        if ($mail->SMTPAuth) {
            $mail->Username = isset($config['smtp_username']) ? $config['smtp_username'] : '';
            $mail->Password = isset($config['smtp_password']) ? $config['smtp_password'] : '';
        }
        if (!empty($config['smtp_secure'])) {
            $mail->SMTPSecure = $config['smtp_secure'];
        }
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ),
        );
        $fromEmail = !empty($config['from_email']) ? $config['from_email'] : 'noreply@' . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
        $fromName = !empty($config['from_name']) ? $config['from_name'] : 'Formation SST';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($destinataire);
        $mail->addReplyTo($email, $nom);
        $mail->Subject = $sujet;
        $mail->isHTML(true);
        $mail->Body = $corpsHtml;
        $mail->AltBody = $corpsTexte;
        $mail->send();
        $sent = true;
    } catch (\Throwable $e) {
        $sent = false;
    }
}

// 2) Fallback : mail() avec en-têtes optimisés pour la délivrabilité
if (!$sent) {
    $fromEmail = !empty($config['from_email']) ? $config['from_email'] : 'noreply@' . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
    $fromName = !empty($config['from_name']) ? $config['from_name'] : 'Formation SST';
    $domain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
    $messageId = '<' . md5(uniqid((string) mt_rand(), true)) . '@' . $domain . '>';

    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $nom . ' <' . $email . '>';
    $headers[] = 'Date: ' . gmdate('D, d M Y H:i:s O');
    $headers[] = 'Message-ID: ' . $messageId;
    $headers[] = 'X-Priority: 3';
    $headers[] = 'X-Mailer: Formation-SST-PHP';

    $encodedSubject = '=?UTF-8?B?' . base64_encode($sujet) . '?=';
    $sent = mail($destinataire, $encodedSubject, $corpsHtml, implode("\r\n", $headers));
}

if ($sent) {
    echo json_encode(array('ok' => true, 'message' => 'Votre demande a bien été envoyée. Nous vous répondrons sous 24 h.'));
} else {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'message' => 'L\'envoi a échoué. Vous pouvez nous contacter par téléphone ou à ' . $destinataire . '.'));
}
