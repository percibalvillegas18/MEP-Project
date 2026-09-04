<?php
/**
 * Simple SMTP mailer — no external dependencies.
 * Works with Gmail, Outlook, or any SMTP server that supports STARTTLS.
 *
 * Usage:
 *   require_once 'includes/mailer.php';
 *   $ok = smtp_send('to@example.com', 'Subject', 'Body text');
 *
 * Configuration: set SMTP_* constants in config.php
 */

/**
 * Send an email via SMTP with STARTTLS authentication.
 *
 * @param  string $to      Recipient email
 * @param  string $subject Email subject
 * @param  string $body    Plain-text body
 * @return bool            true on success
 */
function smtp_send(string $to, string $subject, string $body): bool {
    // Read config constants (set in config.php)
    $host = defined('SMTP_HOST') ? SMTP_HOST : '';
    $port = defined('SMTP_PORT') ? SMTP_PORT : 587;
    $user = defined('SMTP_USER') ? SMTP_USER : '';
    $pass = defined('SMTP_PASS') ? SMTP_PASS : '';
    $from = defined('SMTP_FROM') ? SMTP_FROM : $user;
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'MEP Projects Portal';

    if ($host === '' || $user === '' || $pass === '') {
        error_log('SMTP not configured. Set SMTP_HOST, SMTP_USER, SMTP_PASS in config.php');
        return false;
    }

    $errno = 0;
    $errstr = '';
    $socket = @fsockopen($host, $port, $errno, $errstr, 15);
    if (!$socket) {
        error_log("SMTP connect failed: $errstr ($errno)");
        return false;
    }

    // Helper: send command + read response
    $lastResponse = '';
    $talk = function (string $cmd = '') use ($socket, &$lastResponse): string {
        if ($cmd !== '') {
            fwrite($socket, $cmd . "\r\n");
        }
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            // Last line of multi-line reply has space after code (e.g. "250 OK")
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        $lastResponse = $response;
        return substr($response, 0, 3); // 3-digit status code
    };

    try {
        // Greeting
        if ($talk() !== '220') throw new \RuntimeException("Bad greeting: $lastResponse");

        // EHLO
        $talk("EHLO localhost");

        // STARTTLS
        if ($talk("STARTTLS") !== '220') throw new \RuntimeException("STARTTLS failed: $lastResponse");

        // Enable TLS encryption on the socket
        $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
        if (!$crypto) throw new \RuntimeException("TLS negotiation failed");

        // EHLO again after TLS
        $talk("EHLO localhost");

        // AUTH LOGIN
        if ($talk("AUTH LOGIN") !== '334') throw new \RuntimeException("AUTH failed: $lastResponse");
        if ($talk(base64_encode($user)) !== '334') throw new \RuntimeException("AUTH user failed: $lastResponse");
        if ($talk(base64_encode($pass)) !== '235') throw new \RuntimeException("AUTH pass failed: $lastResponse");

        // MAIL FROM
        if ($talk("MAIL FROM:<{$from}>") !== '250') throw new \RuntimeException("MAIL FROM failed: $lastResponse");

        // RCPT TO
        if ($talk("RCPT TO:<{$to}>") !== '250') throw new \RuntimeException("RCPT TO failed: $lastResponse");

        // DATA
        if ($talk("DATA") !== '354') throw new \RuntimeException("DATA failed: $lastResponse");

        // Build the message headers + body
        $date = date('r');
        $message  = "Date: {$date}\r\n";
        $message .= "From: {$fromName} <{$from}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "\r\n";
        // Escape lines starting with a dot (SMTP transparency)
        $message .= str_replace("\r\n.", "\r\n..", $body);
        $message .= "\r\n.";

        if ($talk($message) !== '250') throw new \RuntimeException("Message rejected: $lastResponse");

        $talk("QUIT");
        fclose($socket);
        return true;

    } catch (\RuntimeException $e) {
        error_log("SMTP error: " . $e->getMessage());
        @fwrite($socket, "QUIT\r\n");
        @fclose($socket);
        return false;
    }
}
