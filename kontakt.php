<?php
// Rückruf-Formular: prüft die Eingabe und schickt sie als Mail an HOFI-TEC.
declare(strict_types=1);

const EMPFAENGER = 'info@hofi-tec.ch';
const ABSENDER   = 'formular@hofi-tec.ch';

function fehler(string $text, int $code = 400): never {
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    $t = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="de-CH"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<meta name="robots" content="noindex"><title>Fehler – HOFI-TEC</title>'
       . '<link rel="stylesheet" href="/style.css"></head><body><main><section class="hero">'
       . '<h1>Das hat leider nicht geklappt</h1><p class="lead">' . $t
       . ' Ruf uns einfach an: <a href="tel:+41798504149">079 850 41 49</a></p>'
       . '<div class="actions"><a class="btn" href="/#kontakt">Zurück zum Formular</a></div>'
       . '</section></main></body></html>';
    exit;
}

function feld(string $name, int $max): string {
    $v = $_POST[$name] ?? '';
    if (!is_string($v)) {
        return '';
    }
    $v = trim(str_replace("\0", '', $v));
    return mb_substr($v, 0, $max, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /#kontakt', true, 303);
    exit;
}

// Honeypot: Menschen sehen das Feld nicht, Bots füllen es aus.
if (feld('bot-field', 100) !== '') {
    header('Location: /danke.html', true, 303);
    exit;
}

$name = feld('name', 100);
$tel  = feld('tel', 30);
$zeit = feld('zeit', 100);
$msg  = feld('msg', 1000);

if ($name === '') {
    fehler('Bitte gib deinen Namen an.');
}
if (!preg_match('/^[0-9+()\/\- ]{6,30}$/', $tel)) {
    fehler('Bitte gib eine gültige Telefonnummer an.');
}

// Einfache Bremse: höchstens eine Anfrage pro Minute und Adresse.
$ip   = $_SERVER['REMOTE_ADDR'] ?? 'unbekannt';
$sperre = sys_get_temp_dir() . '/hofitec-kontakt-' . hash('sha256', $ip);
if (is_file($sperre) && time() - (int) filemtime($sperre) < 60) {
    fehler('Bitte warte kurz, bevor du es nochmals versuchst.', 429);
}

$text = "Neue Rückrufanfrage über die Website\n\n"
      . "Name: $name\n"
      . "Telefon: $tel\n"
      . "Wann passt es: " . ($zeit !== '' ? $zeit : '-') . "\n\n"
      . "Worum geht es:\n" . ($msg !== '' ? $msg : '-') . "\n";

// Nutzereingaben stehen nur im Mail-Text, nie in den Kopfzeilen.
$kopf = "From: HOFI-TEC Formular <" . ABSENDER . ">\r\n"
      . "Content-Type: text/plain; charset=UTF-8\r\n"
      . "Content-Transfer-Encoding: 8bit";
$betreff = '=?UTF-8?B?' . base64_encode('Rückrufanfrage von der Website') . '?=';

if (!mail(EMPFAENGER, $betreff, $text, $kopf, '-f' . ABSENDER)) {
    fehler('Deine Anfrage konnte nicht gesendet werden.', 500);
}

@touch($sperre);
header('Location: /danke.html', true, 303);
