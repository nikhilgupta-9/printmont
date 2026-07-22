<?php
require_once __DIR__ . '/../config/database.php';

class MailService {
    private $db;
    private $defaultEmail = 'web2techamit@gmail.com';
    private $defaultPassword = 'rdpnttnyhwxlbgom';
    private $defaultHost = 'ssl://smtp.gmail.com';
    private $defaultPort = 465;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get active email configuration by purpose from database or fallback to defaults
     */
    private function getConfigForPurpose($purpose = 'Registration') {
        $email = $this->defaultEmail;
        $pass = $this->defaultPassword;
        $host = $this->defaultHost;
        $port = $this->defaultPort;
        $fromName = 'Printmont Store';

        if ($this->db) {
            $stmt = $this->db->prepare("SELECT * FROM email_configurations WHERE (purpose = ? OR config_name LIKE ?) AND status = 'active' LIMIT 1");
            $likePurpose = "%{$purpose}%";
            $stmt->bind_param("ss", $purpose, $likePurpose);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $email = !empty($row['mail_username']) ? $row['mail_username'] : (!empty($row['smtp_user']) ? $row['smtp_user'] : $email);
                $pass = !empty($row['mail_password']) ? $row['mail_password'] : (!empty($row['smtp_pass']) ? $row['smtp_pass'] : $pass);
                $fromName = !empty($row['mail_from_name']) ? $row['mail_from_name'] : (!empty($row['from_name']) ? $row['from_name'] : $fromName);
            }
        }

        return [
            'username' => $email,
            'password' => $pass,
            'host' => $host,
            'port' => $port,
            'fromName' => $fromName
        ];
    }

    /**
     * Send HTML email using native socket SMTP connection
     */
    public function sendEmail($to, $subject, $bodyHtml, $purpose = 'Registration', $customFromName = null) {
        $config = $this->getConfigForPurpose($purpose);
        $fromName = $customFromName ? $customFromName : $config['fromName'];

        $socket = @fsockopen($config['host'], $config['port'], $errno, $errstr, 15);
        if (!$socket) {
            error_log("MailService Connection error: $errstr ($errno)");
            return false;
        }

        $getResponse = function($s) {
            $response = "";
            while ($line = fgets($s, 512)) {
                $response .= $line;
                if (substr($line, 3, 1) == " ") break;
            }
            return $response;
        };

        $sendCommand = function($s, $cmd) use ($getResponse) {
            fputs($s, $cmd . "\r\n");
            return $getResponse($s);
        };

        $getResponse($socket); // banner
        $sendCommand($socket, "EHLO printmont.local");
        $sendCommand($socket, "AUTH LOGIN");
        $sendCommand($socket, base64_encode($config['username']));
        $authRes = $sendCommand($socket, base64_encode($config['password']));

        if (strpos($authRes, '235') === false) {
            fclose($socket);
            error_log("MailService Auth failed: " . trim($authRes));
            return false;
        }

        $sendCommand($socket, "MAIL FROM: <{$config['username']}>");
        $sendCommand($socket, "RCPT TO: <{$to}>");
        $sendCommand($socket, "DATA");

        $headers  = "From: {$fromName} <{$config['username']}>\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";

        fputs($socket, $headers . $bodyHtml . "\r\n.\r\n");
        $sendRes = $getResponse($socket);

        $sendCommand($socket, "QUIT");
        fclose($socket);

        return strpos($sendRes, '250') !== false;
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
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='http://localhost:5173/login' style='background-color: #ffc107; color: #000000; padding: 12px 25px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block;'>Login to Your Account</a>
                </div>
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
