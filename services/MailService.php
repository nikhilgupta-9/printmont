<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Minimal SMTP sender. Deliberately dependency-free: vendor/phpmailer is listed
 * in composer.json but is not actually installed on this project, so anything
 * relying on PHPMailer silently fails.
 *
 * Credentials come from the email_configurations table, which the admin panel
 * writes (Settings -> Email Configurations). That table carries two parallel
 * column sets (mail_* and smtp_*); both are read, mail_* winning.
 */
class MailService {
    private $db;
    private $lastError = '';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /** Why the last sendEmail() returned false. */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Resolve config for a purpose, falling back to any active config.
     * Returns null when nothing is configured, so callers can say so plainly
     * instead of silently trying a hardcoded mailbox.
     */
    public function getConfigForPurpose($purpose = 'Registration') {
        if (!$this->db) {
            return null;
        }

        $row = null;

        $stmt = $this->db->prepare(
            "SELECT * FROM email_configurations
             WHERE (purpose = ? OR config_name LIKE ?) AND status = 'active'
             LIMIT 1"
        );
        if ($stmt) {
            $likePurpose = "%{$purpose}%";
            $stmt->bind_param("ss", $purpose, $likePurpose);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        // Any active config beats not sending at all.
        if (!$row) {
            $res = $this->db->query("SELECT * FROM email_configurations WHERE status = 'active' ORDER BY id ASC LIMIT 1");
            $row = $res ? $res->fetch_assoc() : null;
        }

        return $row ? $this->normalizeConfig($row) : null;
    }

    /**
     * Send an HTML email over SMTP.
     * Supports implicit TLS (port 465) and STARTTLS (587) — the previous version
     * hardcoded ssl://smtp.gmail.com:465 and ignored the configured host entirely,
     * so any non-Gmail SMTP failed authentication.
     */
    public function sendEmail($to, $subject, $bodyHtml, $purpose = 'Registration', $customFromName = null) {
        $this->lastError = '';

        $config = $this->getConfigForPurpose($purpose);
        if (!$config) {
            $this->lastError = 'No active email configuration found. Add one under Email Configurations and set its status to active.';
            error_log('MailService: ' . $this->lastError);
            return false;
        }

        return $this->sendEmailWithConfig($config, $to, $subject, $bodyHtml, $customFromName);
    }

    /**
     * Deliver using an explicit config, bypassing the database lookup. Used by
     * the admin panel to test settings that have not been saved yet.
     */
    public function sendEmailWithConfig(array $config, $to, $subject, $bodyHtml, $customFromName = null) {
        $this->lastError = '';
        $fromName = $customFromName ?: $config['fromName'];

        try {
            $socket = $this->openSession($config);
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log('MailService: ' . $this->lastError);
            return false;
        }

        try {
            $this->command($socket, 'MAIL FROM: <' . $config['fromEmail'] . '>', '250');
            $this->command($socket, 'RCPT TO: <' . $to . '>', '250');
            $this->command($socket, 'DATA', '354');

            $headers = 'From: ' . $this->encodeHeader($fromName) . ' <' . $config['fromEmail'] . ">\r\n"
                . 'To: <' . $to . ">\r\n"
                . 'Subject: ' . $this->encodeHeader($subject) . "\r\n"
                . 'Date: ' . date('r') . "\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n\r\n";

            // Dot-stuffing: a line that is just "." would otherwise end the message.
            $body = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r", "\n"], "\r\n", $bodyHtml));

            fwrite($socket, $headers . $body . "\r\n.\r\n");
            $this->expect($socket, $this->read($socket), '250', 'message body');

            $this->command($socket, 'QUIT', null);
            fclose($socket);

            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log('MailService (' . $config['configName'] . ' via ' . $config['host'] . ':' . $config['port'] . '): ' . $this->lastError);
            @fclose($socket);
            return false;
        }
    }

    /**
     * Connect, negotiate TLS and authenticate. Returns a ready socket.
     * @throws RuntimeException with a human-readable reason
     */
    private function openSession(array $config) {
        $useImplicitTls = ($config['encryption'] === 'ssl') || (int) $config['port'] === 465;
        $address = ($useImplicitTls ? 'ssl://' : '') . $config['host'] . ':' . $config['port'];

        $context = stream_context_create([
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $socket = @stream_socket_client($address, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            throw new RuntimeException(sprintf(
                'Could not connect to %s:%d — %s (%d)',
                $config['host'], $config['port'], $errstr ?: 'connection refused', $errno
            ));
        }

        stream_set_timeout($socket, 15);

        try {
            $this->expect($socket, $this->read($socket), '220', 'greeting');
            $this->command($socket, 'EHLO printmont', '250');

            if (!$useImplicitTls && $config['encryption'] !== 'none') {
                $this->command($socket, 'STARTTLS', '220');
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('STARTTLS negotiation failed');
                }
                // The server forgets everything before STARTTLS, so greet again.
                $this->command($socket, 'EHLO printmont', '250');
            }

            $this->command($socket, 'AUTH LOGIN', '334');
            $this->command($socket, base64_encode($config['username']), '334', 'username');
            $this->command($socket, base64_encode($config['password']), '235', 'authentication');
        } catch (Throwable $e) {
            @fclose($socket);
            throw $e;
        }

        return $socket;
    }

    /**
     * Connect and authenticate without sending anything, for the admin
     * "Test Connection" button.
     *
     * @param array|null $override raw admin-form values; null uses the stored config
     * @return array{success: bool, error: string}
     */
    public function testConnection($override = null, $purpose = 'Registration') {
        $config = $override ? $this->normalizeConfig($override) : $this->getConfigForPurpose($purpose);

        if (!$config) {
            return ['success' => false, 'error' => 'Host and username are required.'];
        }

        try {
            $socket = $this->openSession($config);
            $this->command($socket, 'QUIT', null);
            @fclose($socket);

            return ['success' => true, 'error' => ''];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /** Map loose admin-form / DB keys onto the shape the sender expects. */
    public function normalizeConfig(array $row) {
        $pick = function (...$keys) use ($row) {
            foreach ($keys as $key) {
                if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                    return trim((string) $row[$key]);
                }
            }
            return '';
        };

        $host = $pick('mail_host', 'smtp_host', 'host');
        $username = $pick('mail_username', 'smtp_user', 'username');
        if ($host === '' || $username === '') {
            return null;
        }

        $port = (int) ($pick('mail_port', 'smtp_port', 'port') ?: 587);
        $encryption = strtolower($pick('mail_encryption', 'encryption') ?: ($port === 465 ? 'ssl' : 'tls'));

        return [
            'host'       => $host,
            'port'       => $port ?: 587,
            'encryption' => $encryption,
            'username'   => $username,
            'password'   => $pick('mail_password', 'smtp_pass', 'password'),
            'fromEmail'  => $pick('mail_from_address', 'from_email') ?: $username,
            'fromName'   => $pick('mail_from_name', 'from_name') ?: 'Printmont',
            'configName' => $pick('config_name') ?: '(unsaved form values)',
        ];
    }

    private function read($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Multi-line replies use "250-"; the final line uses "250 ".
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * @param string|null $expected response code to require, null to fire-and-forget
     * @param string|null $label     shown on failure; always pass one for credential
     *                               lines so the base64 secret never reaches a log
     */
    private function command($socket, $command, $expected, $label = null) {
        fwrite($socket, $command . "\r\n");
        if ($expected === null) {
            return '';
        }
        $response = $this->read($socket);
        $this->expect($socket, $response, $expected, $label ?: explode(' ', $command)[0]);
        return $response;
    }

    private function expect($socket, $response, $code, $label) {
        if (strncmp(trim($response), $code, strlen($code)) !== 0) {
            throw new RuntimeException(sprintf('SMTP %s failed (expected %s): %s', $label, $code, trim($response) ?: 'no response'));
        }
    }

    private function encodeHeader($value) {
        return preg_match('/[^\x20-\x7E]/', $value)
            ? '=?UTF-8?B?' . base64_encode($value) . '?='
            : $value;
    }

    /**
     * Send User Registration Welcome Email
     */
    public function sendRegistrationWelcome($userEmail, $userName) {
        $subject = "Welcome to Printmont! Account Created Successfully";
        $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
            <div style='background-color: #0b53a1; padding: 20px; text-align: center; color: #ffffff;'>
                <h2 style='margin: 0;'>Welcome to Printmont!</h2>
            </div>
            <div style='padding: 25px; color: #333333;'>
                <p style='font-size: 16px;'>Hello <b>" . htmlspecialchars($userName) . "</b>,</p>
                <p>Thank you for registering with <b>Printmont</b>. Your account has been created successfully using <b>" . htmlspecialchars($userEmail) . "</b>.</p>
                <p>You can now explore thousands of customizable corporate gifts, promotional items, t-shirts, and merchandises.</p>
                <p style='font-size: 13px; color: #777777;'>If you did not register for this account, please ignore this email.</p>
            </div>
            <div style='background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #888888; border-top: 1px solid #e0e0e0;'>
                &copy; " . date('Y') . " Printmont Corporate Gifts & Merchandise. All rights reserved.
            </div>
        </div>";

        return $this->sendEmail($userEmail, $subject, $htmlBody, 'Registration');
    }

    /**
     * Send OTP Code Email
     */
    public function sendOtpEmail($userEmail, $otp) {
        $subject = "Your Printmont Verification Code: {$otp}";
        $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; padding: 25px;'>
            <h3 style='color: #0b53a1; margin-top: 0;'>Verification Code</h3>
            <p>Your OTP for verification on Printmont is:</p>
            <div style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #28a745; background-color: #f1f8e9; padding: 15px; text-align: center; border-radius: 6px; margin: 20px 0;'>
                {$otp}
            </div>
            <p style='font-size: 13px; color: #777;'>This code is valid for 10 minutes. Do not share this OTP with anyone.</p>
        </div>";

        return $this->sendEmail($userEmail, $subject, $htmlBody, 'OTP Verification');
    }
}
